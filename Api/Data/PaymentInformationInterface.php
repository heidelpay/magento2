<?php

namespace Heidelpay\Gateway\Api\Data;

/**
 * Model for heidelpay Payment Information
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
interface PaymentInformationInterface
{
    /** @const string The Unique Id column */
    const PAYMENTINFORMATION_ID = 'id';

    /** @const string The Store Id column */
    const STORE_ID = 'storeid';

    /** @const string The Customer/Guest's e-mail address column */
    const CUSTOMER_EMAIL = 'customer_email';

    /** @const string The heidelpay payment method column */
    const PAYMENT_METHOD = 'paymentmethod';

    /** @const string The shipping hash column */
    const SHIPPING_HASH = 'shipping_hash';

    /** @const string The additional payment data column */
    const ADDITIONAL_DATA = 'additional_data';

    /** @const string The heidelpay payment reference column */
    const PAYMENT_REFERENCE = 'heidelpay_payment_reference';

    /** @const The creation date column */
    const CREATE_DATE = 'create_date';

    /**
     * Returns the id.
     *
     * @return integer
     * @api
     */
    public function getId();

    /**
     * Returns the store id.
     *
     * @return integer
     * @api
     */
    public function getStore();

    /**
     * Returns the customer/guest e-mail address.
     *
     * @return string
     * @api
     */
    public function getCustomerEmail();

    /**
     * Returns the connected heidelpay payment method.
     *
     * @return string
     * @api
     */
    public function getPaymentMethod();

    /**
     * Returns the Shipping Hash.
     *
     * @return string
     * @api
     */
    public function getShippingHash();

    /**
     * Returns the additional payment data.
     *
     * @return mixed
     * @api
     */
    public function getAdditionalData();

    /**
     * Returns the unique heidelpay payment.
     *
     * @return string
     * @api
     */
    public function getHeidelpayPaymentReference();

    /**
     * Returns the create date of the data set.
     *
     * @return string
     * @api
     */
    public function getCreateDate();

    /**
     * Sets the store id.
     *
     * @param integer $storeId
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     * @api
     */
    public function setStoreId($storeId);

    /**
     * Sets the customer/guest e-mail address.
     *
     * @param string $email
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     * @api
     */
    public function setCustomerEmail($email);

    /**
     * Sets the heidelpay payment method.
     *
     * @param string $method
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     * @api
     */
    public function setPaymentMethod($method);

    /**
     * Sets the shipping hash.
     *
     * @param string $shippingHash
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     * @api
     */
    public function setShippingHash($shippingHash);

    /**
     * Sets additional payment data.
     *
     * @param $additionalData
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     * @api
     */
    public function setAdditionalData($additionalData);

    /**
     * Sets the heidelpay payment reference.
     *
     * @param string $reference
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     * @api
     */
    public function setHeidelpayPaymentReference($reference);

    /**
     * Loads the data set by id.
     *
     * @param integer $id
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     * @api
     */
    public function load($id);

    /**
     * Saves the model.
     *
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     * @api
     */
    public function save();
}
