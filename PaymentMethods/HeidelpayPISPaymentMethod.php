<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Gateway\Config\HgwBasePaymentConfigInterface;
use Heidelpay\Gateway\Gateway\Config\HgwMainConfigInterface;
use Heidelpay\Gateway\Gateway\Config\HgwPISPaymentConfigInterface;
use Heidelpay\Gateway\Helper\BasketHelper;
use Heidelpay\Gateway\Helper\Payment;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory as HeidelpayTransactionCollectionFactory;
use Heidelpay\Gateway\Model\TransactionFactory;
use Heidelpay\PhpPaymentApi\PaymentMethods\PISPaymentMethod;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ResourceInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Helper\Data as SalesData;

/**
 * heidelpay PIS payment method
 *
 * This is the payment class for heidelpay PIS
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
class HeidelpayPISPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     * @var string PayentCode
     */
    const CODE = 'hgwpis';

    /**
     * Payment Code
     * @var string PayentCode
     */
    protected $_code = self::CODE;

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
     * HeidelpayPISPaymentMethod constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param HgwMainConfigInterface $mainConfig
     * @param RequestInterface $request
     * @param UrlInterface $urlinterface
     * @param Encryptor $encryptor
     * @param Logger $logger
     * @param ResolverInterface $localeResolver
     * @param ProductMetadataInterface $productMetadata
     * @param ResourceInterface $moduleResource
     * @param HgwPISPaymentConfigInterface $paymentConfig
     * @param Payment $paymentHelper
     * @param BasketHelper $basketHelper
     * @param SalesData $salesHelper
     * @param PaymentInformationCollectionFactory $paymentInformationCollectionFactory
     * @param TransactionFactory $transactionFactory
     * @param HeidelpayTransactionCollectionFactory $transactionCollectionFactory
     * @param PISPaymentMethod $PISPaymentMethod
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
        HgwMainConfigInterface $mainConfig,
        RequestInterface $request,
        UrlInterface $urlinterface,
        Encryptor $encryptor,
        Logger $logger,
        ResolverInterface $localeResolver,
        ProductMetadataInterface $productMetadata,
        ResourceInterface $moduleResource,
        HgwBasePaymentConfigInterface $paymentConfig,
        Payment $paymentHelper,
        BasketHelper $basketHelper,
        SalesData $salesHelper,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        TransactionFactory $transactionFactory,
        HeidelpayTransactionCollectionFactory $transactionCollectionFactory,
        PISPaymentMethod $PISPaymentMethod,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $mainConfig,
            $request,
            $urlinterface,
            $encryptor,
            $logger,
            $localeResolver,
            $productMetadata,
            $moduleResource,
            $paymentHelper,
            $basketHelper,
            $salesHelper,
            $paymentInformationCollectionFactory,
            $transactionFactory,
            $transactionCollectionFactory,
            $resource,
            $resourceCollection,
            $paymentConfig,
            $data
        );

        $this->_heidelpayPaymentMethod = $PISPaymentMethod;
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
