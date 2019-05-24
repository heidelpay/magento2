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

use Exception;
use Heidelpay\Gateway\Model\PaymentInformation;
use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException;
use Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException;
use Heidelpay\PhpPaymentApi\PaymentMethods\DirectDebitPaymentMethod;
use Heidelpay\PhpPaymentApi\Response;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;

class HeidelpayDirectDebitPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string PaymentCode */
    const CODE = 'hgwdd';

    /** @var string heidelpay gateway payment code */
    protected $_code = self::CODE;

    /** @var bool */
    protected $_canAuthorize = true;

    /** @var boolean */
    protected $_canRefund = true;

    /** @var boolean */
    protected $_canRefundInvoicePartial = true;

    /** @var DirectDebitPaymentMethod */
    protected $_heidelpayPaymentMethod;

    /**
     * Fires the initial request to the heidelpay payment provider.
     *
     * @param Quote $quote
     *
     * @return Response
     * @throws Exception
     * @throws InvalidBasketitemPositionException
     * @throws UndefinedTransactionModeException
     * @throws LocalizedException
     */
    public function getHeidelpayUrl($quote)
    {
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load the payment information by store id, customer email address and payment method
        /** @var PaymentInformation $paymentInfo */
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
            $quote->getStoreId(),
            $quote->getBillingAddress()->getEmail(),
            $quote->getPayment()->getMethod()
        );

        // set some parameters inside the Abstract Payment method helper which are used for all requests,
        // e.g. authentication, customer data, ...
        parent::getHeidelpayUrl($quote);

        // add IBAN and Bank account owner to the request.
        if (isset($paymentInfo->getAdditionalData()->hgw_iban)) {
            $this->_heidelpayPaymentMethod
                ->getRequest()->getAccount()
                ->set('iban', $paymentInfo->getAdditionalData()->hgw_iban);
        }

        if (isset($paymentInfo->getAdditionalData()->hgw_holder)) {
            $this->_heidelpayPaymentMethod
                ->getRequest()->getAccount()
                ->set('holder', $paymentInfo->getAdditionalData()->hgw_holder);
        }

        // send the init request with the debit method.
        $this->_heidelpayPaymentMethod->debit();

        // return the response object
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
}
