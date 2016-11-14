<?php

namespace Heidelpay\Gateway\Model\Payment;

/**
 * Credit card payment method
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link  https://dev.heidelpay.de/magento
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
class Hgwcc extends HgwAbstract
{
    const CODE = 'hgwcc';

    /**
     * @var string
     */
    protected $_code = 'hgwcc';

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * @var string
     */
    protected $_formBlockType = 'Heidelpay\Gateway\Block\Payment\Hgwpp';

    /**
     * @return bool
     */
    public function activeRedirect()
    {
        return false;
    }
}