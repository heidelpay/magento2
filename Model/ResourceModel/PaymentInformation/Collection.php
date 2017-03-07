<?php

namespace Heidelpay\Gateway\Model\ResourceModel\PaymentInformation;

use Heidelpay\Gateway\Model\PaymentInformation;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation as PaymentInformationResource;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Summary
 *
 * Desc
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
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Framework\DataObject
     */
    public function loadByCustomerInformation($quote)
    {
        return $this->addStoreFilter($quote->getStoreId())
            ->addCustomerEmailFilter($quote->getCustomer()->getEmail())
            ->addPaymentMethodFilter($quote->getPayment()->getMethod())
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
