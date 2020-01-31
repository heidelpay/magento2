<?php

namespace Heidelpay\Gateway\Model\ResourceModel\Order\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Zend_Db_Expr;

/**
 * Collection fetching the fields for sales_order_grid and adding quoteId column.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/magento2
 *
 * @author  Simon Gabriel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */

class Collection extends SearchResult
{
    protected function _initSelect()
    {
        parent::_initSelect();
        $subQuery = new Zend_Db_Expr(
            '(SELECT sales_order.quote_id,sales_order.entity_id FROM `sales_order_grid` AS `mt` ' .
            'INNER JOIN `sales_order` ON mt.entity_id = sales_order.entity_id where mt.payment_method like "hgw%")'
        );

        $this->getSelect()->joinLeft(['t' => $subQuery], 'main_table.entity_id = t.entity_id', array('quote_id'));

        return $this;
    }
}
