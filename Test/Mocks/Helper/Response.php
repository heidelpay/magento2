<?php
/**
 * Mocking class for Heidelpay\Gateway\Helper\Response.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @author  David Owusu <development@heidelpay.com>
 *
 * @package  heidelpay/magento2
 */

namespace Heidelpay\Gateway\Test\Mocks\Helper;


use Heidelpay\Gateway\Helper\Response as ResponseHelper;

class Response extends ResponseHelper
{
    /** Returns always true in order to bypass it for the tests.
     * @param \Heidelpay\PhpPaymentApi\Response $response
     * @param $remoteAddress
     * @return bool
     */
    public function validateSecurityHash($response, $remoteAddress)
    {
        return true;
    }
}