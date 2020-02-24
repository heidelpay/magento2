<?php
namespace Heidelpay\Gateway\Helper;

use Heidelpay\Gateway\Gateway\Config\HgwMainConfigInterface;
use Heidelpay\Gateway\Wrapper\ItemWrapper;
use Heidelpay\Gateway\Wrapper\QuoteWrapper;
use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use Heidelpay\PhpBasketApi\Request;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;

/**
 * Heidelpay basket helper
 *
 * The payment helper is a collection of function to prepare an send
 *
 * @license    Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright  Copyright © 2016-present heidelpay GmbH. All rights reserved.
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
     * @var HgwMainConfigInterface
     */
    private $mainConfig;

    /**
     * @param Context $context
     * @param HgwMainConfigInterface $mainConfig
     */
    public function __construct(
        Context $context,
        HgwMainConfigInterface $mainConfig
    ) {
        $this->mainConfig = $mainConfig;

        parent::__construct($context);
    }

    /**
     * Converts a Quote to a heidelpay PHP Basket Api Request instance.
     *
     * @param Quote $quote
     *
     * @return Request|null
     * @throws InvalidBasketitemPositionException
     */
    public function convertQuoteToBasket(Quote $quote)
    {
        // if no (valid) quote is supplied, we can't convert it to a heidelpay Basket object.
        if ($quote === null || $quote->isEmpty()) {
            return null;
        }

        /** @var QuoteWrapper $basketTotals */
        $basketTotals = ObjectManager::getInstance()->create(QuoteWrapper::class, ['quote' => $quote]);

        // initialize the basket request
        $basketRequest = new Request();
        $basketReferenceId = $basketTotals->getBasketReferenceId();
        $basket = $basketRequest->getBasket();

        $amountGrandTotal = $basketTotals->normalizeValue($quote->getGrandTotal());
        $amountTotalNet = $basketTotals->getSubtotalWithDiscountAndShipping();

        $basket->setCurrencyCode($basketTotals->getCurrencyCode())
            ->setAmountTotalNet($amountTotalNet)
            ->setAmountTotalVat($amountGrandTotal - $amountTotalNet)
            ->setBasketReferenceId($basketReferenceId);

        /** @var Item $item */
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

            $basket->addBasketItem($basketItem);
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
        $basket->addBasketItem($shippingPos);

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
            $basket->addBasketItem($discountPosition);
        }
        return $basketRequest;
    }

    /**
     * @param Quote|null $quote
     *
     * @return null|string
     * @throws InvalidBasketitemPositionException
     */
    public function submitQuoteToBasketApi(Quote $quote = null)
    {
        if ($quote === null || $quote->isEmpty()) {
            return null;
        }

        // create a basketApiRequest instance by converting the quote and it's items
        /** @var Request $basketApiRequest */
        $basketApiRequest = $this->convertQuoteToBasket($quote);
        if (!$basketApiRequest) {
            $this->_logger->warning('heidelpay - submitQuoteToBasketApi: basketApiRequest is null.');
            return null;
        }

        $basketApiRequest->setAuthentication(
            $this->mainConfig->getUserLogin(),
            $this->mainConfig->getUserPasswd(),
            $this->mainConfig->getSecuritySender()
        );

        // set sandboxmode according to configured mode
        $basketApiRequest->setIsSandboxMode($this->mainConfig->isSandboxModeActive());

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
