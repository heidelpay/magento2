<?php
/**
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
namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Model\Config\Source\BookingMode;
use Heidelpay\PhpPaymentApi\PaymentMethods\DebitCardPaymentMethod;

class HeidelpayDebitCardPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string PaymentCode */
    const CODE = 'hgwdc';

    /** @var DebitCardPaymentMethod */
    protected $_heidelpayPaymentMethod;

    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();
        $this->_canCapture = true;
        $this->_canAuthorize = true;
        $this->_canCapturePartial = true;
        $this->_canRefund = true;
        $this->_canRefundInvoicePartial = true;
        $this->_usingActiveRedirect = false;
    }

    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     *
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote, array $data = [])
    {
        // set initial data for the request
        parent::getHeidelpayUrl($quote);

        $url = explode('/', $this->urlBuilder->getUrl('/', ['_secure' => true]));
        $paymentFrameOrigin = $url[0] . '//' . $url[2];
        $preventAsyncRedirect = 'FALSE';
        $cssPath = $this->mainConfig->getDefaultCss();
        $bookingMode = $this->getBookingMode();

        // make an authorize request, if set...
        if ($bookingMode === BookingMode::AUTHORIZATION) {
            $this->_heidelpayPaymentMethod->authorize($paymentFrameOrigin, $preventAsyncRedirect, $cssPath);
        }

        // ... else if no booking mode is set or bookingmode is set to 'debit', make a debit request.
        if ($bookingMode === null || $bookingMode === BookingMode::DEBIT) {
            $this->_heidelpayPaymentMethod->debit($paymentFrameOrigin, $preventAsyncRedirect, $cssPath);
        }

        return $this->_heidelpayPaymentMethod->getResponse();
    }
}
