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

use Heidelpay\Gateway\Block\Info\InvoiceSecured as InvoiceSecuredBlock;
use Heidelpay\Gateway\Model\PaymentInformation;
use Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException;
use Heidelpay\PhpPaymentApi\PaymentMethods\InvoiceB2CSecuredPaymentMethod;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;

/** @noinspection LongInheritanceChainInspection */
/**
 * @property InvoiceB2CSecuredPaymentMethod $_heidelpayPaymentMethod
 */
class HeidelpayInvoiceSecuredPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string Payment Code */
    const CODE = 'hgwivs';

    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();
        $this->_canAuthorize            = true;
        $this->_canRefund               = true;
        $this->_canRefundInvoicePartial = true;
        $this->_usingBasket             = true;
        $this->_formBlockType           = InvoiceSecuredBlock::class;
        $this->_infoBlockType           = InvoiceSecuredBlock::class;
        $this->useShippingAddressAsBillingAddress   = true;
    }

    /**
     * @inheritDoc
     * @throws UndefinedTransactionModeException
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote, array $data = [])
    {
        $paymentInfo = $this->getPaymentInfo($quote);

        // set initial data for the request
        parent::getHeidelpayUrl($quote);

        // add salutation and birthdate to the request
        if (isset($paymentInfo->getAdditionalData()->hgw_salutation)) {
            $this->_heidelpayPaymentMethod->getRequest()->getName()
                ->set('salutation', $paymentInfo->getAdditionalData()->hgw_salutation);
        }

        if (isset($paymentInfo->getAdditionalData()->hgw_birthdate)) {
            $this->_heidelpayPaymentMethod->getRequest()->getName()
                ->set('birthdate', $paymentInfo->getAdditionalData()->hgw_birthdate);
        }

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
     * Determines if the payment method will be displayed at the checkout.
     * For B2C methods, the payment method should not be displayed.
     *
     * Else, refer to the parent isActive method.
     *
     * @inheritdoc
     */
    public function isAvailable(CartInterface $quote = null)
    {
        // in B2C payment methods, we don't want companies to be involved.
        // so, if the address contains a company, return false.
        if ($quote !== null && !empty($quote->getBillingAddress()->getCompany())) {
            return false;
        }

        // process the parent isAvailable method
        return parent::isAvailable($quote);
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function pendingTransactionProcessing($data, &$order, $message = null)
    {
        $payment = $order->getPayment();
        $payment->setTransactionId($data['IDENTIFICATION_UNIQUEID']);
        $payment->setIsTransactionClosed(false);
        $payment->addTransaction(Transaction::TYPE_AUTH, null, true);

        $order->setState(Order::STATE_PROCESSING)
            ->addStatusHistoryComment($message, Order::STATE_PROCESSING)
            ->setIsCustomerNotified(true);

        // payment is pending at the beginning, so we set the total paid sum to 0.
        $order->setTotalPaid(0.00)->setBaseTotalPaid(0.00);

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
