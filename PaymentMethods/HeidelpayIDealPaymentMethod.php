<?php /** @noinspection ClassOverridesFieldOfSuperClassInspection */

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\PhpPaymentApi\PaymentMethods\IDealPaymentMethod;
use Heidelpay\PhpPaymentApi\Response;

/**
 * Heidelpay iDeal payment method
 *
 * This is the payment class for heidelpay iDeal
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link http://dev.heidelpay.com/magento2
 * @author David Owusu
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class HeidelpayIDealPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    const CODE = 'hgwidl';

    protected $_code = self::CODE;

    protected $_canAuthorize = true;

    protected $_isGateway = true;

    /** @var IDealPaymentMethod */
    protected $_heidelpayPaymentMethod;

    /**
     * @inheritdoc
     * Prepare necessary information for bank selection
     */
    public function prepareAdditionalFormData(Response $response)
    {
        $brands = $response->getConfig()->getBrands();
        $bankList = [];

        /**
         * build array object for javascript frontend
         */
        foreach ($brands as $brandValue => $brandName) {
            $bank = [];
            $bank['value'] = $brandValue;
            $bank['name'] = $brandName;

            $bankList[] = $bank;
        }

        if (empty($bankList)) {
            $this->_logger->warning('heidelpay - iDeal config: brand list is empty');
        }

        return $bankList;
    }

    public function getHeidelpayUrl($quote)
    {
        // create the collection factory
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load the payment information by store id, customer email address and payment method
        /** @var \Heidelpay\Gateway\Model\PaymentInformation $paymentInfo */
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
            $quote->getStoreId(),
            $quote->getBillingAddress()->getEmail(),
            $quote->getPayment()->getMethod()
        );

        // set some parameters inside the Abstract Payment method helper which are used for all requests,
        // e.g. authentication, customer data, ...
        parent::getHeidelpayUrl($quote);

        // add Bank selection to the request.
        if (isset($paymentInfo->getAdditionalData()->hgw_bank_name)) {
            $this->_heidelpayPaymentMethod
                ->getRequest()->getAccount()
                ->setBankName($paymentInfo->getAdditionalData()->hgw_bank_name);
        }

        if (isset($paymentInfo->getAdditionalData()->hgw_holder)) {
            $this->_heidelpayPaymentMethod
                ->getRequest()->getAccount()
                ->setHolder($paymentInfo->getAdditionalData()->hgw_holder);
        }

        // send the init request with the authorize method.
        $this->_heidelpayPaymentMethod->authorize();

        // return the response object
        return $this->_heidelpayPaymentMethod->getResponse();
    }

    public function activeRedirect()
    {
        return true;
    }

    /*
     * Send an authorize request to get a response which contains list of available banks.
     */
    public function initMethod()
    {
        $this->setupInitialRequest();
        return $this->_heidelpayPaymentMethod->authorize()->getResponse();
    }
}
