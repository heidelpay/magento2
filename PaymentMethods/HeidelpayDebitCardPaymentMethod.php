<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\PhpPaymentApi\PaymentMethods\DebitCardPaymentMethod;
use Heidelpay\Gateway\Model\Config\Source\BookingMode;

/**
 * Heidelpay debit card payment method
 *
 * This is the payment class for heidelpay debit card
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
class HeidelpayDebitCardPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     * @var string PaymentCode
     */
    const CODE = 'hgwdc';

    /**
     * Payment Code
     * @var string PaymentCode
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

    /** @var boolean */
    protected $_canCapture = true;

    /** @var boolean */
    protected $_canCapturePartial = true;

    /** @var boolean */
    protected $_canRefund = true;

    /** @var boolean */
    protected $_canRefundInvoicePartial = true;

    /** @var DebitCardPaymentMethod */
    protected $_heidelpayPaymentMethod;

    /**
     * Active redirect
     *
     * This function will return false, if the used payment method needs additional
     * customer payment data to pursue.
     *
     * @return boolean
     */
    public function activeRedirect()
    {
        return false;
    }

    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     *
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote)
    {
        // set initial data for the request
        parent::getHeidelpayUrl($quote);

        $url = explode('/', $this->urlBuilder->getUrl('/', ['_secure' => true]));
        $paymentFrameOrigin = $url[0] . '//' . $url[2];
        $preventAsyncRedirect = 'FALSE';
        $cssPath = $this->mainConfig->getDefaultCss();

        // make an authorize request, if set...
        if ($this->getBookingMode() === BookingMode::AUTHORIZATION) {
            $this->_heidelpayPaymentMethod->authorize($paymentFrameOrigin, $preventAsyncRedirect, $cssPath);
        }

        // ... else if no booking mode is set or bookingmode is set to 'debit', make a debit request.
        if ($this->getBookingMode() === null || $this->getBookingMode() === BookingMode::DEBIT) {
            $this->_heidelpayPaymentMethod->debit($paymentFrameOrigin, $preventAsyncRedirect, $cssPath);
        }

        return $this->_heidelpayPaymentMethod->getResponse();
    }
}
