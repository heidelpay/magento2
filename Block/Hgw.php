<?php

namespace Heidelpay\Gateway\Block;
use Magento\Framework\View\Element\Template;

/**
 * Heidelpay Gateway Block
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
class Hgw extends Template
{
    /**
     * @var string
     */
    protected $_hgwUrl;

    /**
     * @return Template
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
}