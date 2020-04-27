<?php
namespace Heidelpay\Gateway\Helper;

use Exception;
use Heidelpay\Gateway\Helper\Order as OrderHelper;
use Heidelpay\MessageCodeMapper\Exceptions\MissingLocaleFileException;
use Heidelpay\MessageCodeMapper\MessageCodeMapper;
use Heidelpay\PhpPaymentApi\Constants\TransactionType;
use Heidelpay\PhpPaymentApi\Constants\PaymentMethod;
use Heidelpay\PhpPaymentApi\Constants\ProcessingResult;
use Heidelpay\PhpPaymentApi\Constants\StatusCode;
use Heidelpay\PhpPaymentApi\Response;
use Magento\Customer\Model\Group;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Heidelpay\Gateway\Model\Transaction;
use Heidelpay\Gateway\Model\TransactionFactory as HgwTransactionFactory;
use Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;
use Magento\Framework\Lock\LockManagerInterface;


/**
 * Heidelpay payment helper
 *
 * The payment helper is a collection of function to prepare an send payment requests
 *
 * @license    Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright  Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link       http://dev.heidelpay.com/magento2
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

    /** @var TransactionFactory */
    protected $transactionFactory;

    /** @var Resolver */
    protected $localeResolver;

    /** @var QuoteManagement */
    private $_cartManagement;

    /** @var HgwTransactionFactory */
    private $heidelpayTransactionFactory;

    const NEW_ORDER_TRANSACTION_TYPE_ARRAY = [
        TransactionType::RECEIPT,
        TransactionType::DEBIT
    ];
    /**
     * @var PaymentInformationCollectionFactory
     */
    private $paymentInformationCollectionFactory;
    /**
     * @var \Heidelpay\Gateway\Helper\Order
     */
    private $orderHelper;
    /**
     * @var LockManagerInterface
     */
    private $lockManager;

    /**
     * @param Context $context
     * @param ZendClientFactory $httpClientFactory
     * @param TransactionFactory $transactionFactory
     * @param Resolver $localeResolver
     * @param QuoteManagement $cartManagement
     * @param PaymentInformationCollectionFactory $paymentInformationCollectionFactory
     * @param HgwTransactionFactory $heidelpayTransactionFactory
     * @param OrderHelper $orderHelper
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        Context $context,
        ZendClientFactory $httpClientFactory,
        TransactionFactory $transactionFactory,
        Resolver $localeResolver,
        QuoteManagement $cartManagement,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        HgwTransactionFactory $heidelpayTransactionFactory,
        OrderHelper $orderHelper,
        LockManagerInterface $lockManager
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->transactionFactory = $transactionFactory;
        $this->localeResolver = $localeResolver;

        parent::__construct($context);
        $this->_cartManagement = $cartManagement;
        $this->heidelpayTransactionFactory = $heidelpayTransactionFactory;
        $this->paymentInformationCollectionFactory = $paymentInformationCollectionFactory;
        $this->orderHelper = $orderHelper;
        $this->lockManager = $lockManager;
    }

    /**
     * Returns an array containing the payment method code and the transaction type code.
     *
     * @param $paymentCode
     *
     * @return array
     *
     * @see PaymentMethod
     * @see TransactionType
     */
    public function splitPaymentCode($paymentCode)
    {
        return explode('.', $paymentCode);
    }

    /**
     * @param array $data
     * @param Order $order
     * @param bool  $message
     */
    public function mapStatus($data, $order, $message = false)
    {
        $paymentCode = $this->splitPaymentCode($data['PAYMENT_CODE']);
        $message = !empty($message) ? $message : $data['PROCESSING_RETURN'];

        // If an order has been canceled, closed or complete -> do not change order status.
        if (in_array($order->getStatus(), [Order::STATE_CANCELED, Order::STATE_CLOSED, Order::STATE_COMPLETE], true)) {
            // you can use this event for example to get a notification when a canceled order has been paid
            return;
        }

        /** @var HeidelpayAbstractPaymentMethod $paymentMethod */
        $paymentMethod = $order->getPayment()->getMethodInstance();
        if ($data['PROCESSING_RESULT'] === ProcessingResult::NOK) {
            $paymentMethod->cancelledTransactionProcessing($order, $message);
        } elseif ($this->isProcessing($paymentCode[1], $data)) {
            $paymentMethod->processingTransactionProcessing($data, $order);
        } else {
            $paymentMethod->pendingTransactionProcessing($data, $order, $message);
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

    public function getDataFromResponse(Response $response)
    {
        $data = [];

        foreach ($response->toArray() as $parameterKey => $value) {
            $data[str_replace('.', '_', $parameterKey)] = $value;
        }

        return $data;
    }

    /**
     * helper to generate customer payment error messages
     *
     * @param string|null $errorCode
     *
     * @return string
     * @throws MissingLocaleFileException
     */
    public function handleError($errorCode = null)
    {
        $messageCodeMapper = new MessageCodeMapper($this->localeResolver->getLocale());
        return $messageCodeMapper->getMessage($errorCode);
    }

    public function handleInvoiceCreation($order, $paymentCode, $uniqueId)
    {
        $data['PAYMENT_CODE'] = $paymentCode;
        if ($order->canInvoice() && !$this->isPreAuthorization($data)) {
            $invoice = $order->prepareInvoice();

            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->setTransactionId($uniqueId);
            $invoice->register()->pay();

            $this->saveTransaction($invoice);
        }
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

        return $order->getOrderCurrencyCode() === $data['PRESENTATION_CURRENCY'];
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

        $processingTransactions = [
            TransactionType::CAPTURE,
            TransactionType::DEBIT,
            TransactionType::FINALIZE,
            TransactionType::RECEIPT
        ];
        return in_array($paymentCode, $processingTransactions, true)
            && $data['PROCESSING_RESULT'] === ProcessingResult::ACK
            && $data['PROCESSING_STATUS_CODE'] !== StatusCode::WAITING;
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
        list(, $paymentCode) = $this->splitPaymentCode($data['PAYMENT_CODE']);
        return $paymentCode === TransactionType::RESERVATION;
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
        if ($paymentType !== TransactionType::RECEIPT) {
            return false;
        }

        switch ($paymentMethod) {
            case PaymentMethod::DIRECT_DEBIT:
            case PaymentMethod::PREPAYMENT:
            case PaymentMethod::INVOICE:
            case PaymentMethod::ONLINE_TRANSFER:
            case PaymentMethod::PAYMENT_CARD:
            case PaymentMethod::MOBILE_PAYMENT:
            case PaymentMethod::HIRE_PURCHASE:
                $return = true;
                break;
            default:
                $return = false;
                break;
        }

        return $return;
    }

    /**
     * Checks if the given payment code is viable for a refund transaction.
     *
     * @param string $paymentCode
     *
     * @return bool
     */
    public function isRefundable($paymentCode)
    {
        $refundableTransactions = [TransactionType::DEBIT, TransactionType::CAPTURE, TransactionType::RECEIPT];
        return in_array($paymentCode, $refundableTransactions, true);
    }

    /**
     * Saves a transaction by the given invoice.
     *
     * @param Invoice $invoice
     *
     * @throws Exception
     */
    public function saveTransaction(Invoice $invoice)
    {
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($invoice)->addObject($invoice->getOrder())->save();
    }

    /**
     * Save the heidelpay transaction data
     *
     * @param Response $response
     * @param array $data
     * @param string $source
     *
     * @return void
     *
     */
    public function saveHeidelpayTransaction(Response $response, array $data, $source)
    {
        list($paymentMethod, $paymentType) = $this->getPaymentMethodAndType($response);

        try {
            // save the response details into the heidelpay Transactions table.
            /** @var Transaction $transaction */
            $transaction = $this->heidelpayTransactionFactory->create();
            $transaction->setPaymentMethod($paymentMethod)
                ->setPaymentType($paymentType)
                ->setTransactionId($response->getIdentification()->getTransactionId())
                ->setUniqueId($response->getIdentification()->getUniqueId())
                ->setShortId($response->getIdentification()->getShortId())
                ->setStatusCode($response->getProcessing()->getStatusCode())
                ->setResult($response->getProcessing()->getResult())
                ->setReturnMessage($response->getProcessing()->getReturn())
                ->setReturnCode($response->getProcessing()->getReturnCode())
                ->setJsonResponse(json_encode($data))
                ->setSource($source)
                ->save();
        } catch (Exception $e) {
            $this->_logger->error('Heidelpay - ' . $source . ': Save transaction error. ' . $e->getMessage());
        }
    }

    /**
     * Create an order by submitting the quote. If Order for that qoute already exist this order will be returned.
     * @param Quote $quote
     * @return AbstractExtensibleModel|OrderInterface|Order|object|null
     * @throws LocalizedException
     */
    public function handleOrderCreation($quote, $context = null)
    {
        $lockName = sprintf('heidelpay_gateway_quote_%d', $quote->getId());

        $this->lockManager->lock($lockName);
        try{
            /** @var Order $order */
            $order = $this->orderHelper->fetchOrder($quote->getId());
            // Ensure to use the currency of the quote.
            if ($order === null || $order->isEmpty()) {
                $quote->getStore()->setCurrentCurrencyCode($quote->getQuoteCurrencyCode());
                $quote->collectTotals();
                // in case of guest checkout, set some customer related data.
                if ($quote->getCustomerId() === null) {
                    $quote->setCustomerId(null)
                        ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                        ->setCustomerIsGuest(true)
                        ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
                }
                $order = $this->_cartManagement->submit($quote);
                if($context) {
                    $order->addStatusHistoryComment('heidelpay - Order created via ' . $context);
                }
            }
        } finally {
            $this->lockManager->unlock($lockName);
        }

        return $order;
    }

    /**
     * Returns an array containing the payment method and payment type of the given Response object.
     *
     * @param $response
     * @return array
     */
    public function getPaymentMethodAndType(Response $response)
    {
        return $this->splitPaymentCode($response->getPayment()->getCode());
    }

    /**
     * Provide information whether a transaction type is able to create an order or not
     * @param $paymentMethod
     * @param $paymentType
     * @return bool
     */
    public function isNewOrderType($paymentMethod, $paymentType)
    {
        // Order should be created for incoming payments
        if(in_array($paymentType, self::NEW_ORDER_TRANSACTION_TYPE_ARRAY, true)){
            return true;
        }
        // Reservation should only create order if its not online transfer payment method.
        if (PaymentMethod::ONLINE_TRANSFER !== $paymentMethod && $paymentType === TransactionType::RESERVATION){
            return true;
        }
        return false;
    }

    /**
     * If the customer is a guest, we'll delete the additional payment information, which
     * is only used for customer recognition.
     * @param Quote $quote
     * @throws \Exception
     */
    public function handleAdditionalPaymentInformation($quote)
    {
        if ($quote !== null && $quote->getCustomerIsGuest()) {
            // create a new instance for the payment information collection.
            $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

            // load the payment information and delete it.
            /** @var \Heidelpay\Gateway\Model\PaymentInformation $paymentInfo */
            $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
                $quote->getStoreId(),
                $quote->getBillingAddress()->getEmail(),
                $quote->getPayment()->getMethod()
            );

            if (!$paymentInfo->isEmpty()) {
                $paymentInfo->delete();
            }
        }
    }
}
