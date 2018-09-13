<?php
/**
 * Created by PhpStorm.
 * User: David.Owusu
 * Date: 04.09.2018
 * Time: 13:54
 */

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Gateway\Config\HgwMainConfigInterface;
use Heidelpay\Gateway\Gateway\Config\HgwIDealPaymentConfigInterface;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory as HeidelpayTransactionCollectionFactory;

class HeidelpayIDealPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    const CODE = 'hgwidl';

    protected $_code = self::CODE;

    protected $_canAuthorize = true;

    protected $_isGateway = true;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        HgwMainConfigInterface $mainConfig,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlinterface,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        HgwIDealPaymentConfigInterface $paymentConfig,
        \Heidelpay\Gateway\Helper\Payment $paymentHelper,
        \Heidelpay\Gateway\Helper\BasketHelper $basketHelper,
        \Magento\Sales\Helper\Data $salesHelper,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        \Heidelpay\Gateway\Model\TransactionFactory $transactionFactory,
        HeidelpayTransactionCollectionFactory $transactionCollectionFactory,
        \Heidelpay\PhpPaymentApi\PaymentMethods\IDealPaymentMethod $iDealPaymentMethod,
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

        $this->_heidelpayPaymentMethod = $iDealPaymentMethod;
    }

    public function getHeidelpayUrl($quote)
    {
        // create the collection factory
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load the payment information by store id, customer email address and payment method
        /** @var \Heidelpay\Gateway\Model\PaymentInformation $paymentInfo */
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
            $quote->getStoreId(),
            $quote->getBillingAddress()->getEmail(),
            $quote->getPayment()->getMethod()
        );

        // set some parameters inside the Abstract Payment method helper which are used for all requests,
        // e.g. authentication, customer data, ...
        parent::getHeidelpayUrl($quote);

        // add Bank selection to the request.
        if (isset($paymentInfo->getAdditionalData()->hgw_bank_name)) {
            $this->_heidelpayPaymentMethod
                ->getRequest()->getAccount()
                ->setBankName($paymentInfo->getAdditionalData()->hgw_bank_name);
        }

        if (isset($paymentInfo->getAdditionalData()->hgw_holder)) {
            $this->_heidelpayPaymentMethod
                ->getRequest()->getAccount()
                ->setHolder($paymentInfo->getAdditionalData()->hgw_holder);
        }

        // send the init request with the debit method.
        $this->_heidelpayPaymentMethod->authorize();

        // return the response object
        return $this->_heidelpayPaymentMethod->getResponse();
    }

    public function activeRedirect()
    {
        return true;
    }

    public function initMethod()
    {
        $this->setInitialRequest();
        //$this->_heidelpayPaymentMethod->getRequest()->getFrontend()->setEnabled('FALSE');

        return $this->_heidelpayPaymentMethod->authorize()->getResponse();
    }
}
