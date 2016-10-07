<?php

namespace Heidelpay\Gateway\PaymentMethodes;
use \Heidelpay\PhpApi\PaymentMethodes\AbstractPaymentMethod as HeidelpayPaymentMethod;
/**
 * Heidelpay  abstract payment method
 *
 * All Heidelpay payment methodes will extend this abstract payment method
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

class HeidelpayAbstractPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod {
    /**
     * PaymentCode
     * @var string
     */
	const CODE = 'hgwabstract';
	protected $_code = 'hgwabstract';
	/**
	 * PaymentCode
	 * @var string
	 */
	protected $_isGateway = true;
	/**
	 * canCapture
	 * @var boolean
	 */
	protected $_canCapture = false;
	/**
	 * canCapturePartial
	 * @var boolean
	 */
	protected $_canCapturePartial = false;
	/**
	 * canRefund
	 * @var string
	 */
	protected $_canRefund = false;
	/**
	 * canRefundInvoicePartial
	 * @var string
	 */
	protected $_canRefundInvoicePartial = false;
	/**
	 * form block type
	 * @var string
	 */
	protected $_formBlockType = 'Heidelpay\Gateway\Block\Payment\HgwAbstract';
	/**
	 * @var \Magento\Framework\UrlInterface
	 */
	protected $urlBuilder = null;
	/**
	 * 
	 * @var \Magento\Framework\App\RequestInterface
	 */
	protected $_requestHttp = null;
	/**
	 * 
	 * @var \Heidelpay\Gateway\Helper\Payment
	 */
	protected $_paymentHelper = null;
	/**
	 * 
	 * @var \Magento\Framework\Locale\ResolverInterface
	 */
	protected $_localResolver = null;
	/**
	 * 
	 * @var \Heidelpay\PhpApi\PaymentMethodes\AbstractPaymentMethod
	 */
	protected $_heidelpayPaymentMethod = NULL;
	
	/**
	 * 
	 * @var \Magento\Payment\Model\Method\Logger
	 */
	
	protected $logger = NULL;
	/**
	 * 
	 * @var \Magento\Framework\Encryption\Encryptor
	 */
	
	protected $_encryptor = null;
	
	/**
	 * Productive payment server url
	 * @var string https://heidelpay.hpcgw.net/ngw/post
	 */
	protected $_live_url = 'https://heidelpay.hpcgw.net/ngw/post';
	/**
	 * Sandbox payment server url
	 * @var string https://test-heidelpay.hpcgw.net/ngw/post
	 */
	protected $_sandbox_url = 'https://test-heidelpay.hpcgw.net/ngw/post';
	
	/**
	 * contract
	 * 
	 * @param \Magento\Framework\Model\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
	 * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
	 * @param \Magento\Payment\Helper\Data $paymentData
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param \Magento\Framework\App\RequestInterface $request
	 * @param \Magento\Framework\UrlInterface $urlinterface
	 * @param \Magento\Framework\Encryption\Encryptor $encryptor
	 * @param \Magento\Payment\Model\Method\Logger $logger
	 * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
	 * @param \Heidelpay\Gateway\Helper\Payment $paymentHelper
	 * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
	 * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
	 * @param array $data
	 */
	public function __construct(
			\Magento\Framework\Model\Context $context, 
			\Magento\Framework\Registry $registry, 
			\Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory, 
			\Magento\Framework\Api\AttributeValueFactory $customAttributeFactory, 
			\Magento\Payment\Helper\Data $paymentData, 
			\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
			\Magento\Framework\App\RequestInterface $request,
			\Magento\Framework\UrlInterface $urlinterface,
			\Magento\Framework\Encryption\Encryptor $encryptor,
			\Magento\Payment\Model\Method\Logger $logger, 
			\Magento\Framework\Locale\ResolverInterface $localeResolver,
			\Heidelpay\Gateway\Helper\Payment $paymentHelper,
			\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null, 
			\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
			array $data = []) {
		parent::__construct ( $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig,  $logger, $resource, $resourceCollection, $data );
		
		$this->urlBuilder = $urlinterface;
		$this->logger	= $logger;
		$this->_requestHttp = $request;
		$this->_paymentHelper = $paymentHelper;
		
		$this->_encryptor = $encryptor;
		$this->_localResolver = $localeResolver;
	}
	
	/**
	 * Active redirect 
	 * 
	 * This function will return false, if the used payment method needs additional
	 * customer payment data to pursue.
	 * @return boolean
	 */
	
	public function	activeRedirct() {
		return true ;
	}
	
	/**
	 * override getConfig to change the configuration path 
	 * @param unknown $field
	 * @param unknown $storeId
	 * @return string config value
	 */
	
	public function getConfigData($field, $storeId = null) {
		$path = 'payment/' . $this->getCode(). '/' . $field;
		return $this->_scopeConfig->getValue ( $path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId );
		;
	}
	public function getHeidelpayUrl($quote) {
	    
	    $config = $this->getMainConfig ( $this->_code, $this->getStore() );
	    
	    $this->_heidelpayPaymentMethod->getRequest()->authentification( 
            $config ['SECURITY.SENDER'],        // SecuritySender
            $config ['USER.LOGIN'],             // UserLogin
            $config ['USER.PWD'],               // UserPassword
            $config ['TRANSACTION.CHANNEL'],    // TransactionChannel credit card without 3d secure
            $config ['TRANSACTION.MODE']        // Enable sandbox mode
        );
	    
	    $frontend = $this->getFrontend();
	    
	    $this->_heidelpayPaymentMethod->getRequest()->async(
	        $frontend['LANGUAGE'],                 // Language code for the Frame
	        $frontend['RESPONSE_URL']              // Response url from your application
	    );
	    
	    $user = $this->getUser($quote);
	    $this->_heidelpayPaymentMethod->getRequest()->customerAddress(
	        $user['NAME.GIVEN'],                   // Given name
	        $user['NAME.FAMILY'],                  // Family name
	        $user['NAME.COMPANY'],                 // Company Name
	        $quote->getCustomerId(),               // Customer id of your application
	        $user['ADDRESS.STREET'],               // Billing address street
	        NULL,                                  // Billing address state
	        $user['ADDRESS.ZIP'],                  // Billing address post code
	        $user['ADDRESS.CITY'],                 // Billing address city
	        $user['ADDRESS.COUNTRY'],              // Billing address country code            
	        $user['CONTACT.EMAIL']                 // Customer mail address
	        );
	    
	    $this->_heidelpayPaymentMethod->getRequest()->basketData(
	        $quote->getId(),                                                               // Reference Id of your application
	        $this->_paymentHelper->format($quote->getGrandTotal()),  // Amount of this request
	        $quote->getBaseCurrencyCode(),                                                 // Currency code of this request
	        $this->_encryptor->exportKeys()                                                // A secret passphrase from your application
	        );
	    
	    /** @todo should be removed after using heidelpay php-api for every payment method */
	    $this->_heidelpayPaymentMethod->getRequest()->getCriterion()->set('secret',$this->_encryptor->getHash($quote->getId().$this->_encryptor->exportKeys()));
	    
	    /** Force PhpApi to just generate the request instead of sending it directly */
	    $this->_heidelpayPaymentMethod->_dryRun=TRUE;
	    
	    $this->_heidelpayPaymentMethod->authorize();
	    
	    
	    $request =   $this->_heidelpayPaymentMethod->getRequest()->prepareRequest();
	    $response =  $this->_heidelpayPaymentMethod->getRequest()->send($this->_heidelpayPaymentMethod->getPaymentUrl(), $request);
	        
		return $response;
	}
	
	/**
	 * getMainConfig will return the backend configuration for the given payment method 
	 * 
	 * @param string $code payment method name
	 * @param string $storeId id of the store front
	 * @return array configuration form backend
	 */
	
	
	public function getMainConfig($code, $storeId = false) {
		$path = "payment/hgwmain/";
		$config = array ();
		
		$config ['SECURITY.SENDER'] = $this->_scopeConfig->getValue ( $path . "security_sender", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId );
		
		if ($this->_scopeConfig->getValue ( $path . "sandbox_mode", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId ) == 0) {
			$config ['TRANSACTION.MODE'] = 'FALSE';
		} else {
			$config ['TRANSACTION.MODE'] = 'TRUE';
		}
		$config ['USER.LOGIN'] = trim ( $this->_scopeConfig->getValue ( $path . "user_login", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId ) );
		$config ['USER.PWD'] = trim ( $this->_scopeConfig->getValue ( $path . "user_passwd", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId ) );
		
		$path = 'payment/' . $code . '/';
		$config ['TRANSACTION.CHANNEL'] = trim ( $this->_scopeConfig->getValue ( $path . "channel", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId ) );
		
		return $config;
	}
	
	/**
	 * getFrontend will return shop language and response url
	 * 
	 * @return array shop language and response url
	 */
	
	public function getFrontend() {
		$langCode = explode('_', (string)$this->_localResolver->getLocale());
		$lang = strtoupper($langCode[0]);
		
		return array (
				'LANGUAGE' => $lang, 
				'RESPONSE_URL' => $this->urlBuilder->getUrl ( 'hgw/index/response', array (
						'_forced_secure' => true,
						'_store_to_url' => true,
						'_nosid' => true 
				) ),
		);
	}
	
	/**
	 *  getUser extract customer information form magento order object
	 *  
	 * @param  $order object
	 * @return array customer information 
	 */	
	public function getUser($order) {
	
		$user = array();
		$billing	= $order->getBillingAddress();
		$email = ($order->getBillingAddress()->getEmail()) ? $order->getBillingAddress()->getEmail() : $order->getCustomerEmail();
		
		$billingStreet = '';
		
		foreach ($billing->getStreet() as $street) {
			$billingStreet .= $street.' ';
		}
	
		$user['NAME.COMPANY'] = ($billing->getCompany() === false) ? NULL : trim($billing->getCompany());
		$user['NAME.GIVEN']			= trim($billing->getFirstname());
		$user['NAME.FAMILY']		= trim($billing->getLastname());
		$user['ADDRESS.STREET']		= trim($billingStreet);
		$user['ADDRESS.ZIP']		= trim($billing->getPostcode());
		$user['ADDRESS.CITY']		= trim($billing->getCity());
		$user['ADDRESS.COUNTRY']	= trim($billing->getCountryId());
		$user['CONTACT.EMAIL']		= trim($email);
	
		return $user;
	}
	
	/**
	 * Additional payment information
	 *
	 * This function will return a text message used to show payment information
	 * to your customer on the checkout success page
	 * @param unknown $Response
	 * @return string|boolean payment information or false
	 */
	
	public function additionalPaymentInformation($Response)
	{
	    return false;
	}
}