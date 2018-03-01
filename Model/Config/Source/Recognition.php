<?php

namespace Heidelpay\Gateway\Model\Config\Source;

/**
 * Recognition
 *
 * Config Model for customer recognition settings
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class Recognition implements \Magento\Framework\Option\ArrayInterface
{
    const RECOGNITION_NEVER = 'never';
    const RECOGNITION_SAME_SHIPPING_ADDRESS = 'same_shipping_address';
    const RECOGNITION_ALWAYS = 'always';

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::RECOGNITION_NEVER, 'label' => __('Never')],
            ['value' => self::RECOGNITION_SAME_SHIPPING_ADDRESS, 'label' => __('Same Shipping Address')],
            ['value' => self::RECOGNITION_ALWAYS, 'label' => __('Always')]
        ];
    }
}
