<?php

namespace Heidelpay\Gateway\Api\Data;

/**
 * $Summary$
 *
 * $Desc$
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
    /**
     * Returns the id.
     *
     * @return integer
     * @api
     */
    public function getId();

    /**
     * Returns the store.
     *
     * @return \Magento\Store\Model\Store
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
     * Sets the store.
     *
     * @param \Magento\Store\Api\Data\StoreInterface|integer $store
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     * @api
     */
    public function setStore($store);

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
