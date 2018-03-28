<?php
/**
 * Interface for the payment config representation handling the parameters every payment method has.
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

interface HgwBasePaymentConfigInterface
{
    /**
     * Get payment configuration status
     *
     * @return bool
     */
    public function isActive();

    /**
     * Get payment channel id.
     *
     * @return string
     */
    public function getChannel();

    /**
     * Get allow specific flag
     *
     * @return int
     */
    public function getAllowSpecific();

    /**
     * Get allowed countries
     *
     * @return array
     */
    public function getSpecificCountries();

    /**
     * Get the minimum amount the total has to be in order to make this payment method available.
     *
     * @return int
     */
    public function getMinOrderTotal();

    /**
     * Get the maximum amount the total has to be in order to make this payment method available.
     *
     * @return int
     */
    public function getMaxOrderTotal();

    /**
     * Returns true if the payment method needs to be initialized to be rendered due to additional text
     * to be output in the payment form.
     *
     * @return bool
     */
    public function getNeedsExternalInfoInCheckout();

    /**
     * Get the configured booking mode e.g. authorize, debit,...
     *
     * @return int
     */
    public function getBookingMode();
}
