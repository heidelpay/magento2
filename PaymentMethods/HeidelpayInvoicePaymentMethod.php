<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\PhpPaymentApi\PaymentMethods\InvoicePaymentMethod;
/**
 * Heidelpay prepayment payment method
 *
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
class HeidelpayInvoicePaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     * @var string
     */
    const CODE = 'hgwiv';

    /**
     * Payment Code
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * Info Block Class (used for Order/Invoice details)
     * @var string
     */
    protected $_infoBlockType = 'Heidelpay\Gateway\Block\Info\Invoice';

    /**
     * isGateway
     * @var boolean
     */
    protected $_isGateway = true;

    /**
     * canAuthorize
     * @var boolean
     */
    protected $_canAuthorize = true;

    /** @var boolean */
    protected $_canRefund = true;

    /** @var boolean */
    protected $_canRefundInvoicePartial = true;

    /** @var InvoicePaymentMethod */
    protected $_heidelpayPaymentMethod;

    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote)
    {
        // set initial data for the request
        parent::getHeidelpayUrl($quote);

        // send the authorize request
        $this->_heidelpayPaymentMethod->authorize();

        return $this->_heidelpayPaymentMethod->getResponse();
    }

    /**
     * @inheritdoc
     */
    public function additionalPaymentInformation($response)
    {
        return __(
            'Please transfer the amount of <strong>%1 %2</strong> '
            . 'to the following account after your order has arrived:<br /><br />'
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
     */
    public function pendingTransactionProcessing($data, &$order, $message = null)
    {
        $order->getPayment()->setTransactionId($data['IDENTIFICATION_UNIQUEID']);
        $order->getPayment()->setIsTransactionClosed(false);
        $order->getPayment()->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true);

        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
            ->addStatusHistoryComment($message, \Magento\Sales\Model\Order::STATE_PROCESSING)
            ->setIsCustomerNotified(true);

        // payment is pending at the beginning, so we set the total paid sum to 0.
        $order->setTotalPaid(0.00)->setBaseTotalPaid(0.00);

        // if the order can be invoiced, create one and save it into a transaction.
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE)
                ->setTransactionId($data['IDENTIFICATION_UNIQUEID'])
                ->setIsPaid(false)
                ->register();

            $this->_paymentHelper->saveTransaction($invoice);
        }
    }
}
