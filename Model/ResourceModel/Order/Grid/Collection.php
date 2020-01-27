<?php
namespace Heidelpay\Gateway\Model\ResourceModel\Order\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->join(
            [$this->getTable('sales_order')],
            'main_table.entity_id = '.$this->getTable('sales_order').'.entity_id',
            array('quote_id')
        );

        return $this;
    }
}