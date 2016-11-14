<?php

namespace Heidelpay\Gateway\Model\ResourceModel;

use Magento\Framework\DB\Select;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link  https://dev.heidelpay.de/magento
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
class Transaction extends AbstractDb
{
    /**
     * @param AbstractModel $object
     * @param $value
     * @param null $field
     * @return $this
     */
    public function loadLastTransactionByQuoteId(AbstractModel $object, $value, $field = null)
    {
        if ($field === null) {
            $field = $this->getIdFieldName();
        }

        $connection = $this->getConnection();

        if ($connection && $value !== null) {
            $select = $this->_getLoadLastSelect($field, $value);
            $data = $connection->fetchRow($select);

            if ($data) {
                $object->setData($data);
            }
        }

        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('heidelpay_transaction', 'id');
    }

    /**
     * @param $field
     * @param $value
     * @return Select
     */
    protected function _getLoadLastSelect($field, $value)
    {
        $adapter = $this->getConnection();
        $mainTable = $this->getMainTable();
        $field = $adapter->quoteIdentifier(sprintf('%s.%s', $mainTable, $field));
        $selectStatement = $adapter->select();
        $select = $selectStatement->from($mainTable)->where($field . '=?', $value)->limit(1)->order('datetime DESC');

        return $select;
    }
}