<?php

namespace Heidelpay\Gateway\Api;

/**
 * Test!
 *
 * Test
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
interface PaymentInterface
{
    /**
     * Method for getting stored additional payment information by quote information,
     * if the recognition is allowed and customer payment data is stored for
     * the requested payment method.
     *
     * @param integer $quoteId
     * @param string $paymentMethod
     * @return mixed|null
     */
    public function getAdditionalPaymentInformation($quoteId, $paymentMethod);

    /**
     * Method for storing additional payment data for customers
     *
     * @param int $cartId
     * @param string $hgwIban
     * @param string $hgwOwner
     * @return mixed
     */
    public function saveDirectDebitInfo($cartId, $hgwIban, $hgwOwner);

    /**
     * Method for storing additional payment data for guest customers
     *
     * @param string $cartId
     * @param string $hgwIban
     * @param string $hgwOwner
     * @return mixed
     */
    public function saveGuestDirectDebitInfo($cartId, $hgwIban, $hgwOwner);
}
