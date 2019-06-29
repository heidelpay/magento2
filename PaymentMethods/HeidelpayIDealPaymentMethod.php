<?php
/**
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
namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Model\PaymentInformation;
use Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException;
use Heidelpay\PhpPaymentApi\PaymentMethods\IDealPaymentMethod;
use Heidelpay\PhpPaymentApi\Response;

class HeidelpayIDealPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    const CODE = 'hgwidl';

    /** @var IDealPaymentMethod $_heidelpayPaymentMethod*/
    protected $_heidelpayPaymentMethod;

    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();
        $this->_canAuthorize = true;
    }

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

    /**
     * Send an authorize request to get a response which contains list of available banks.
     *
     * @return Response
     *
     * @throws UndefinedTransactionModeException
     */
    public function initMethod()
    {
        $this->setupInitialRequest();
        return $this->_heidelpayPaymentMethod->authorize()->getResponse();
    }
}
