<?php

namespace Heidelpay\Gateway\Block\Info;

/**
 * Heidelpay Prepayment Info Block
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay\magento2\block\info\prepayment
 */
class Prepayment extends \Heidelpay\Gateway\Block\Info\AbstractBlock
{
    /**
     * @var string
     */
    protected $_template = 'Heidelpay_Gateway::info/prepayment.phtml';

    /**
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Heidelpay_Gateway::info/pdf/prepayment.phtml');
        return $this->toHtml();
    }
}
