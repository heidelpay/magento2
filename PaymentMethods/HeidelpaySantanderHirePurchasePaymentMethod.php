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

use Heidelpay\Gateway\Model\PaymentInformation;
use Heidelpay\Gateway\Wrapper\CustomerWrapper;
use Heidelpay\PhpPaymentApi\PaymentMethods\SantanderHirePurchasePaymentMethod;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\Data\CartInterface;

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
        $this->_canRefund               = true;
        $this->_canRefundInvoicePartial = true;
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
        if ($quote !== null && !empty($quote->getBillingAddress()->getCompany())) {
            return false;
        }

        // process the parent isAvailable method
        return parent::isAvailable($quote);
    }

    /**
     * Initial Request to heidelpay payment server to get the form url
     * {@inheritDoc}
     *
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote)
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

        $objectManager = ObjectManager::getInstance();
        /** @var CustomerWrapper $customer */
        $customer = $objectManager->create(CustomerWrapper::class)->setCustomer($quote->getCustomer());
        $request->getRiskInformation()
                ->setCustomerGuestCheckout($customer->isGuest() ? 'TRUE' : 'FALSE')
                ->setCustomerOrderCount($customer->numberOfOrders())
                ->setCustomerSince($customer->customerSince());

        // send the authorize request
        $this->_heidelpayPaymentMethod->initialize();

        return $this->_heidelpayPaymentMethod->getResponse();
    }
}
