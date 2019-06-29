<?php
/**
 * heidelpay Payment Method for giropay.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link http://dev.heidelpay.com/magento2
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\PhpPaymentApi\Exceptions\UndefinedTransactionModeException;
use Heidelpay\PhpPaymentApi\PaymentMethods\GiropayPaymentMethod;

/** @noinspection LongInheritanceChainInspection */
/**
 * @property GiropayPaymentMethod $_heidelpayPaymentMethod
 */
class HeidelpayGiropayPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string PaymentCode */
    const CODE = 'hgwgp';

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
     * @inheritdoc
     *
     * @throws UndefinedTransactionModeException
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
