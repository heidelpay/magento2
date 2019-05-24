<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\PhpPaymentApi\PaymentMethods\SofortPaymentMethod;
/**
 * heidelpay sofort payment method
 *
 * This is the payment class for heidelpay sofort
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
    protected $_code = self::CODE;

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
     * @var boolean */
    protected $_canRefund = true;

    /** @var boolean */
    protected $_canRefundInvoicePartial = true;

    /** @var SofortPaymentMethod */
    protected $_heidelpayPaymentMethod;

    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote)
    {
        // set initial data for the request
        parent::getHeidelpayUrl($quote);

        // send the authorize request
        $this->_heidelpayPaymentMethod->authorize();

        return $this->_heidelpayPaymentMethod->getResponse();
    }
}
