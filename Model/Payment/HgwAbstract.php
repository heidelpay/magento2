<?php
namespace Heidelpay\Gateway\Model\Payment;
/**
 * Abstract payment method
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

class HgwAbstract extends \Magento\Payment\Model\Method\AbstractMethod {
	const CODE = 'hgwabstract';
	protected $_code = 'hgwabstract';
	protected $_isGateway = true;
	protected $_canCapture = false;
	protected $_canCapturePartial = false;
	protected $_canRefund = false;
	protected $_canRefundInvoicePartial = false;
	protected $_formBlockType = 'Heidelpay\Gateway\Block\Payment\HgwAbstract';
	protected $_minAmount = null;
	protected $_maxAmount = null;
	protected $urlBuilder = null;
	protected $_requestHttp = null;
	protected $_paymentHelper = null;
	protected $_localResolver = null;
	
	protected $_encryptor = null;
	
	protected $_live_url = 'https://heidelpay.hpcgw.net/ngw/post';
	protected $_sandbox_url = 'https://test-heidelpay.hpcgw.net/ngw/post';
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
		$this->log	= $logger;
		$this->_requestHttp = $request;
		$this->_paymentHelper = $paymentHelper;
		
		$this->_encryptor = $encryptor;
		$this->_localResolver = $localeResolver;
	}
	
	public function	activeRedirct() {
		return true ;
	}
	
	public function getConfigData($field, $storeId = null) {
		$path = 'payment/' . $this->getCode () . '/' . $field;
		return $this->_scopeConfig->getValue ( $path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId );
		;
	}
	public function getHeidelpayUrl($quote, $isRegistration = false) {
		$config = $frontend = $user = $basketData = array ();
		$criterion = array ();
		
		$ordernr = $quote->getId();
		
		;
		// $this->log("Heidelpay Payment Code : ".$this->_code);
		$config = $this->getMainConfig ( $this->_code, $this->getStore() );
		
		if ($isRegistration === true)
			$config ['PAYMENT.TYPE'] = 'RG';
		
		// add parameters for pci 3 iframe
		 if ($this->_code == 'hgwcc' or $this->_code == 'hgwdc' ) {
			 $url = explode( '/', $this->urlBuilder->getUrl('/', array('_secure' => true)));
		 	$criterion['FRONTEND.PAYMENT_FRAME_ORIGIN'] = $url[0].'//'.$url[2];
			$criterion['FRONTEND.CSS_PATH'] = $this->_scopeConfig->getValue ("payment/hgwmain/default_css", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->getStore() );
			
			$config ['PAYMENT.TYPE'] = 'DB'; // Todo load payment type 
			 // set frame to sync modus if frame is used in bevor order mode (this is the registration case)
		 	$criterion['FRONTEND.PREVENT_ASYNC_REDIRECT'] = ($isRegistration === true) ? 'TRUE' : 'FALSE';
		 }
		
		$frontend = $this->getFrontend ( $ordernr );
		
		
		$user = $this->getUser($quote, $isRegistration);
		$basketData = $this->getBasketData($quote);
		
		$params = $this->_paymentHelper->preparePostData( $config, $frontend, $user, $basketData,$criterion);
		
		$src = $this->_paymentHelper->doRequest($config['URL'], $params);
		
		return $src;
	}
	
	public function getBasketData($quote, $amount=false) {
		$data = array (
				'PRESENTATION.AMOUNT' 			=> ($amount) ? $amount : $this->_paymentHelper->format($quote->getGrandTotal()),
				'PRESENTATION.CURRENCY'			=> $quote->getBaseCurrencyCode(),
				'IDENTIFICATION.TRANSACTIONID'	=> $quote->getId()
		);
		return $data;
	}
	
	public function getMainConfig($code, $storeId = false) {
		$path = "payment/hgwmain/";
		$config = array ();
		$config ['PAYMENT.METHOD'] = preg_replace ( '/^hgw/', '', $code );
		
		$config ['SECURITY.SENDER'] = $this->_scopeConfig->getValue ( $path . "security_sender", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId );
		
		if ($this->_scopeConfig->getValue ( $path . "sandbox_mode", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId ) == 0) {
			$config ['TRANSACTION.MODE'] = 'LIVE';
			$config ['URL'] = $this->_live_url;
		} else {
			$config ['TRANSACTION.MODE'] = 'CONNECTOR_TEST';
			$config ['URL'] = $this->_sandbox_url;
		}
		$config ['USER.LOGIN'] = trim ( $this->_scopeConfig->getValue ( $path . "user_login", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId ) );
		$config ['USER.PWD'] = trim ( $this->_scopeConfig->getValue ( $path . "user_passwd", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId ) );
		
		$path = 'payment/' . $code . '/';
		$config ['TRANSACTION.CHANNEL'] = trim ( $this->_scopeConfig->getValue ( $path . "channel", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId ) );
		// ($this->_scopeConfig->getValue($path."bookingmode", $storeId) == true) ? $config['PAYMENT.TYPE'] = $this->_scopeConfig->getValue($path."bookingmode", $storeId) : false ;
		
		return $config;
	}
	public function getFrontend($ordernr, $storeId = false) {
		$langCode = explode('_', (string)$this->_localResolver->getLocale());
		$lang = strtoupper($langCode[0]);
		
		return array (
				'FRONTEND.LANGUAGE' => $lang, 
				'FRONTEND.RESPONSE_URL' => $this->urlBuilder->getUrl ( 'hgw/index/response', array (
						'_forced_secure' => true,
						'_store_to_url' => true,
						'_nosid' => true 
				) ),
				'CRITERION.PUSH_URL' => $this->urlBuilder->getUrl ( 'hgw/index/push', array (
						'_forced_secure' => true,
						'_store_to_url' => true,
						'_nosid' => true 
				) ),				// PUSH proxy is only used for development purpose
				'CRITERION.SECRET' => $this->_encryptor->getHash($ordernr.$this->_encryptor->exportKeys()),
				'CRITERION.LANGUAGE' => (string)$this->_localResolver->getLocale(),
				'CRITERION.STOREID' => $this->getStore(),
				'SHOP.TYPE' => 'Magento 2.x', 
				'SHOPMODULE.VERSION' => 'Heidelpay Gateway - 16.10.7'
		);
	}
	
	
	public function getUser($order, $isReg=false) {
	
		$user = array();
		$billing	= $order->getBillingAddress();
		$email = ($order->getBillingAddress()->getEmail()) ? $order->getBillingAddress()->getEmail() : $order->getCustomerEmail();
		$CustomerId = $order->getCustomerId();
		$user['CRITERION.GUEST'] = 'false';
		if ( $CustomerId == 0) {
			$user['CRITERION.GUEST'] = 'true';
		}
		
		$billingStreet = '';
		
		foreach ($billing->getStreet() as $street) {
			$billingStreet .= $street.' ';
		}
	
		$user['IDENTIFICATION.SHOPPERID'] 	= $CustomerId;
		if ($billing->getCompany() == true) $user['NAME.COMPANY']	= trim($billing->getCompany());
		$user['NAME.GIVEN']			= trim($billing->getFirstname());
		$user['NAME.FAMILY']		= trim($billing->getLastname());
		$user['ADDRESS.STREET']		= trim($billingStreet);
		$user['ADDRESS.ZIP']		= trim($billing->getPostcode());
		$user['ADDRESS.CITY']		= trim($billing->getCity());
		$user['ADDRESS.COUNTRY']	= trim($billing->getCountryId());
		$user['CONTACT.EMAIL']		= trim($email);
		$user['CONTACT.IP']		=  (filter_var(trim($this->_requestHttp->getClientIp(true)), FILTER_VALIDATE_IP)) ? trim($this->_requestHttp->getClientIp(true)) : '127.0.0.1' ;
	
	
		return $user;
	}
	
	public function assignData(\Magento\Framework\DataObject $data) {
		$this->_logger->addDebug ( 'payment methode hgw assignData' );
		$infoInstance = $this->getInfoInstance ();
		$infoInstance->setAdditionalInformation ( 'payment_method_nonce', $data->getPaymentMethodNonce () );
		return $this;
	}
	public function validate() {
		$this->_logger->addDebug ( 'payment methode hgw validate' );
		$info = $this->getInfoInstance ();
		if ($info instanceof \Magento\Sales\Model\Order\Payment) {
			$billingCountry = $info->getOrder ()->getBillingAddress ()->getCountryId ();
		} else {
			$billingCountry = $info->getQuote ()->getBillingAddress ()->getCountryId ();
		}
		
		return $this;
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