<?php
namespace Heidelpay\Gateway\Controller;
/**
 * Abstract controller class
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

use Heidelpay\Gateway\Helper\Payment AS HeidelpayHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

abstract class HgwAbstract extends \Magento\Framework\App\Action\Action
{
	protected $resultPageFactory;
	protected $logger;
	
	/**
	 * @var \Magento\Customer\Model\Session
	 */
	protected $_checkoutSession;
	
	/**
	 * @var \Magento\Quote\Model\Quote
	 */
	protected $_quote = false;
	
	/**
	 * @var \Magento\Sales\Model\OrderFactory
	 */
	protected $_orderFactory;
	
	protected $_encryptor;
	
	protected $_logger;
	
	protected $_paymentHelper;
	
	protected $_quoteObject;
	
	/*
	 * \Magento\Quote\Api\CartManagementInterface
	 */
	protected $_cartManagement;
	
	/*
	 * \Magento\Sales\Model\Order\Email\Sender\OrderSender;
	 */
	protected $_orderSender;
	
	/*
	 * \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
	 */
	protected $_orderCommentSender;
	
	/*
	 * 
	 */
	
	protected $_invoiceSender;
	
	/*
	 * \Magento\Framework\View\Result\PageFactory
	 */
	protected $_resultPageFactory;

	/**      * @param \Magento\Framework\App\Action\Context $context      */
public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Url\Helper\Data $urlHelper,
		\Psr\Log\LoggerInterface $logger,
		\Magento\Quote\Api\CartManagementInterface $cartManagement,
		\Magento\Quote\Api\CartRepositoryInterface $quoteObject,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		HeidelpayHelper $paymentHelper,
		OrderSender $orderSender,
		InvoiceSender $invoiceSender,
		OrderCommentSender $orderCommentSender,
		\Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Customer\Model\Url $customerUrl
		
    ) {
	
    	$this->_quoteObject = $quoteObject;
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_urlHelper = $urlHelper;
        $this->_encryptor = $encryptor;
        $this->_customerUrl = $customerUrl;
        $this->_logger = $logger;
        $this->_paymentHelper = $paymentHelper;
        $this->_cartManagement = $cartManagement;
        $this->_orderSender = $orderSender;
        $this->_orderCommentSender = $orderCommentSender;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_invoiceSender = $invoiceSender ;
        parent::__construct($context);
    }
	
	
	/**
	 * Return checkout session object
	 *
	 * @return \Magento\Checkout\Model\Session
	 */
	protected function getCheckout()
	{
		return $this->_checkoutSession;
	}
	
	/**
	 * Return checkout quote object
	 *
	 * @return \Magento\Quote\Model\Quote
	 */
	protected function getQuote()
	{
		if (!$this->_quote) {
			$this->_quote = $this->getCheckout()->getQuote();
		}
		return $this->_quote;
	}
	

}