<?php

namespace Heidelpay\Gateway\Model\ResourceModel\Transaction;

/**
 * Collection class for the Transactions
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/magento2
 *
 * @author Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('Heidelpay\Gateway\Model\Transaction', 'Heidelpay\Gateway\Model\ResourceModel\Transaction');
        parent::_construct();
    }

    /**
     * @param $quoteId
     *
     * @return \Magento\Framework\DataObject
     */
    public function loadByQuoteId($quoteId)
    {
        return $this->addQuoteIdFilter($quoteId)->load()->getLastItem();
    }

    /**
     * @param $transactionId
     *
     * @return \Magento\Framework\DataObject
     */
    public function loadByTransactionId($transactionId)
    {
        return $this->addTransactionIdFilter($transactionId)->load()->getLastItem();
    }

    /**
     * Adds a filter for the quote id
     *
     * @param string $quoteId
     *
     * @return $this
     */
    public function addQuoteIdFilter($quoteId)
    {
        $this->addFieldToFilter('transactionid', $quoteId);
        return $this;
    }

    /**
     * Adds a filter for unique transaction id.
     *
     * @param string $transactionId
     *
     * @return $this
     */
    public function addTransactionIdFilter($transactionId)
    {
        $this->addFieldToFilter('uniqeid', $transactionId);
        return $this;
    }

    /**
     * Adds a filter for unique transaction id.
     *
     * @param string|array $paymentType
     *
     * @return $this
     */
    public function addPaymentTypeFilter($paymentType)
    {
        $this->addFieldToFilter('payment_type', $paymentType);
        return $this;
    }
}
