<?php

namespace Heidelpay\Gateway\Helper;

use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Heidelpay payment helper
 *
 * The payment helper is a collection of function to prepare an send
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/magento
 *
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
class Payment extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_invoiceOrderEmail = true;
    protected $_debug = false;

    /** @var ZendClientFactory */
    protected $httpClientFactory;

    /** @var Logger */
    protected $log;

    /** @var \Magento\Framework\DB\TransactionFactory */
    protected $transactionFactory;

    /** @var \Heidelpay\CustomerMessages\CustomerMessage */
    protected $customerMessage;

    /**
     * @param ZendClientFactory $httpClientFactory
     * @param Logger $logger
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Heidelpay\CustomerMessages\CustomerMessage $customerMessage
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        Logger $logger,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Heidelpay\CustomerMessages\CustomerMessage $customerMessage
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->log = $logger;
        $this->transactionFactory = $transactionFactory;
    }

    public function splitPaymentCode($PAYMENT_CODE)
    {
        return preg_split('/\./', $PAYMENT_CODE);
    }


    public function mapStatus($data, $order, $message = false)
    {
        $PaymentCode = $this->splitPaymentCode($data['PAYMENT_CODE']);
        $totalypaid = false;

        $message = (!empty($message)) ? $message : $data['PROCESSING_RETURN'];

        $quoteID = ($order->getLastQuoteId() === false)
            ? $order->getQuoteId()
            : $order->getLastQuoteId(); // last_quote_id workaround for trusted shop buyerprotection

        /**
         * If an order has been canceled, cloesed or complete do not change order status
         */
        if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_CANCELED or
            $order->getStatus() == \Magento\Sales\Model\Order::STATE_CLOSED or
            $order->getStatus() == \Magento\Sales\Model\Order::STATE_COMPLETE
        ) {
            // you can use this event for example to get a notification when a canceled order has been paid
            return;
        }

        if ($data['PROCESSING_RESULT'] == 'NOK') {
            if ($order->canCancel()) {
                $order->cancel();

                $state = \Magento\Sales\Model\Order::STATE_CANCELED;
                $status = \Magento\Sales\Model\Order::STATE_CANCELED;

                $order->setState($state)
                    ->addStatusHistoryComment($message, $status)
                    ->setIsCustomerNotified(false);
            }
        } elseif (($PaymentCode[1] == 'CP' or $PaymentCode[1] == 'DB' or $PaymentCode[1] == 'FI' or $PaymentCode[1] == 'RC')
            and ($data['PROCESSING_RESULT'] == 'ACK' and $data['PROCESSING_STATUS_CODE'] != 80)
        ) {
            $message = __('ShortId : %1', $data['IDENTIFICATION_SHORTID']);

            $order->getPayment()->setTransactionId($data['IDENTIFICATION_UNIQUEID'])
                ->setParentTransactionId($order->getPayment()->getLastTransId())
                ->setIsTransactionClosed(true);

            if ($this->format($order->getGrandTotal()) == $data['PRESENTATION_AMOUNT'] and $order->getOrderCurrencyCode() == $data['PRESENTATION_CURRENCY']) {
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $status = \Magento\Sales\Model\Order::STATE_PROCESSING;

                $order->setState($state)
                    ->addStatusHistoryComment($message, $status)
                    ->setIsCustomerNotified(true);
                $totalypaid = true;
            } else {
                /*
                 * in case rc is ack and amount is to low/heigh or curreny missmatch
                 */
                $message = __('Amount or currency missmatch : %1',
                    $data['PRESENTATION_AMOUNT'] . ' ' . $data['PRESENTATION_CURRENCY']);
                $state = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
                $status = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
                $order->setState($state)
                    ->addStatusHistoryComment($message, $status)
                    ->setIsCustomerNotified(true);
            }


            if ($order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->setIsPaid(true);
                $invoice->pay();
                $transaction = $this->transactionFactory->create();
                $transaction->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();
            }

            $order->getPayment()->addTransaction(
                Transaction::TYPE_CAPTURE,
                null,
                true
            );
        } else {
            $order->getPayment()->setTransactionId($data['IDENTIFICATION_UNIQUEID']);
            $order->getPayment()->setIsTransactionClosed(false);

            $order->getPayment()->addTransaction(
                Transaction::TYPE_AUTH,
                null,
                true
            );
            $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            $status = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            $order->setState($state)
                ->addStatusHistoryComment($message, $status)
                ->setIsCustomerNotified(true);
        }
    }

    /**
     * function to format amount
     *
     * @param mixed $number
     * @return string
     */
    public function format($number)
    {
        return number_format($number, 2, '.', '');
    }

    /**
     * @param string $default
     * @return string
     */
    public function getLang($default = 'en')
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        if (!empty($locale)) {
            return strtoupper($locale[0]);
        }
        return strtoupper($default); //TOBO falses Module
    }

    /**
     * helper to generate customer payment error messages
     *
     * @param null|mixed $errorCode
     * @return string
     */
    public function handleError($errorCode = null)
    {
        return $this->customerMessage->getMessage($errorCode);
    }
}
