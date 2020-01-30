<?php
namespace Heidelpay\Gateway\Model\ResourceModel\Order\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Zend_Db_Expr;

class Collection extends SearchResult
{
    protected function _initSelect()
    {
        parent::_initSelect();
        $subquery = new Zend_Db_Expr(
            '(SELECT sales_order.quote_id,sales_order.entity_id FROM `sales_order_grid` AS `mt` ' .
            'INNER JOIN `sales_order` ON mt.entity_id = sales_order.entity_id where mt.payment_method like "hgw%")'
        );

        $this->getSelect()->joinLeft(['t' => $subquery], 'main_table.entity_id = t.entity_id', array('quote_id'));

        return $this;
    }
}
