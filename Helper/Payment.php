<?php
namespace Heidelpay\Gateway\Helper;

use Exception;
use Heidelpay\MessageCodeMapper\Exceptions\MissingLocaleFileException;
use Heidelpay\MessageCodeMapper\MessageCodeMapper;
use Heidelpay\PhpPaymentApi\Constants\PaymentMethod;
use Heidelpay\PhpPaymentApi\Constants\ProcessingResult;
use Heidelpay\PhpPaymentApi\Constants\StatusCode;
use Heidelpay\PhpPaymentApi\Constants\TransactionType;
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

/**
 * Heidelpay payment helper
 *
 * The payment helper is a collection of function to prepare an send
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

    /**
     * @param Context $context
     * @param ZendClientFactory $httpClientFactory
     * @param TransactionFactory $transactionFactory
     * @param Resolver $localeResolver
     * @param QuoteManagement $cartManagement
     * @param Transaction $heidelpayTransactionFactory
     */
    public function __construct(
        Context $context,
        ZendClientFactory $httpClientFactory,
        TransactionFactory $transactionFactory,
        Resolver $localeResolver,
        QuoteManagement $cartManagement,
        Transaction $heidelpayTransactionFactory
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->transactionFactory = $transactionFactory;
        $this->localeResolver = $localeResolver;

        parent::__construct($context);
        $this->_cartManagement = $cartManagement;
        $this->heidelpayTransactionFactory = $heidelpayTransactionFactory;
    }

    /**
     * Returns an array containing the payment method code and the transaction type code.
     *
     * @see PaymentMethod
     * @see TransactionType
     * @param $PAYMENT_CODE
     * @return array
     */
    public function splitPaymentCode($PAYMENT_CODE)
    {
        return explode('.', $PAYMENT_CODE);
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

        $payment = $order->getPayment();
        if ($data['PROCESSING_RESULT'] === ProcessingResult::NOK) {
            $payment->getMethodInstance()->cancelledTransactionProcessing($order, $message);
        } elseif ($this->isProcessing($paymentCode[1], $data)) {
            $payment->getMethodInstance()->processingTransactionProcessing($data, $order);
        } else {
            $payment->getMethodInstance()->pendingTransactionProcessing($data, $order, $message);
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
     * @throws MissingLocaleFileException
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
     * @throws Exception
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
     * Create an order by submitting the quote.
     * @param Quote $quote
     * @return AbstractExtensibleModel|OrderInterface|null|object
     * @throws LocalizedException
     */
    public function createOrderFromQuote($quote)
    {
        // Ensure to use the currency of the quote.
        $quote->getStore()->setCurrentCurrencyCode($quote->getQuoteCurrencyCode());
        $quote->collectTotals();
        // in case of guest checkout, set some customer related data.
        if ($quote->getCustomerId() === null) {
            $quote->setCustomerId(null)
                ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        }

        return $this->_cartManagement->submit($quote);
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
}
