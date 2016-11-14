<?php

namespace Heidelpay\Gateway\PaymentMethodes;

use \Heidelpay\PhpApi\PaymentMethodes\PayPalPaymentMethod as HeidelpayPhpApiPayPal;
use \Heidelpay\Gateway\PaymentMethodes\HeidelpayAbstractPaymentMethod;

/**
 * heidelpay PayPal payment method
 *
 * This is the payment class for heidelpay PayPal
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
class HeidelpayPayPalPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     * @var string PayentCode
     */
    const CODE = 'hgwpal';

    /**
     * Payment Code
     * @var string PayentCode
     */
    protected $_code = 'hgwpal';

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

        $this->_heidelpayPaymentMethod = new HeidelpayPhpApiPayPal();

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