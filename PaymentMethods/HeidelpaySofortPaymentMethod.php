<?php
/**
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
namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException;
use Heidelpay\PhpPaymentApi\PaymentMethods\SofortPaymentMethod;

/** @noinspection LongInheritanceChainInspection */
/**
 * @property SofortPaymentMethod $_heidelpayPaymentMethod
 */
class HeidelpaySofortPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string PaymentCode */
    const CODE = 'hgwsue';

    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();
        $this->_canAuthorize = true;
        $this->_canRefund = true;
        $this->_canRefundInvoicePartial = true;
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

        // send the authorize request
        $this->_heidelpayPaymentMethod->authorize();

        return $this->_heidelpayPaymentMethod->getResponse();
    }
}
