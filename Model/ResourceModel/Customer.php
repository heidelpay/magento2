<?php

namespace Heidelpay\Gateway\Model\ResourceModel\Customer;

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
class Customer extends AbstractDb
{
    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init('heidelpay_customer', 'id');
    }
}
