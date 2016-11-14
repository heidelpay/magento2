<?php
namespace Heidelpay\Gateway\Model\Payment;

/**
 * Sofortüberweisung payment method
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
class Hgwsue extends HgwAbstract
{
    const CODE = 'hgwsue';

    /**
     * @var string
     */
    protected $_code = 'hgwsue';

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_canAuthorize = true;
}