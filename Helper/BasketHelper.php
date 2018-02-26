<?php
namespace Heidelpay\Gateway\Helper;

use Heidelpay\Gateway\Gateway\Config\MainConfigInterface;
use Heidelpay\Gateway\Wrapper\ItemWrapper;
use Heidelpay\Gateway\Wrapper\QuoteWrapper;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use Heidelpay\PhpBasketApi\Request;
use /** @noinspection PhpUndefinedClassInspection */
    Magento\Catalog\Helper\ImageFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use /** @noinspection PhpUndefinedClassInspection */
    Magento\Framework\HTTP\ZendClientFactory;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\App\Emulation;

/**
 * Heidelpay basket helper
 *
 * The payment helper is a collection of function to prepare an send
 *
 * @license    Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright  Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link       https://dev.heidelpay.de/magento
 *
 * @author     Jens Richter
 *
 * @package    Heidelpay
 * @subpackage Magento2
 * @category   Magento2
 */
class BasketHelper extends AbstractHelper
{
    /**
     * @var Emulation $appEmulation
     */
    private $appEmulation;

    /**
     * @var ImageFactory $imageHelperFactory
     */
    private $imageHelperFactory;

    /**
     * @var MainConfigInterface
     */
    private $mainConfig;

    /**
     * @param Context $context
     * @param Emulation $appEmulation
     * @param ImageFactory $imageHelperFactory
     * @param MainConfigInterface $mainConfig
     */
    public function __construct(
        Context $context,
        Emulation $appEmulation,
        /** @noinspection PhpUndefinedClassInspection */
        ImageFactory $imageHelperFactory,
        MainConfigInterface $mainConfig
    ) {
        $this->appEmulation = $appEmulation;
        $this->imageHelperFactory = $imageHelperFactory;
        $this->mainConfig = $mainConfig;

        parent::__construct($context);
    }

    /**
     * Converts a Quote to a heidelpay PHP Basket Api Request instance.
     *
     * @param Quote $quote
     *
     * @return Request|null
     * @throws \Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException
     */
    public function convertQuoteToBasket(Quote $quote)
    {
        // if no (valid) quote is supplied, we can't convert it to a heidelpay Basket object.
        if ($quote === null || $quote->isEmpty()) {
            return null;
        }

        // we emulate that we are in the frontend to get frontend product images.
        $this->appEmulation->startEnvironmentEmulation(
            $quote->getStoreId(),
            Area::AREA_FRONTEND,
            true
        );

        /** @var QuoteWrapper $basketTotals */
        $basketTotals = ObjectManager::getInstance()->create(QuoteWrapper::class, ['quote' => $quote]);

        // initialize the basket request
        $basketRequest = new Request();
        $basketReferenceId = $basketTotals->getBasketReferenceId();
        $basketRequest->getBasket()
            ->setCurrencyCode($basketTotals->getCurrencyCode())
            ->setAmountTotalNet($basketTotals->getSubtotalWithDiscountAndShipping())
            ->setAmountTotalVat($basketTotals->getActualTaxAmount())
            ->setAmountTotalDiscount($basketTotals->getTotalDiscountAmount())
            ->setBasketReferenceId($basketReferenceId);

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllVisibleItems() as $item) {
            $basketItem = ObjectManager::getInstance()->create(BasketItem::class);

            /** @var ItemWrapper $itemTotals */
            $itemTotals = ObjectManager::getInstance()->create(ItemWrapper::class, ['item' => $item]);

            $basketItem->setQuantity($item->getQty())
                ->setVat($itemTotals->getTaxPercent())
                ->setAmountPerUnit($itemTotals->getPrice())
                ->setAmountNet($itemTotals->getRowTotal())
                ->setAmountVat($itemTotals->getTaxAmount())
                ->setAmountGross($itemTotals->getRowTotalInclTax())

                ->setTitle($item->getName())
                ->setDescription($item->getDescription())
                ->setArticleId($item->getSku())
                ->setBasketItemReferenceId($itemTotals->getReferenceId($basketReferenceId));

            /** @noinspection PhpUndefinedMethodInspection */
            $basketItem->setImageUrl(
                $this->imageHelperFactory->create()->init($item->getProduct(), 'category_page_list')->getUrl()
            );

            $basketRequest->getBasket()->addBasketItem($basketItem);
        }

        /** @var BasketItem $shippingPos */
        $shippingPos = ObjectManager::getInstance()->create(BasketItem::class);
        $itemCount = count($quote->getAllVisibleItems());
        $shippingPos->setQuantity(1)
            ->setTitle('Shipping')
            ->setType('shipment')
            ->setVat($basketTotals->getShippingTaxPercent())
            ->setAmountVat($basketTotals->getShippingTaxAmount())
            ->setAmountPerUnit($basketTotals->getShippingInclTax())
            ->setAmountNet($basketTotals->getShippingAmount())
            ->setAmountGross($basketTotals->getShippingInclTax())
            ->setBasketItemReferenceId($itemCount);
        $basketRequest->getBasket()->addBasketItem($shippingPos);

        if ($basketTotals->getTotalDiscountAmount() > 0) {
            /** @var BasketItem $discountPosition */
            $discountPosition = ObjectManager::getInstance()->create(BasketItem::class);
            $discountPosition->setQuantity(1)
                ->setTitle('Discount')
                ->setType('discount')
                ->setAmountPerUnit(0)
                ->setAmountNet(0)
                ->setAmountDiscount($basketTotals->getTotalDiscountAmount())
                ->setBasketItemReferenceId($itemCount);
            $basketRequest->getBasket()->addBasketItem($discountPosition);
        }

        // stop the frontend environment emulation
        $this->appEmulation->stopEnvironmentEmulation();

        return $basketRequest;
    }

    /**
     * @param Quote|null $quote
     *
     * @return null|string
     * @throws \Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException
     */
    public function submitQuoteToBasketApi(Quote $quote = null)
    {
        if ($quote === null || $quote->isEmpty()) {
            return null;
        }

        // create a basketApiRequest instance by converting the quote and it's items
        if (!$basketApiRequest = $this->convertQuoteToBasket($quote)) {
            $this->_logger->warning('heidelpay - submitQuoteToBasketApi: basketApiRequest is null.');
            return null;
        }

        $basketApiRequest->setAuthentication(
            $this->mainConfig->getUserLogin(),
            $this->mainConfig->getUserPasswd(),
            $this->mainConfig->getSecuritySender()
        );

        // add a new basket via api request by sending the addNewBasket request
        $basketApiResponse = $basketApiRequest->addNewBasket();

        // if the request wasn't successful, log the error message(s) and return null, because we got no BasketId.
        if ($basketApiResponse->isFailure()) {
            $this->_logger->warning($basketApiResponse->printMessage());
            return null;
        }

        return $basketApiResponse->getBasketId();
    }
}
