<?php

namespace Heidelpay\Gateway\Api;

/**
 * API implementation for the heidelpay Payment functionality
 *
 * To work with additional data, the module stored customer payment information
 * in a separate data set to avoid mixing up Magento 2 and heidelpay data.
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
interface PaymentInterface
{
    /**
     * Method for getting stored additional payment information by quote information,
     * if the recognition is allowed and customer payment data is stored for
     * the requested payment method.
     *
     * Returns a json presentation of payment data, if information is available.
     * Else, it just returns null.
     *
     * @param integer $quoteId
     * @param string $paymentMethod
     * @return string
     */
    public function getAdditionalPaymentInformation($quoteId, $paymentMethod);

    /**
     * Method for storing additional payment data for customers.
     *
     * @param int $cartId
     * @param string $method The payment method code
     * @param string[] $additionalData
     * @return string
     */
    public function saveAdditionalPaymentInfo($cartId, $method, $additionalData);

    /**
     * Method for storing additional payment data for guest customers.
     *
     * @param string $cartId
     * @param string $method The payment method code
     * @param string[] $additionalData
     * @return string
     */
    public function saveGuestAdditionalPaymentInfo($cartId, $method, $additionalData);
}
