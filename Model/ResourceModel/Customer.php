<?php
namespace Heidelpay\Gateway\Model\ResourceModel\Customer;

class Customer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	    public function _construct()
        {
            $this->_init('heidelpay_customer', 'id');
        }
}
