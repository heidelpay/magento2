<?php
/**
 * This is the payment class for heidelpay PayPal.
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
use Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException;
use Heidelpay\PhpPaymentApi\PaymentMethods\PayPalPaymentMethod;

/** @noinspection LongInheritanceChainInspection */
/**
 * @property PayPalPaymentMethod $_heidelpayPaymentMethod
 */
class HeidelpayPayPalPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string PaymentCode */
    const CODE = 'hgwpal';

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
        $this->useShippingAddressOnly   = true;
    }

    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     * @throws UndefinedTransactionModeException
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote, array $data = [])
    {
        // set initial data for the request
        parent::getHeidelpayUrl($quote);
        $bookingMode = $this->getBookingMode();

        // make an authorize request, if set...
        if ($bookingMode === BookingMode::AUTHORIZATION) {
            $this->_heidelpayPaymentMethod->authorize();
        }

        // ... else if no booking mode is set or bookingmode is set to 'debit', make a debit request.
        if ($bookingMode === null || $bookingMode === BookingMode::DEBIT) {
            $this->_heidelpayPaymentMethod->debit();
        }

        return $this->_heidelpayPaymentMethod->getResponse();
    }
}
