<?php

namespace Heidelpay\Gateway\Model\ResourceModel\Transaction;

/**
 * Collection class for the Transactions
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento2
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
     * Adds a filter for the customer/guest e-mail address
     *
     * @param string $quoteId
     *
     * @return $this
     */
    private function addQuoteIdFilter($quoteId)
    {
        $this->addFieldToFilter('transactionid', $quoteId);
        return $this;
    }

    private function addTransactionIdFilter($transactionId)
    {
        $this->addFieldToFilter('uniqeid', $transactionId);
        return $this;
    }
}
