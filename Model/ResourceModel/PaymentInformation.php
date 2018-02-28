<?php

namespace Heidelpay\Gateway\Model\ResourceModel;

/**
 * heidelpay PaymentInformation Resource Model
 *
 * The resource model for the heidelpay payment information table.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class PaymentInformation extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize the payment information model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('heidelpay_payment_information', 'id');
    }
}
