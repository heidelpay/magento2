<?php
namespace Heidelpay\Gateway\Model\Transaction;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	    public function _construct()
        {
            $this->_init('Heidelpay\Gateway\Model\Transaction', 'Heidelpay\Gateway\Model\ResourceModel\Transaction');
            parent::_construct();
        }
}

