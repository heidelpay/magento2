<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;

/**
 * heidelpay giropay Payment Method
 *
 * heidelpay Payment Method for giropay.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/magento2
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class HeidelpayGiropayPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string heidelpay Gateway Paymentcode */
    protected $_code = 'hgwgp';

    /** @var bool */
    protected $_canAuthorize = true;

    /**
     * HeidelpayGiropayPaymentMethod constructor.
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
     * @param \Heidelpay\PhpApi\PaymentMethods\GiropayPaymentMethod $giropayPaymentMethod
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
        \Heidelpay\PhpApi\PaymentMethods\GiropayPaymentMethod $giropayPaymentMethod,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig,
            $request, $urlinterface, $encryptor, $logger, $localeResolver, $productMetadata, $moduleResource,
            $paymentHelper, $paymentInformationCollectionFactory, $resource, $resourceCollection, $data);

        $this->_heidelpayPaymentMethod = $giropayPaymentMethod;
    }

    /**
     * Returns the redirect url to the giropay site.
     *
     * @param $quote
     * @return array $response An array with heidelpay processing results
     */
    public function getHeidelpayUrl($quote)
    {
        parent::getHeidelpayUrl($quote);

        // force PhpApi to just generate the request instead of sending it directly
        $this->_heidelpayPaymentMethod->_dryRun = true;

        // set payment type to debit
        $this->_heidelpayPaymentMethod->authorize();

        // pepare and send request to heidelpay
        $response = $this->_heidelpayPaymentMethod->getRequest()->send(
            $this->_heidelpayPaymentMethod->getPaymentUrl(),
            $this->_heidelpayPaymentMethod->getRequest()->convertToArray()
        );

        return $response[0];
    }
}
