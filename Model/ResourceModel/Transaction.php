<?php
namespace Heidelpay\Gateway\Model\ResourceModel;

class Transaction extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('heidelpay_transaction', 'id');
    }
    
    public function loadLastTransactionByQuoteId(\Magento\Framework\Model\AbstractModel $object, $value, $field = null)
    {
        if ($field === null) {
            $field = $this->getIdFieldName();
        }
    
        $connection = $this->getConnection();
        if ($connection && $value !== null) {
            $select = $this->_getLoadLastSelect($field, $value, $object);
            $data = $connection->fetchRow($select);
    
            if ($data) {
                $object->setData($data);
            }
        }
    
        $this->unserializeFields($object);
        $this->_afterLoad($object);
    
        return $this;
    }
    
    protected function _getLoadLastSelect($field, $value, $object)
    {
        $field = $this->getConnection()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), $field));
        $select = $this->getConnection()->select()->from($this->getMainTable())->where($field . '=?', $value)->limit(1)->order('datetime DESC');
        return $select;
    }
}
