<?php

namespace Heidelpay\Gateway\Block\Info;

/**
 * Heidelpay InvoiceSecured Info Block Class
 *
 * @license    Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright  Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link       https://dev.heidelpay.de/magento2
 * @author     Stephano Vogel
 *
 * @package    heidelpay\magento2\block\info\invoicesecured
 */
class InvoiceSecured extends \Heidelpay\Gateway\Block\Info\AbstractBlock
{
    /**
     * @var string
     */
    protected $_template = 'Heidelpay_Gateway::info/invoice_secured.phtml';

    /**
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Heidelpay_Gateway::info/pdf/invoice_secured.phtml');
        return $this->toHtml();
    }
}
