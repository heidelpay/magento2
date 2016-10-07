<?php
namespace Heidelpay\Gateway\Model\Payment ;
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
use \Heidelpay\Gateway\Model\Payment\HgwAbstract;

class Hgwsue extends   HgwAbstract
{
    const CODE = 'hgwsue';

    protected $_code = 'hgwsue';

    protected $_isGateway                   = true;
    protected $_canAuthorize 				= true;


    
}