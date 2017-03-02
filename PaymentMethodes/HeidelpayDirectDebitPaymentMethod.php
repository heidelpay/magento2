<?php

namespace Heidelpay\Gateway\PaymentMethodes;

use Heidelpay\PhpApi\PaymentMethodes\DirectDebitPaymentMethod as HeidelpayPhpApiDirectDebit;

/**
 * Heidelpay Direct Debit
 *
 * The heidelpay Direct Debit payment method.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class HeidelpayDirectDebitPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string heidelpay Gateway Paymentcode */
    protected $_code = 'hgwdd';

    /** @var bool */
    protected $_canAuthorize = true;

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return mixed
     */
    public function getHeidelpayUrl($quote)
    {
        // create the collection factory
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load the payment information by store id, customer email address and payment method
        /** @var \Heidelpay\Gateway\Model\PaymentInformation $paymentInfo */
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation($quote);

        // initialize the Direct Debit payment method
        $this->_heidelpayPaymentMethod = new HeidelpayPhpApiDirectDebit();

        // make an initial request to the heidelpay payment.
        parent::getHeidelpayUrl($quote);

        // Force PhpApi to just generate the request instead of sending it directly
        $this->_heidelpayPaymentMethod->_dryRun = true;

        // add IBAN and Bank account owner to the request.
        $this->_heidelpayPaymentMethod
            ->getRequest()->getAccount()
            ->set('iban', $paymentInfo->getAdditionalData()->hgw_iban)
            ->set('holder', $paymentInfo->getAdditionalData()->hgw_holder);

        // Set payment type to debit
        $this->_heidelpayPaymentMethod->debit();

        // Prepare and send request to heidelpay
        $request = $this->_heidelpayPaymentMethod->getRequest()->prepareRequest();
        $response = $this->_heidelpayPaymentMethod
            ->getRequest()
            ->send($this->_heidelpayPaymentMethod->getPaymentUrl(), $request);

        return $response[0];
    }

    /**
     * Prints out a message with information for
     * the customer about the direct debit.
     *
     * @param array $Response
     * @return \Magento\Framework\Phrase
     */
    public function additionalPaymentInformation($Response)
    {
        return __(
            'The amount of <strong>%1 %2</strong> will be debited from this account within the next days:'
            . '<br /><br />IBAN: %3<br /><br /><i>The booking contains the mandate reference ID: %4'
            . '<br >and the creditor identifier: %5</i><br /><br />'
            . 'Please ensure that there will be sufficient funds on the corresponding account.',
            $Response['PRESENTATION_AMOUNT'],
            $Response['PRESENTATION_CURRENCY'],
            $Response['ACCOUNT_IBAN'],
            $Response['ACCOUNT_IDENTIFICATION'],
            $Response['IDENTIFICATION_CREDITOR_ID']
        );
    }
}
