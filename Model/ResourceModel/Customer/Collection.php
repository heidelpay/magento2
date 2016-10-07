<?php
namespace Heidelpay\Gateway\Model\Customer;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	    public function _construct()
        {
            $this->_init('heidelpay_customer');
            parent::_construct();
        }
}

