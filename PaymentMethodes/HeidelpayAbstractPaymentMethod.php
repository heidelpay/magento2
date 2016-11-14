<?php

namespace Heidelpay\Gateway\PaymentMethodes;

use Heidelpay\Gateway\Helper\Payment;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\ScopeInterface;

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
class HeidelpayAbstractPaymentMethod extends AbstractMethod
{
    /**
     * PaymentCode
     * @var string
     */
    const CODE = 'hgwabstract';

    /**
     * @var string
     */
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
     * @var UrlInterface
     */
    protected $urlBuilder = null;

    /**
     * @var RequestInterface
     */
    protected $_requestHttp = null;

    /**
     * @var Payment
     */
    protected $_paymentHelper = null;

    /**
     * @var ResolverInterface
     */
    protected $_localResolver = null;

    /**
     * @var \Heidelpay\PhpApi\PaymentMethodes\AbstractPaymentMethod
     */
    protected $_heidelpayPaymentMethod = null;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var Encryptor
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
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     * @param UrlInterface $urlinterface
     * @param Encryptor $encryptor
     * @param Logger $logger
     * @param ResolverInterface $localeResolver
     * @param Payment $paymentHelper
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        UrlInterface $urlinterface,
        Encryptor $encryptor,
        Logger $logger,
        ResolverInterface $localeResolver,
        Payment $paymentHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data);

        $this->urlBuilder = $urlinterface;
        $this->logger = $logger;
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
    public function activeRedirct()
    {
        return true;
    }

    /**
     * override getConfig to change the configuration path
     * @param $field
     * @param $storeId
     * @return string config value
     */
    public function getConfigData($field, $storeId = null)
    {
        $code = $this->getCode();
        $path = 'payment/' . $code . '/' . $field;

        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $quote
     * @return array|object
     */
    public function getHeidelpayUrl($quote)
    {
        $storeId = $this->getStore();
        $code = $this->_code;
        $config = $this->getMainConfig($code, $storeId);

        $abstractPaymentMethod = $this->_heidelpayPaymentMethod;
        $abstractPaymentMethod->getRequest()->authentification(
            $config['SECURITY.SENDER'],        // SecuritySender
            $config['USER.LOGIN'],             // UserLogin
            $config['USER.PWD'],               // UserPassword
            $config['TRANSACTION.CHANNEL'],    // TransactionChannel credit card without 3d secure
            $config['TRANSACTION.MODE']        // Enable sandbox mode
        );

        $frontend = $this->getFrontend();

        $abstractPaymentMethod->getRequest()->async(
            $frontend['LANGUAGE'],                 // Language code for the Frame
            $frontend['RESPONSE_URL']              // Response url from your application
        );

        $user = $this->getUser($quote);
        $abstractPaymentMethod->getRequest()->customerAddress(
            $user['NAME.GIVEN'],                   // Given name
            $user['NAME.FAMILY'],                  // Family name
            $user['NAME.COMPANY'],                 // Company Name
            $quote->getCustomerId(),               // Customer id of your application
            $user['ADDRESS.STREET'],               // Billing address street
            null,                                  // Billing address state
            $user['ADDRESS.ZIP'],                  // Billing address post code
            $user['ADDRESS.CITY'],                 // Billing address city
            $user['ADDRESS.COUNTRY'],              // Billing address country code
            $user['CONTACT.EMAIL']                 // Customer mail address
        );
        $abstractPaymentMethod->getRequest()->getCriterion()->set('guest', $user['CRITERION.GUEST']);

        $payment = $this->_paymentHelper;
        $encryptor = $this->_encryptor;
        $abstractPaymentMethod->getRequest()->basketData(
            $quote->getId(),                                                               // Reference Id of your application
            $payment->format($quote->getGrandTotal()),  // Amount of this request
            $quote->getBaseCurrencyCode(),                                                 // Currency code of this request
            $encryptor->exportKeys()                                                // A secret passphrase from your application
        );

        /**
         * Magento Version
         * @todo replace fixed shop version and plugin version
         * */
        $abstractPaymentMethod->getRequest()->getCriterion()->set('SHOP.TYPE', 'Magento 2.x');
        $abstractPaymentMethod->getRequest()->getCriterion()->set('SHOPMODULE.VERSION', 'Heidelpay Gateway - 16.10.27');

        /** @todo should be removed after using heidelpay php-api for every payment method */
        //$this->_heidelpayPaymentMethod->getRequest()->getCriterion()->set('secret',$this->_encryptor->getHash($quote->getId().$this->_encryptor->exportKeys()));

        /** Force PhpApi to just generate the request instead of sending it directly */
        $abstractPaymentMethod->_dryRun = true;
        $abstractPaymentMethod->authorize();
        $request = $abstractPaymentMethod->getRequest()->prepareRequest();
        $response = $abstractPaymentMethod->getRequest()->send($abstractPaymentMethod->getPaymentUrl(), $request);

        return $response;
    }

    /**
     * getMainConfig will return the backend configuration for the given payment method
     *
     * @param string $code payment method name
     * @param bool|string $storeId id of the store front
     * @return array configuration form backend
     */
    public function getMainConfig($code, $storeId = false)
    {
        $path = "payment/hgwmain/";
        $config = [];
        $config ['SECURITY.SENDER'] = $this->_scopeConfig->getValue($path . "security_sender", ScopeInterface::SCOPE_STORE, $storeId);

        if ($this->_scopeConfig->getValue($path . "sandbox_mode", ScopeInterface::SCOPE_STORE, $storeId) == 0) {
            $config ['TRANSACTION.MODE'] = 'FALSE';
        } else {
            $config ['TRANSACTION.MODE'] = 'TRUE';
        }
        $config ['USER.LOGIN'] = trim($this->_scopeConfig->getValue($path . "user_login", ScopeInterface::SCOPE_STORE, $storeId));
        $config ['USER.PWD'] = trim($this->_scopeConfig->getValue($path . "user_passwd", ScopeInterface::SCOPE_STORE, $storeId));
        $path = 'payment/' . $code . '/';
        $config ['TRANSACTION.CHANNEL'] = trim($this->_scopeConfig->getValue($path . "channel", ScopeInterface::SCOPE_STORE, $storeId));

        return $config;
    }

    /**
     * getFrontend will return shop language and response url
     *
     * @return array shop language and response url
     */
    public function getFrontend()
    {
        $langCode = explode('_', (string)$this->_localResolver->getLocale());
        $lang = strtoupper($langCode[0]);

        return [
            'LANGUAGE'     => $lang,
            'RESPONSE_URL' => $this->urlBuilder->getUrl('hgw/index/response', [
                '_forced_secure' => true,
                '_store_to_url'  => true,
                '_nosid'         => true,
            ]),
        ];
    }

    /**
     *  getUser extract customer information form magento order object
     *
     * @param  $order object
     * @return array customer information
     */
    public function getUser($order)
    {

        $user = [];
        $billing = $order->getBillingAddress();
        $email = ($order->getBillingAddress()->getEmail()) ? $order->getBillingAddress()->getEmail() : $order->getCustomerEmail();

        $billingStreet = '';

        foreach ($billing->getStreet() as $street) {
            $billingStreet .= $street . ' ';
        }
        $CustomerId = $order->getCustomerId();
        $user['CRITERION.GUEST'] = 'false';
        if ($CustomerId == 0) {
            $user['CRITERION.GUEST'] = 'true';
        }

        $user['NAME.COMPANY'] = ($billing->getCompany() === false) ? null : trim($billing->getCompany());
        $user['NAME.GIVEN'] = trim($billing->getFirstname());
        $user['NAME.FAMILY'] = trim($billing->getLastname());
        $user['ADDRESS.STREET'] = trim($billingStreet);
        $user['ADDRESS.ZIP'] = trim($billing->getPostcode());
        $user['ADDRESS.CITY'] = trim($billing->getCity());
        $user['ADDRESS.COUNTRY'] = trim($billing->getCountryId());
        $user['CONTACT.EMAIL'] = trim($email);

        return $user;
    }

    /**
     * Additional payment information
     *
     * This function will return a text message used to show payment information
     * to your customer on the checkout success page
     * @param $response
     * @return string|boolean payment information or false
     */
    public function additionalPaymentInformation($response)
    {
        return false;
    }
}