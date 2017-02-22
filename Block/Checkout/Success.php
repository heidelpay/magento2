<?php 
namespace Heidelpay\Gateway\Block\Checkout;

/**
 * Add payment information to the checkout success block
 *
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/magento
 *
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
class Success extends \Magento\Checkout\Block\Onepage\Success
{
    public function getHeidelpayInfo()
    {
        $info = ($this->_checkoutSession->getHeidelpayInfo() !== false) ? $this->_checkoutSession->getHeidelpayInfo() : false;
        $this->_checkoutSession->setHeidelpayInfo(false);
        return $info;
    }
}
