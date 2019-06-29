<?php
/**
 * This is the payment class for heidelpay prepayment
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link http://dev.heidelpay.com/magento2
 * @author Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
namespace Heidelpay\Gateway\PaymentMethods;

use Exception;
use Heidelpay\Gateway\Block\Info\Prepayment;
use Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException;
use Heidelpay\PhpPaymentApi\PaymentMethods\PrepaymentPaymentMethod;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;

/** @noinspection LongInheritanceChainInspection */
/**
 * @property PrepaymentPaymentMethod $_heidelpayPaymentMethod
 */
class HeidelpayPrepaymentPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string PaymentCode */
    const CODE = 'hgwpp';

    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();
        $this->_canAuthorize = true;
        $this->_canRefund = true;
        $this->_canRefundInvoicePartial = true;
        $this->_formBlockType = Prepayment::class;
    }

    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     * @throws UndefinedTransactionModeException
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote, array $data = [])
    {
        parent::getHeidelpayUrl($quote);

        // set payment type to authorize
        $this->_heidelpayPaymentMethod->authorize();

        return $this->_heidelpayPaymentMethod->getResponse();
    }

    /**
     * @inheritdoc
     */
    public function additionalPaymentInformation($response)
    {
        return __(
            'Please transfer the amount of <strong>%1 %2</strong> to the following account<br /><br />'
            . 'Holder: %3<br/>IBAN: %4<br/>BIC: %5<br/><br/><i>'
            . 'Please use only this identification number as the descriptor :</i><br/><strong>%6</strong>',
            $this->_paymentHelper->format($response['PRESENTATION_AMOUNT']),
            $response['PRESENTATION_CURRENCY'],
            $response['CONNECTOR_ACCOUNT_HOLDER'],
            $response['CONNECTOR_ACCOUNT_IBAN'],
            $response['CONNECTOR_ACCOUNT_BIC'],
            $response['IDENTIFICATION_SHORTID']
        );
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function pendingTransactionProcessing($data, &$order, $message = null)
    {
        $payment = $order->getPayment();
        $payment->setTransactionId($data['IDENTIFICATION_UNIQUEID']);
        $payment->setIsTransactionClosed(false);
        $payment->addTransaction(Transaction::TYPE_AUTH, null, true);

        $order->setState(Order::STATE_PENDING_PAYMENT)
            ->addCommentToStatusHistory($message, Order::STATE_PENDING_PAYMENT)
            ->setIsCustomerNotified(true);

        // payment is pending at the beginning, so we set the total paid sum to 0.
        $order->setTotalPaid(0.00);

        // if the order can be invoiced, create one and save it into a transaction.
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE)
                ->setTransactionId($data['IDENTIFICATION_UNIQUEID'])
                ->setIsPaid(false)
                ->register();

            $this->_paymentHelper->saveTransaction($invoice);
        }
    }
}
