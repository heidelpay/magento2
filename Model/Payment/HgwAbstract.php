<?php

namespace Heidelpay\Gateway\Model\Payment;

/**
 * @TODO This Abstract is deprecated and should be replaced
 */
use Heidelpay\Gateway\Helper\Payment;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
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
class HgwAbstract extends AbstractMethod
{
    const CODE = 'hgwabstract';

    /**
     * @var string
     */
    protected $_code = 'hgwabstract';

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_canCapture = false;

    /**
     * @var bool
     */
    protected $_canCapturePartial = false;

    /**
     * @var bool
     */
    protected $_canRefund = false;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = false;

    /**
     * @var string
     */
    protected $_formBlockType = 'Heidelpay\Gateway\Block\Payment\HgwAbstract';

    /**
     * @var null
     */
    protected $_minAmount = null;

    /**
     * @var null
     */
    protected $_maxAmount = null;

    /**
     * @var null
     */
    protected $urlBuilder = null;

    /**
     * @var null
     */
    protected $_requestHttp = null;

    /**
     * @var null
     */
    protected $_paymentHelper = null;

    /**
     * @var null
     */
    protected $_localResolver = null;

    /**
     * @var null
     */
    protected $_encryptor = null;

    /**
     * @var string
     */
    protected $_live_url = 'https://heidelpay.hpcgw.net/ngw/post';

    /**
     * @var string
     */
    protected $_sandbox_url = 'https://test-heidelpay.hpcgw.net/ngw/post';

    /**
     * @var Logger
     */
    private $log;

    /**
     * HgwAbstract constructor.
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
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
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
        $this->log = $logger;
        $this->_requestHttp = $request;
        $this->_paymentHelper = $paymentHelper;
        $this->_encryptor = $encryptor;
        $this->_localResolver = $localeResolver;
    }

    /**
     * @return bool
     */
    public function activeRedirect()
    {
        return true;
    }

    /**
     * @param string $field
     * @param null $storeId
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        $path = 'payment/' . $this->getCode() . '/' . $field;
        $scopeConfig = $this->_scopeConfig;
        $scopeValue = $scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);

        return $scopeValue;
    }

    /**
     * @param $quote
     * @param bool $isRegistration
     * @return mixed|null
     */
    public function getHeidelpayUrl($quote, $isRegistration = false)
    {
        $criterion = [];
        $orderNr = $quote->getId();
        $storeId = $this->getStore();
        $config = $this->getMainConfig($this->_code, $storeId);

        if ($isRegistration === true) {
            $config ['PAYMENT.TYPE'] = 'RG';
        }

        // add parameters for pci 3 iframe
        if ($this->_code == 'hgwcc' or $this->_code == 'hgwdc') {
            $urlBuilder = $this->urlBuilder;
            $url = explode('/', $urlBuilder->getUrl('/', ['_secure' => true]));
            $criterion['FRONTEND.PAYMENT_FRAME_ORIGIN'] = $url[0] . '//' . $url[2];
            $scopeConfig = $this->_scopeConfig;
            $criterion['FRONTEND.CSS_PATH'] = $scopeConfig->getValue("payment/hgwmain/default_css", ScopeInterface::SCOPE_STORE, $storeId);

            $config ['PAYMENT.TYPE'] = 'DB';
            /** @TODO load payment type */
            // set frame to sync modus if frame is used in bevor order mode (this is the registration case)
            $criterion['FRONTEND.PREVENT_ASYNC_REDIRECT'] = ($isRegistration === true) ? 'TRUE' : 'FALSE';
        }

        $frontend = $this->getFrontend($orderNr);
        $user = $this->getUser($quote, $isRegistration);
        $basketData = $this->getBasketData($quote);
        $paymentHelper = $this->_paymentHelper;
        $params = $paymentHelper->preparePostData($config, $frontend, $user, $basketData, $criterion);
        $src = $paymentHelper->doRequest($config['URL'], $params);

        return $src;
    }

    /**
     * @param $quote
     * @param bool $amount
     * @return array
     */
    public function getBasketData($quote, $amount = false)
    {
        $data = [
            'PRESENTATION.AMOUNT'          => ($amount) ? $amount : $this->_paymentHelper->format($quote->getGrandTotal()),
            'PRESENTATION.CURRENCY'        => $quote->getBaseCurrencyCode(),
            'IDENTIFICATION.TRANSACTIONID' => $quote->getId(),
        ];

        return $data;
    }

    /**
     * @param $code
     * @param bool $storeId
     * @return array
     */
    public function getMainConfig($code, $storeId = false)
    {
        $path = "payment/hgwmain/";
        $config = [];
        $config ['PAYMENT.METHOD'] = preg_replace('/^hgw/', '', $code);
        $scopeConfig = $this->_scopeConfig;
        $config ['SECURITY.SENDER'] = $scopeConfig->getValue($path . "security_sender", ScopeInterface::SCOPE_STORE, $storeId);

        if ($scopeConfig->getValue($path . "sandbox_mode", ScopeInterface::SCOPE_STORE, $storeId) == 0) {
            $config ['TRANSACTION.MODE'] = 'LIVE';
            $config ['URL'] = $this->_live_url;
        } else {
            $config ['TRANSACTION.MODE'] = 'CONNECTOR_TEST';
            $config ['URL'] = $this->_sandbox_url;
        }
        $config ['USER.LOGIN'] = trim($scopeConfig->getValue($path . "user_login", ScopeInterface::SCOPE_STORE, $storeId));
        $config ['USER.PWD'] = trim($scopeConfig->getValue($path . "user_passwd", ScopeInterface::SCOPE_STORE, $storeId));

        $path = 'payment/' . $code . '/';
        $config ['TRANSACTION.CHANNEL'] = trim($scopeConfig->getValue($path . "channel", ScopeInterface::SCOPE_STORE, $storeId));

        // ($this->_scopeConfig->getValue($path."bookingmode", $storeId) == true) ? $config['PAYMENT.TYPE'] = $this->_scopeConfig->getValue($path."bookingmode", $storeId) : false ;

        return $config;
    }

    /**
     * @param $orderNr
     * @return array
     */
    public function getFrontend($orderNr)
    {
        $resolver = $this->_localResolver;
        $langCode = explode('_', (string)$resolver->getLocale());
        $lang = strtoupper($langCode[0]);

        $urlBuilder = $this->urlBuilder;

        $encryptor = $this->_encryptor;

        return [
            'FRONTEND.LANGUAGE'     => $lang,
            'FRONTEND.RESPONSE_URL' => $urlBuilder->getUrl('hgw/index/response', [
                '_forced_secure' => true,
                '_store_to_url'  => true,
                '_nosid'         => true,
            ]),
            'CRITERION.PUSH_URL'    => $urlBuilder->getUrl('hgw/index/push', [
                '_forced_secure' => true,
                '_store_to_url'  => true,
                '_nosid'         => true,
            ]),                // PUSH proxy is only used for development purpose
            'CRITERION.SECRET'      => $encryptor->getHash($orderNr . $encryptor->exportKeys()),
            'CRITERION.LANGUAGE'    => (string)$resolver->getLocale(),
            'CRITERION.STOREID'     => $this->getStore(),
            'SHOP.TYPE'             => 'Magento 2.x',
            'SHOPMODULE.VERSION'    => 'Heidelpay Gateway - 16.10.7',
        ];
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    public function getUser($order)
    {
        $user = [];
        $billingAddress = $order->getBillingAddress();
        $email = ($billingAddress->getEmail()) ? $billingAddress->getEmail() : $order->getCustomerEmail();
        $customerId = $order->getCustomerId();
        $user['CRITERION.GUEST'] = 'false';

        if ($customerId == 0) {
            $user['CRITERION.GUEST'] = 'true';
        }

        $billingStreet = '';

        foreach ($billingAddress->getStreet() as $street) {
            $billingStreet .= $street . ' ';
        }

        $user['IDENTIFICATION.SHOPPERID'] = $customerId;

        if ($billingAddress->getCompany() == true) {
            $user['NAME.COMPANY'] = trim($billingAddress->getCompany());
        }

        $user['NAME.GIVEN'] = trim($billingAddress->getFirstname());
        $user['NAME.FAMILY'] = trim($billingAddress->getLastname());
        $user['ADDRESS.STREET'] = trim($billingStreet);
        $user['ADDRESS.ZIP'] = trim($billingAddress->getPostcode());
        $user['ADDRESS.CITY'] = trim($billingAddress->getCity());
        $user['ADDRESS.COUNTRY'] = trim($billingAddress->getCountryId());
        $user['CONTACT.EMAIL'] = trim($email);
        $request = $this->_requestHttp;
        $user['CONTACT.IP'] = (filter_var(trim($request->getClientIp(true)), FILTER_VALIDATE_IP)) ? trim($request->getClientIp(true)) : '127.0.0.1';

        return $user;
    }

    /**
     * @param DataObject $data
     * @return $this
     */
    public function assignData(DataObject $data)
    {
        $logger = $this->_logger;
        $logger->addDebug('payment methode hgw assignData');
        $infoInstance = $this->getInfoInstance();
        $paymentMethodNonce = $data->getPaymentMethodNonce();
        $infoInstance->setAdditionalInformation('payment_method_nonce', $paymentMethodNonce);

        return $this;
    }

    /**
     * @return $this
     */
    public function validate()
    {
        /**
         * @TODO removed unused variables and condition
         */
        $this->_logger->addDebug('payment methode hgw validate');

        return $this;
    }
}