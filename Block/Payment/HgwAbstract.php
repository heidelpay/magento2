<?php
namespace Heidelpay\Gateway\Block\Payment;
/**
 *Abstract payment block
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
class HgwAbstract extends \Magento\Payment\Block\Form\Cc
{
	/**
	 * @var string
	 */
	protected $_template = 'Magento_Payment::form/cc.phtml';

	/**
	 * Payment config model
	 *
	 * @var \Magento\Payment\Model\Config
	 */

	/**
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Magento\Payment\Model\Config $paymentConfig
	 * @param array $data
	 */


	/**
	 * Render block HTML
	 *
	 * @return string
	 */
	
}
