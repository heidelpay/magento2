<?php

namespace Heidelpay\Gateway\Model\ResourceModel\PaymentInformation;

use Heidelpay\Gateway\Model\PaymentInformation;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation as PaymentInformationResource;
use Magento\Store\Api\Data\StoreInterface;

/**
 * The Collection class for the Payment Information
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
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        // register the PaymentInformation Model and ResourceModel
        $this->_init(
            PaymentInformation::class,
            PaymentInformationResource::class
        );

        parent::_construct();
    }

    /**
     * Loads payment information by certain parameters.
     *
     * @param integer $storeId
     * @param string $customerEmail
     * @param string $paymentMethod
     * @return \Magento\Framework\DataObject
     */
    public function loadByCustomerInformation($storeId, $customerEmail, $paymentMethod)
    {
        return $this->addStoreFilter($storeId)
            ->addCustomerEmailFilter($customerEmail)
            ->addPaymentMethodFilter($paymentMethod)
            ->load()
            ->getLastItem();
    }

    /**
     * Adds a filter for the customer/guest e-mail address
     *
     * @param string $email
     * @return $this
     */
    private function addCustomerEmailFilter($email)
    {
        $this->addFieldToFilter('customer_email', $email);
        return $this;
    }

    /**
     * Adds a filter for the payment method.
     *
     * @param string $method
     * @return $this
     */
    private function addPaymentMethodFilter($method)
    {
        $this->addFieldToFilter('paymentmethod', $method);
        return $this;
    }

    /**
     * Adds a filter for the store id/instance
     *
     * @param \Magento\Store\Api\Data\StoreInterface|integer $store
     * @return $this
     */
    private function addStoreFilter($store)
    {
        $id = -1;
        if ($store instanceof StoreInterface) {
            $id = $store->getId();
        } elseif (is_numeric($store)) {
            $id = $store;
        }

        $this->addFieldToFilter('storeid', $id);
        return $this;
    }
}
