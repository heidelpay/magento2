<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;

/**
 * Heidelpay dibit card payment method
 *
 * This is the payment class for heidelpay debit card
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/magento
 * @author Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class HeidelpayDebitCardPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     * @var string PayentCode
     */
    const CODE = 'hgwdc';

    /**
     * Payment Code
     * @var string PayentCode
     */
    protected $_code = 'hgwdc';

    /**
     * isGateway
     * @var boolean
     */
    protected $_isGateway = true;

    /**
     * canAuthorize
     * @var boolean
     */
    protected $_canAuthorize = true;

    /**
     * HeidelpayDebitCardPaymentMethod constructor.
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
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface $moduleResource
     * @param \Heidelpay\Gateway\Helper\Payment $paymentHelper
     * @param PaymentInformationCollectionFactory $paymentInformationCollectionFactory
     * @param \Heidelpay\PhpApi\PaymentMethods\DebitCardPaymentMethod $debitCardPaymentMethod
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
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
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Heidelpay\Gateway\Helper\Payment $paymentHelper,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        \Heidelpay\PhpApi\PaymentMethods\DebitCardPaymentMethod $debitCardPaymentMethod,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig,
            $request, $urlinterface, $encryptor, $logger, $localeResolver, $productMetadata, $moduleResource,
            $paymentHelper, $paymentInformationCollectionFactory, $resource, $resourceCollection, $data);

        $this->_heidelpayPaymentMethod = $debitCardPaymentMethod;
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
        return false;
    }

    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote)
    {
        parent::getHeidelpayUrl($quote);

        // Force PhpApi to just generate the request instead of sending it directly
        $this->_heidelpayPaymentMethod->_dryRun = true;

        $url = explode('/', $this->urlBuilder->getUrl('/', ['_secure' => true]));
        $paymentFrameOrigin = $url[0] . '//' . $url[2];
        $preventAsyncRedirect = 'FALSE';
        $cssPath = $this->_scopeConfig->getValue(
            "payment/hgwmain/default_css",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()
        );

        // Set payment type to debit
        $this->_heidelpayPaymentMethod->debit($paymentFrameOrigin, $preventAsyncRedirect, $cssPath);

        // Prepare and send request to heidelpay
        $response = $this->_heidelpayPaymentMethod->getRequest()->send(
            $this->_heidelpayPaymentMethod->getPaymentUrl(),
            $this->_heidelpayPaymentMethod->getRequest()->convertToArray()
        );

        return $response[0];
    }
}
