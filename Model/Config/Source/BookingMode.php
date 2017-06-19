<?php

namespace Heidelpay\Gateway\Model\Config\Source;

/**
 * BookingMode
 *
 * Configuration Model for the Booking Mode of certain payment methods
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class BookingMode implements \Magento\Framework\Option\ArrayInterface
{
    const AUTHORIZATION = 'authorize';
    const DEBIT = 'debit';

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::AUTHORIZATION, 'label' => __('Authorization')],
            ['value' => self::DEBIT, 'label' => __('Debit')],
        ];
    }
}
