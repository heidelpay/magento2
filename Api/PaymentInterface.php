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
     * @param int $cartId
     * @param string $hgwIban
     * @param string $hgwOwner
     * @return mixed
     */
    public function saveDirectDebitInfo($cartId, $hgwIban, $hgwOwner);
}
