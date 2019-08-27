<?php
/**
 * Payment config representation handling the parameters every payment method has.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpay/magento2
 */
namespace Heidelpay\Gateway\Gateway\Config;

use Heidelpay\Gateway\Traits\DumpGetterReturnsTrait;
use \Magento\Payment\Gateway\Config\Config as BaseConfig;

class HgwBasePaymentConfig extends BaseConfig implements HgwBasePaymentConfigInterface
{
    use DumpGetterReturnsTrait;

    const KEY_ACTIVE = 'active';
    const KEY_CHANNEL = 'channel';
    const KEY_ALLOW_SPECIFIC = 'allowspecific';
    const KEY_SPECIFIC = 'specificcountry';
    const KEY_RECOGNITION = 'recognition';
    const KEY_MIN_ORDER_TOTAL = 'min_order_total';
    const KEY_MAX_ORDER_TOTAL = 'max_order_total';
    const KEY_NEEDS_EXTERNAL_INFO_IN_CHECKOUT = 'needs_external_info_in_checkout';
    const KEY_BOOKING_MODE = 'bookingmode';

    const VALUE_ALL_COUNTRIES = 0;
    const VALUE_SPECIFIC_COUNTRIES = 1;

    /**
     * Get payment configuration status
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    /**
     * Get payment channel id.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getChannel($storeId = null)
    {
        return $this->getValue(self::KEY_CHANNEL, $storeId);
    }

    /**
     * Get allow specific flag
     *
     * @return int
     */
    public function getAllowSpecific()
    {
        return (int) $this->getValue(self::KEY_ALLOW_SPECIFIC);
    }

    /**
     * Get allowed countries
     *
     * @return array
     */
    public function getSpecificCountries()
    {
        if ($this->getAllowSpecific() === self::VALUE_ALL_COUNTRIES) {
            return [];
        }

        return explode(',', $this->getValue(self::KEY_SPECIFIC));
    }

    /**
     * Get the minimum amount the total has to be in order to make this payment method available.
     *
     * @return int
     */
    public function getMinOrderTotal()
    {
        return (int) $this->getValue(self::KEY_MIN_ORDER_TOTAL);
    }

    /**
     * Get the maximum amount the total has to be in order to make this payment method available.
     *
     * @return int
     */
    public function getMaxOrderTotal()
    {
        return (int) $this->getValue(self::KEY_MAX_ORDER_TOTAL);
    }

    /**
     * Returns true if the payment method needs to be initialized to be rendered due to additional text
     * to be output in the payment form.
     *
     * @return bool
     */
    public function getNeedsExternalInfoInCheckout()
    {
        return (bool) $this->getValue(self::KEY_NEEDS_EXTERNAL_INFO_IN_CHECKOUT);
    }

    /**
     * Get the configured booking mode e.g. authorize, debit,...
     *
     * @return int
     */
    public function getBookingMode()
    {
        return $this->getValue(self::KEY_BOOKING_MODE);
    }
}
