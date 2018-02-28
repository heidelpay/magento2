<?php

namespace Heidelpay\Gateway\Model;

use Heidelpay\Gateway\Api\Data\PaymentInformationInterface as HeidelpayPaymentInformationInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * PaymentInformation
 *
 * The heidelpay payment information model.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class PaymentInformation extends AbstractModel implements HeidelpayPaymentInformationInterface
{
    /** @var \Magento\Framework\Encryption\EncryptorInterface */
    protected $encryptor;

    /**
     * PaymentInformation constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation|null $resource
     * @param \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\Collection|null $resourceCollection
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation $resource = null,
        \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\Collection $resourceCollection = null,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->encryptor = $encryptor;
    }

    /**
     * @inheritdoc
     */
    public function _construct()
    {
        $this->_init('Heidelpay\Gateway\Model\ResourceModel\PaymentInformation');
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getData(self::PAYMENTINFORMATION_ID);
    }

    /**
     * @inheritdoc
     */
    public function getStore()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        $this->setData(self::STORE_ID, $storeId);

        return $this;
    }

    public function getCustomerEmail()
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerEmail($email)
    {
        $this->setData(self::CUSTOMER_EMAIL, $email);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethod()
    {
        return $this->getData(self::PAYMENT_METHOD);
    }

    /**
     * @inheritdoc
     */
    public function setPaymentMethod($method)
    {
        $this->setData(self::PAYMENT_METHOD, $method);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getShippingHash()
    {
        return $this->getData(self::SHIPPING_HASH);
    }

    /**
     * @inheritdoc
     */
    public function setShippingHash($shippingHash)
    {
        $this->setData(self::SHIPPING_HASH, $shippingHash);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalData()
    {
        return json_decode(
            $this->encryptor->decrypt(
                $this->getData(self::ADDITIONAL_DATA)
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function setAdditionalData($additionalData)
    {
        $this->setData(
            self::ADDITIONAL_DATA,
            $this->encryptor->encrypt(json_encode($additionalData))
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHeidelpayPaymentReference()
    {
        return $this->getData(self::PAYMENT_REFERENCE);
    }

    /**
     * @inheritdoc
     */
    public function setHeidelpayPaymentReference($reference)
    {
        $this->setData(self::PAYMENT_REFERENCE, $reference);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreateDate()
    {
        return $this->getData(self::CREATE_DATE);
    }
}
