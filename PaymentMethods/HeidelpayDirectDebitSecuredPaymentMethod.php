<?php
/**
 * The heidelpay Direct Debit payment method.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link http://dev.heidelpay.com/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Model\PaymentInformation;
use Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException;
use Heidelpay\PhpPaymentApi\PaymentMethods\DirectDebitB2CSecuredPaymentMethod;
use Magento\Quote\Api\Data\CartInterface;

/** @noinspection LongInheritanceChainInspection */
/**
 * @property DirectDebitB2CSecuredPaymentMethod $_heidelpayPaymentMethod
 */
class HeidelpayDirectDebitSecuredPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string PaymentCode */
    const CODE = 'hgwdds';

    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();
        $this->_canAuthorize = true;
        $this->_canRefund = true;
        $this->_canRefundInvoicePartial = true;
        $this->useShippingAddressAsBillingAddress   = true;
    }

    /**
     * @inheritdoc
     *
     * @throws UndefinedTransactionModeException
     */
    public function getHeidelpayUrl($quote, array $data = [])
    {
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load the payment information by store id, customer email address and payment method
        /** @var PaymentInformation $paymentInfo */
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
            $quote->getStoreId(),
            $quote->getBillingAddress()->getEmail(),
            $quote->getPayment()->getMethod()
        );

        // make an initial request to the heidelpay payment.
        parent::getHeidelpayUrl($quote);

        // add IBAN and Bank account owner to the request.
        if (isset($paymentInfo->getAdditionalData()->hgw_iban)) {
            $this->_heidelpayPaymentMethod->getRequest()->getAccount()
                ->set('iban', $paymentInfo->getAdditionalData()->hgw_iban);
        }

        if (isset($paymentInfo->getAdditionalData()->hgw_holder)) {
            $this->_heidelpayPaymentMethod->getRequest()->getAccount()
                ->set('holder', $paymentInfo->getAdditionalData()->hgw_holder);
        }

        // add salutation and date of birth to the request
        if (isset($paymentInfo->getAdditionalData()->hgw_salutation)) {
            $this->_heidelpayPaymentMethod->getRequest()->getName()
                ->set('salutation', $paymentInfo->getAdditionalData()->hgw_salutation);
        }

        if (isset($paymentInfo->getAdditionalData()->hgw_birthdate)) {
            $this->_heidelpayPaymentMethod->getRequest()->getName()
                ->set('birthdate', $paymentInfo->getAdditionalData()->hgw_birthdate);
        }

        // Set payment type to debit
        $this->_heidelpayPaymentMethod->debit();

        return $this->_heidelpayPaymentMethod->getResponse();
    }

    /**
     * @inheritdoc
     */
    public function additionalPaymentInformation($response)
    {
        return __(
            'The amount of <strong>%1 %2</strong> will be debited from this account within the next days:'
            . '<br /><br />IBAN: %3<br /><br /><i>The booking contains the mandate reference ID: %4'
            . '<br >and the creditor identifier: %5</i><br /><br />'
            . 'Please ensure that there will be sufficient funds on the corresponding account.',
            $this->_paymentHelper->format($response['PRESENTATION_AMOUNT']),
            $response['PRESENTATION_CURRENCY'],
            $response['ACCOUNT_IBAN'],
            $response['ACCOUNT_IDENTIFICATION'],
            $response['IDENTIFICATION_CREDITOR_ID']
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
}
