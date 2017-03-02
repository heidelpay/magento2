<?php

namespace Heidelpay\Gateway\Block\Payment;

/**
 * TODO: fill summary
 *
 * TODO: fill desc
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/magento2
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */

class HgwDirectDebit extends \Magento\Payment\Block\Form
{
    /**
     * Purchase order template
     *
     * @var string
     */
    protected $_template = 'Heidelpay_Gateway::form/dd.phtml';
}