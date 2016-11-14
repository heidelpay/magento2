<?php

namespace Heidelpay\Gateway\PaymentMethodes;

use \Heidelpay\PhpApi\PaymentMethodes\SofortPaymentMethod as HeidelpayPhpApiSofort;
use \Heidelpay\Gateway\PaymentMethodes\HeidelpayAbstractPaymentMethod;

/**
 * heidelpay sofort payment method
 *
 * This is the payment class for heidelpay sofort
 *
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link  https://dev.heidelpay.de/magento
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
class HeidelpaySofortPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     * @var string PayentCode
     */
    const CODE = 'hgwsue';

    /**
     * Payment Code
     * @var string PayentCode
     */
    protected $_code = 'hgwsue';

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

    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     * @see \Heidelpay\Gateway\PaymentMethodes\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote)
    {
        $this->_heidelpayPaymentMethod = new HeidelpayPhpApiSofort();

        parent::getHeidelpayUrl($quote);

        /** Force PhpApi to just generate the request instead of sending it directly */
        $this->_heidelpayPaymentMethod->_dryRun = true;

        /** Set payment type to debit */
        $this->_heidelpayPaymentMethod->debit();

        /** Prepare and send request to heidelpay */
        $request = $this->_heidelpayPaymentMethod->getRequest()->prepareRequest();
        $response = $this->_heidelpayPaymentMethod->getRequest()->send($this->_heidelpayPaymentMethod->getPaymentUrl(), $request);

        return $response[0];
    }
}