<?php
namespace Heidelpay\Gateway\Block;

/**
 * Heidelpay Gateway Block
 *
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/magento
 *
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
class Hgw extends \Magento\Framework\View\Element\Template
{
    protected $_hgwUrl;
    
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
    
    /*
    protected function setHgwUrl($url) {
        $this->_hgwUrl = $url;
    }

    protected function getHgwUrl() {
        return $this->_hgwUrl;
    }
    */
}
