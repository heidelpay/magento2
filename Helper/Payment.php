<?php
namespace Heidelpay\Gateway\Helper;

use Heidelpay\MessageCodeMapper\MessageCodeMapper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\ScopeInterface;

/**
 * Heidelpay payment helper
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
class Payment extends AbstractHelper
{
    protected $_invoiceOrderEmail = true;
    protected $_debug = false;

    /** @var ZendClientFactory */
    protected $httpClientFactory;

    /** @var \Magento\Framework\DB\TransactionFactory */
    protected $transactionFactory;

    /** @var \Magento\Framework\Locale\Resolver */
    protected $localeResolver;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    protected $imageHelperFactory;

    /**
     * @param Context                                            $context
     * @param \Magento\Framework\HTTP\ZendClientFactory          $httpClientFactory
     * @param \Magento\Framework\DB\TransactionFactory           $transactionFactory
     * @param \Magento\Framework\Locale\Resolver                 $localeResolver
     * @param \Magento\Store\Model\App\Emulation                 $appEmulation
     * @param \Magento\Catalog\Helper\ImageFactory               $imageHelperFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->transactionFactory = $transactionFactory;
        $this->localeResolver = $localeResolver;
        $this->appEmulation = $appEmulation;
        $this->imageHelperFactory = $imageHelperFactory;

        parent::__construct($context);
    }

    public function splitPaymentCode($PAYMENT_CODE)
    {
        return preg_split('/\./', $PAYMENT_CODE);
    }

    /**
     * Returns an array containing the heidelpay authentication data
     * (sender-id, user login, password, transaction channel).
     *
     * @param string $code the payment method code
     * @param int|string $storeId id of the store front
     *
     * @return array configuration form backend
     */
    public function getHeidelpayAuthenticationConfig($code = '', $storeId = null)
    {
        $path = 'payment/hgwmain/';
        $config = [];

        $config['SECURITY.SENDER'] = $this->scopeConfig->getValue(
            $path . 'security_sender',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($this->scopeConfig->getValue($path . 'sandbox_mode', ScopeInterface::SCOPE_STORE, $storeId) == 0) {
            $config['TRANSACTION.MODE'] = 'LIVE';
        } else {
            $config['TRANSACTION.MODE'] = 'CONNECTOR_TEST';
        }

        $config['USER.LOGIN'] = trim(
            $this->scopeConfig->getValue(
                $path . 'user_login',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );

        $config['USER.PWD'] = trim(
            $this->scopeConfig->getValue(
                $path . 'user_passwd',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );

        $path = 'payment/' . $code . '/';
        $config['TRANSACTION.CHANNEL'] = trim(
            $this->scopeConfig->getValue(
                $path . 'channel',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );

        return $config;
    }

    /**
     * @param array                      $data
     * @param \Magento\Sales\Model\Order $order
     * @param bool                       $message
     */
    public function mapStatus($data, $order, $message = false)
    {
        $paymentCode = $this->splitPaymentCode($data['PAYMENT_CODE']);

        $message = !empty($message) ? $message : $data['PROCESSING_RETURN'];

        $quoteID = ($order->getLastQuoteId() === false)
            ? $order->getQuoteId()
            : $order->getLastQuoteId(); // last_quote_id workaround for trusted shop buyerprotection

        // If an order has been canceled, closed or complete -> do not change order status.
        if ($order->getStatus() == Order::STATE_CANCELED
            || $order->getStatus() == Order::STATE_CLOSED
            || $order->getStatus() == Order::STATE_COMPLETE
        ) {
            // you can use this event for example to get a notification when a canceled order has been paid
            return;
        }

        if ($data['PROCESSING_RESULT'] == 'NOK') {
            $order->getPayment()->getMethodInstance()->cancelledTransactionProcessing($order, $message);
        } elseif ($this->isProcessing($paymentCode[1], $data)) {
            $order->getPayment()->getMethodInstance()->processingTransactionProcessing($data, $order);
        } else {
            $order->getPayment()->getMethodInstance()->pendingTransactionProcessing($data, $order, $message);
        }
    }

    /**
     * function to format amount
     *
     * @param mixed $number
     *
     * @return string
     */
    public function format($number)
    {
        return number_format($number, 2, '.', '');
    }

    /**
     * helper to generate customer payment error messages
     *
     * @param string|null $errorCode
     *
     * @return string
     * @throws \Heidelpay\MessageCodeMapper\Exceptions\MissingLocaleFileException
     */
    public function handleError($errorCode = null)
    {
        $messageCodeMapper = new MessageCodeMapper($this->localeResolver->getLocale());
        return $messageCodeMapper->getMessage($errorCode);
    }

    /**
     * Checks if the currency in the data set matches the currency in the order.
     *
     * @param Order $order
     * @param array $data
     *
     * @return bool
     */
    public function isMatchingAmount(Order $order, $data)
    {
        if (!isset($data['PRESENTATION_AMOUNT'])) {
            return false;
        }

        return $this->format($order->getGrandTotal()) == $data['PRESENTATION_AMOUNT'];
    }

    /**
     * Checks if the currency in the data set matches the currency in the order.
     *
     * @param Order $order
     * @param array $data
     *
     * @return bool
     */
    public function isMatchingCurrency(Order $order, $data)
    {
        if (!isset($data['PRESENTATION_CURRENCY'])) {
            return false;
        }

        return $order->getOrderCurrencyCode() == $data['PRESENTATION_CURRENCY'];
    }

    /**
     * Checks if the data indicates a processing payment transaction.
     *
     * @param string $paymentCode
     * @param array  $data
     *
     * @return bool
     */
    public function isProcessing($paymentCode, $data)
    {
        if (!isset($data['PROCESSING_RESULT']) && !isset($data['PROCESSING_STATUS_CODE'])) {
            return false;
        }

        return in_array($paymentCode, ['CP', 'DB', 'FI', 'RC'])
            && $data['PROCESSING_RESULT'] == 'ACK'
            && $data['PROCESSING_STATUS_CODE'] != 80;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function isPreAuthorization(array $data)
    {
        if (!isset($data['PAYMENT_CODE'])) {
            return false;
        }

        $paymentCode = $this->splitPaymentCode($data['PAYMENT_CODE']);

        if ($paymentCode[1] == 'PA') {
            return true;
        }

        return false;
    }

    /**
     * Determines if the payment code and type are for a receipt.
     *
     * @param string $paymentMethod
     * @param string $paymentType
     *
     * @return bool
     */
    public function isReceiptAble($paymentMethod, $paymentType)
    {
        if ($paymentType !== 'RC') {
            return false;
        }

        switch ($paymentMethod) {
            case 'DD':
            case 'PP':
            case 'IV':
            case 'OT':
            case 'PC':
            case 'MP':
            case 'HP':
                $return = true;
                break;

            default:
                $return = false;
                break;
        }

        return $return;
    }

    /**
     * Checks if the given paymentcode is viable for a refund transaction.
     *
     * @param string $paymentcode
     *
     * @return bool
     */
    public function isRefundable($paymentcode)
    {
        if ($paymentcode === 'DB' || $paymentcode === 'CP' || $paymentcode === 'RC') {
            return true;
        }

        return false;
    }

    /**
     * Saves a transaction by the given invoice.
     *
     * @param Invoice $invoice
     */
    public function saveTransaction(Invoice $invoice)
    {
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();
    }

    /**
     * Converts a Quote to a heidelpay PHP Basket Api Request instance.
     *
     * @param Quote $quote
     *
     * @return \Heidelpay\PhpBasketApi\Request|null
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
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );

        // initialize the basket request
        $basketRequest = new \Heidelpay\PhpBasketApi\Request();

        $basketRequest->getBasket()
            ->setCurrencyCode($quote->getQuoteCurrencyCode())
            ->setAmountTotalNet((int) ($quote->getGrandTotal() * 100))
            ->setAmountTotalVat((int) ($quote->getShippingAddress()->getTaxAmount() * 100))
            ->setAmountTotalDiscount((int) ($quote->getShippingAddress()->getDiscountAmount() * 100))
            ->setBasketReferenceId(sprintf('M2-S%dQ%d-%s', $quote->getStoreId(), $quote->getId(), date('YmdHis')));

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllVisibleItems() as $item) {
            $basketItem = new \Heidelpay\PhpBasketApi\Object\BasketItem();

            $basketItem->setQuantity($item->getQty())
                ->setVat((int) ($item->getTaxPercent() * 100))
                ->setAmountPerUnit((int) ($item->getPrice() * 100))
                ->setAmountVat((int) ($item->getTaxAmount() * 100))
                ->setAmountNet((int) ($item->getRowTotal() * 100))
                ->setAmountGross((int) ($item->getRowTotalInclTax() * 100))
                ->setAmountDiscount((int) ($item->getDiscountAmount() * 100))
                ->setTitle($item->getName())
                ->setDescription($item->getDescription())
                ->setArticleId($item->getSku())
                ->setImageUrl(
                    $this->imageHelperFactory->create()->init($item->getProduct(), 'category_page_list')->getUrl()
                )
                ->setBasketItemReferenceId(
                    sprintf('M2-S%dQ%d-%s-x%d', $quote->getStoreId(), $quote->getId(), $item->getSku(), $item->getQty())
                );

            $basketRequest->getBasket()->addBasketItem($basketItem);
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

        $config = $this->getHeidelpayAuthenticationConfig('', $quote->getStoreId());

        // create a basketApiRequest instance by converting the quote and it's items
        if (!$basketApiRequest = $this->convertQuoteToBasket($quote)) {
            $this->_logger->warning('heidelpay - submitQuoteToBasketApi: basketApiRequest is null.');
            return null;
        }

        $basketApiRequest->setAuthentication($config['USER.LOGIN'], $config['USER.PWD'], $config['SECURITY.SENDER']);

        // add a new basket via api request by sending the addNewBasket request
        $basketApiResponse = $basketApiRequest->addNewBasket();

        // if the request wasn't successful, log the error message(s) and return null, because we got no BasketId.
        if ($basketApiResponse->isFailure()) {
            $this->_logger->warning($basketApiResponse->printMessage());
            return null;
        }

        // TODO: remove debug log
        $this->_logger->debug($basketApiResponse->printMessage() . ' Response: ' . $basketApiResponse->toJson());

        return $basketApiResponse->getBasketId();
    }
}
