<?php

namespace Heidelpay\Gateway\Block\Checkout;

/**
 * Add payment information to the checkout success block
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento
 *
 * @author Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class Success extends \Magento\Checkout\Block\Onepage\Success
{
    /**
     * @return \Magento\Framework\Phrase|null
     */
    public function getHeidelpayInfo()
    {
        // get the heidelpay additional information
        $info = $this->_checkoutSession->getHeidelpayInfo();

        // clear the additional information
        $this->_checkoutSession->setHeidelpayInfo(null);

        return $info;
    }
}
