<?php
namespace Heidelpay\Gateway\Model\Payment ;
/**
 * Debit card payment method
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
use \Heidelpay\Gateway\Model\Payment\HgwAbstract;

class Hgwdc extends   HgwAbstract
{
    const CODE = 'hgwdc';

    protected $_code = 'hgwdc';

    protected $_isGateway                   = true;
    protected $_canAuthorize 				= true;
    //protected $_canCapture                  = true;
    //protected $_canCapturePartial           = true;
    //protected $_canRefund                   = true;
    //protected $_canRefundInvoicePartial     = true;
  

	
    public function	activeRedirect() {
    	return false ;
    }
    
}