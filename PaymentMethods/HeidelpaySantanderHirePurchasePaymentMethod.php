<?php
/**
 * This is the payment class for heidelpay santander hire purchase payment method.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/magento2
 *
 * @author Simon Gabriel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
namespace Heidelpay\Gateway\PaymentMethods;

use Exception;
use Heidelpay\Gateway\Model\PaymentInformation;
use Heidelpay\Gateway\Wrapper\CustomerWrapper;
use Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException;
use Heidelpay\PhpPaymentApi\PaymentMethods\SantanderHirePurchasePaymentMethod;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;

/** @noinspection LongInheritanceChainInspection */
/**
 * @property SantanderHirePurchasePaymentMethod $_heidelpayPaymentMethod
 */
class HeidelpaySantanderHirePurchasePaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string PaymentCode */
    const CODE = 'hgwsanhp';

    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();
        $this->_canAuthorize            = true;
        $this->_usingBasket             = true;
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
        if ($quote !== null && $quote->getBillingAddress() === null && !empty($quote->getBillingAddress()->getCompany())) {
            return false;
        }

        // process the parent isAvailable method
        return parent::isAvailable($quote);
    }

    /**
     * Initial Request to heidelpay payment server to get the form url
     * {@inheritDoc}
     *
     * @throws UndefinedTransactionModeException
     * @throws Exception
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote, array $data = [])
    {
        // create the collection factory
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load the payment information by store id, customer email address and payment method
        /** @var PaymentInformation $paymentInfo */
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
            $quote->getStoreId(),
            $quote->getBillingAddress()->getEmail(),
            $quote->getPayment()->getMethod()
        );

        // set initial data for the request
        parent::getHeidelpayUrl($quote);

        // add salutation and birthdate to the request
        $request = $this->_heidelpayPaymentMethod->getRequest();
        if (isset($paymentInfo->getAdditionalData()->hgw_salutation)) {
            $request->getName()->set('salutation', $paymentInfo->getAdditionalData()->hgw_salutation);
        }

        if (isset($paymentInfo->getAdditionalData()->hgw_birthdate)) {
            $request->getName()->set('birthdate', $paymentInfo->getAdditionalData()->hgw_birthdate);
        }

        // set risk information
        $objectManager = ObjectManager::getInstance();
        /** @var CustomerWrapper $customer */
        $customer = $objectManager->create(CustomerWrapper::class)->setCustomer($quote->getCustomer());
        $request->getRiskInformation()
            ->setCustomerGuestCheckout($customer->isGuest() ? 'TRUE' : 'FALSE')
            ->setCustomerOrderCount($customer->numberOfOrders())
            ->setCustomerSince($customer->customerSince());

        if (isset($data['referenceId']) && !empty($data['referenceId'])) {
            $this->_heidelpayPaymentMethod->authorizeOnRegistration($data['referenceId']);
        } else {
            $this->_heidelpayPaymentMethod->initialize();
        }

        return $this->_heidelpayPaymentMethod->getResponse();
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
