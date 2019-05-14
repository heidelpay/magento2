<?php
/**
 * This is the base for all wrappers containing shared code.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  magento2
 */
namespace Heidelpay\Gateway\Wrapper;

use Heidelpay\Gateway\Traits\DumpGetterReturnsTrait;

class BaseWrapper
{
    use DumpGetterReturnsTrait;

    /**
     * Convert an euro amount to cent.
     * @param $value
     * @return int
     */
    public function normalizeValue($value)
    {
        return (int)round(bcmul($value, 100));
    }
}
