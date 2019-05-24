<?php
/**
 * This is the payment class for heidelpay santander hire purchase payment method.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/magento2
 *
 * @author Simon Gabriel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\PhpPaymentApi\PaymentMethods\SantanderHirePurchasePaymentMethod;
use Magento\Quote\Api\Data\CartInterface;

class HeidelpaySantanderHirePurchasePaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string PaymentCode */
    const CODE = 'hgwsanhp';

    /** @var string PaymentCode */
    protected $_code = self::CODE;

    /** @var boolean $_isGateway */
    protected $_isGateway = true;

    /** @var boolean $_canAuthorize */
    protected $_canAuthorize = true;

    /** @var boolean $_canRefund */
    protected $_canRefund = true;

    /** @var boolean $_canRefundInvoicePartial */
    protected $_canRefundInvoicePartial = true;

    /** @var SantanderHirePurchasePaymentMethod */
    protected $_heidelpayPaymentMethod;

    /**
     * Determines if the payment method will be displayed at the checkout.
     * For B2C methods, the payment method should not be displayed.
     *
     * Else, refer to the parent isActive method.
     *
     * @inheritdoc
     */
    public function isAvailable(CartInterface $quote = null)
    {
        // in B2C payment methods, we don't want companies to be involved.
        // so, if the address contains a company, return false.
        if ($quote !== null && !empty($quote->getBillingAddress()->getCompany())) {
            return false;
        }

        // process the parent isAvailable method
        return parent::isAvailable($quote);
    }

    /**
     * Initial Request to heidelpay payment server to get the form url
     * {@inheritDoc}
     *
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote)
    {
        // set initial data for the request
        parent::getHeidelpayUrl($quote);

        // send the authorize request
        $this->_heidelpayPaymentMethod->initialize();

        return $this->_heidelpayPaymentMethod->getResponse();
    }
}
