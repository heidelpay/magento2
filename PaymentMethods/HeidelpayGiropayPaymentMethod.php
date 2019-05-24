<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\PhpPaymentApi\PaymentMethods\GiropayPaymentMethod;

/**
 * heidelpay giropay Payment Method
 *
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
class HeidelpayGiropayPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     * @var string PayentCode
     */
    const CODE = 'hgwgp';

    /**
     * @var string heidelpay gateway payment code
     */
    protected $_code = self::CODE;

    /** @var bool */
    protected $_canAuthorize = true;

    /** @var boolean */
    protected $_canRefund = true;

    /** @var boolean */
    protected $_canRefundInvoicePartial = true;

    /** @var GiropayPaymentMethod */
    protected $_heidelpayPaymentMethod;

    /**
     * @inheritdoc
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
