<?php
/**
 * This is the payment class for heidelpay santander hire purchase payment method.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/magento2
 *
 * @author Simon Gabriel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Gateway\Config\HgwMainConfigInterface;
use Heidelpay\Gateway\Gateway\Config\HgwSantanderHirePurchasePaymentConfigInterface;
use Heidelpay\Gateway\Helper\BasketHelper;
use Heidelpay\Gateway\Helper\Payment;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory as HeidelpayTransactionCollectionFactory;
use Heidelpay\Gateway\Model\TransactionFactory;
use Heidelpay\PhpPaymentApi\PaymentMethods\SantanderHirePurchasePaymentMethod;
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

class HeidelpaySantanderHirePurchasePaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string PaymentCode */
    const CODE = 'hgwsanhp';

    /** @var string PaymentCode */
    protected $_code = self::CODE;

    /** @var boolean $_isGateway */
    protected $_isGateway = true;

    /** @var boolean $_canAuthorize */
    protected $_canAuthorize = true;

    /** @var boolean $_canRefund */
    protected $_canRefund = true;

    /** @var boolean $_canRefundInvoicePartial */
    protected $_canRefundInvoicePartial = true;

    /**
     * @param Context                                        $context
     * @param Registry                                       $registry
     * @param ExtensionAttributesFactory                     $extensionFactory
     * @param AttributeValueFactory                          $customAttributeFactory
     * @param Data                                           $paymentData
     * @param HgwMainConfigInterface                         $mainConfig
     * @param RequestInterface                               $request
     * @param UrlInterface                                   $urlinterface
     * @param Encryptor                                      $encryptor
     * @param Logger                                         $logger
     * @param ResolverInterface                              $localeResolver
     * @param ProductMetadataInterface                       $productMetadata
     * @param ResourceInterface                              $moduleResource
     * @param HgwSantanderHirePurchasePaymentConfigInterface $paymentConfig
     * @param Payment                                        $paymentHelper
     * @param BasketHelper                                   $basketHelper
     * @param \Magento\Sales\Helper\Data                     $salesHelper
     * @param PaymentInformationCollectionFactory            $paymentInformationCollectionFactory
     * @param TransactionFactory                             $transactionFactory
     * @param HeidelpayTransactionCollectionFactory          $transactionCollectionFactory
     * @param SantanderHirePurchasePaymentMethod             $paymentMethod
     * @param AbstractResource|null                          $resource
     * @param AbstractDb|null                                $resourceCollection
     * @param array                                          $data
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
        HgwSantanderHirePurchasePaymentConfigInterface $paymentConfig,
        Payment $paymentHelper,
        BasketHelper $basketHelper,
        \Magento\Sales\Helper\Data $salesHelper,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        TransactionFactory $transactionFactory,
        HeidelpayTransactionCollectionFactory $transactionCollectionFactory,
        SantanderHirePurchasePaymentMethod $paymentMethod,
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

        $this->_heidelpayPaymentMethod = $paymentMethod;
    }

    /**
     * Initial Request to heidelpay payment server to get the form url
     * {@inheritDoc}
     *
     * @see \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
    public function getHeidelpayUrl($quote)
    {
        // set initial data for the request
        parent::getHeidelpayUrl($quote);

        // send the authorize request
        $this->_heidelpayPaymentMethod->initialize();

        return $this->_heidelpayPaymentMethod->getResponse();
    }
}
