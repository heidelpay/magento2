<?php

namespace Heidelpay\Gateway\Model;

use Heidelpay\Gateway\Api\Data\PaymentInformationInterface as HeidelpayPaymentInformationInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Api\Data\StoreInterface as MageApiDataStoreInterface;

/**
 * PaymentInformation
 *
 * The heidelpay payment information model.
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
class PaymentInformation extends AbstractModel implements HeidelpayPaymentInformationInterface
{
    /** @var \Magento\Store\Model\StoreFactory */
    protected $storeFactory;

    /** @var \Magento\Framework\Encryption\EncryptorInterface */
    protected $encryptor;

    /**
     * PaymentInformation constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation|null $resource
     * @param \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\Collection|null $resourceCollection
     * @param \Magento\Store\Model\StoreFactory $storeFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation $resource = null,
        \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\Collection $resourceCollection = null,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->storeFactory = $storeFactory;
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
        return $this->getData('id');
    }

    /**
     * @inheritdoc
     */
    public function getStore()
    {
        return $this->storeFactory->create()->load($this->getData('storeid'));
    }

    /**
     * @inheritdoc
     */
    public function setStore($store)
    {
        $id = null;
        if ($store instanceof MageApiDataStoreInterface) {
            $id = $store->getId();
        } elseif (is_numeric($store)) {
            $id = $store;
        }

        $this->setData('storeid', $id);
        return $this;
    }

    public function getCustomerEmail()
    {
        return $this->getData('customer_email');
    }

    /**
     * @inheritdoc
     */
    public function setCustomerEmail($email)
    {
        $this->setData('customer_email', $email);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethod()
    {
        return $this->getData('paymentmethod');
    }

    /**
     * @inheritdoc
     */
    public function setPaymentMethod($method)
    {
        $this->setData('paymentmethod', $method);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getShippingHash()
    {
        return $this->getData('shipping_hash');
    }

    /**
     * @inheritdoc
     */
    public function setShippingHash($shippingHash)
    {
        $this->setData('shipping_hash', $shippingHash);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalData()
    {
        return json_decode(
            $this->encryptor->decrypt(
                $this->getData('additional_data')
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function setAdditionalData($additionalData)
    {
        $this->setData(
            'additional_data',
            $this->encryptor->encrypt(json_encode($additionalData))
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHeidelpayPaymentReference()
    {
        return $this->getData('heidelpay_payment_reference');
    }

    /**
     * @inheritdoc
     */
    public function setHeidelpayPaymentReference($reference)
    {
        $this->setData('heidelpay_payment_reference', $reference);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreateDate()
    {
        return $this->getData('create_date');
    }
}
