<?php

namespace Heidelpay\Gateway\Block\Checkout;

/**
 * Add payment information to the checkout success block
 *
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
class Success extends \Magento\Checkout\Block\Onepage\Success
{
    /**
     * @return bool
     */
    public function getHeidelpayInfo()
    {
        $session = $this->_checkoutSession;
        $heidelpayInfo = $session->getHeidelpayInfo();
        $info = ($heidelpayInfo !== false) ? $heidelpayInfo : false;
        $session->setHeidelpayInfo(false);

        return $info;
    }
}
