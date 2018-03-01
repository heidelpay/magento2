<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory as HeidelpayTransactionCollectionFactory;

/**
 * heidelpay sofort payment method
 *
 * This is the payment class for heidelpay sofort
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link http://dev.heidelpay.com/magento2
 * @author Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class HeidelpaySofortPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     * @var string PayentCode
     */
    const CODE = 'hgwsue';

    /**
     * Payment Code
     * @var string PayentCode
     */
    protected $_code = 'hgwsue';

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
     * @var boolean
     */
    protected $_canRefund = true;

    /**
     * @var boolean
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * HeidelpaySofortPaymentMethod constructor.
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
     * @param \Magento\Sales\Helper\Data $salesHelper
     * @param PaymentInformationCollectionFactory $paymentInformationCollectionFactory
     * @param \Heidelpay\Gateway\Model\TransactionFactory $transactionFactory
     * @param HeidelpayTransactionCollectionFactory $transactionCollectionFactory
     * @param \Heidelpay\PhpApi\PaymentMethods\SofortPaymentMethod $sofortPaymentMethod
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
        \Magento\Sales\Helper\Data $salesHelper,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        \Heidelpay\Gateway\Model\TransactionFactory $transactionFactory,
        HeidelpayTransactionCollectionFactory $transactionCollectionFactory,
        \Heidelpay\PhpApi\PaymentMethods\SofortPaymentMethod $sofortPaymentMethod,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $request,
            $urlinterface,
            $encryptor,
            $logger,
            $localeResolver,
            $productMetadata,
            $moduleResource,
            $paymentHelper,
            $salesHelper,
            $paymentInformationCollectionFactory,
            $transactionFactory,
            $transactionCollectionFactory,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_heidelpayPaymentMethod = $sofortPaymentMethod;
    }

    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote)
    {
        // set initial data for the request
        parent::getHeidelpayUrl($quote);

        // send the authorize request
        $this->_heidelpayPaymentMethod->authorize();

        return $this->_heidelpayPaymentMethod->getResponse();
    }
}
