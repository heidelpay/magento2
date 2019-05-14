<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Gateway\Config\HgwBasePaymentConfigInterface;
use Heidelpay\Gateway\Gateway\Config\HgwMainConfigInterface;
use Heidelpay\Gateway\Model\Config\Source\BookingMode;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory as HeidelpayTransactionCollectionFactory;
use Heidelpay\PhpPaymentApi\ParameterGroups\BasketParameterGroup;
use Heidelpay\PhpPaymentApi\PaymentMethods\AbstractPaymentMethod;
use Heidelpay\PhpPaymentApi\Response;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Heidelpay\Gateway\Block\Payment\HgwAbstract;
use Heidelpay\PhpPaymentApi\PaymentMethods\PaymentMethodInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Heidelpay  abstract payment method
 *
 * All Heidelpay payment methods will extend this abstract payment method
 *
 * @license    Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright  Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link       http://dev.heidelpay.com/magento2
 * @author     Jens Richter
 *
 * @package    heidelpay
 * @subpackage magento2
 * @category   magento2
 */
class HeidelpayAbstractPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * PaymentCode
     *
     * @var string
     */
    const CODE = 'hgwabstract';

    /**
     * PaymentCode
     *
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var boolean
     */
    protected $_isGateway = true;

    /**
     * @var boolean
     */
    protected $_canCapture = false;

    /**
     * @var boolean
     */
    protected $_canCapturePartial = false;

    /**
     * @var boolean
     */
    protected $_canRefund = false;

    /**
     * @var boolean
     */
    protected $_canRefundInvoicePartial = false;

    /**
     * @var boolean
     */
    protected $_canUseInternal = false;

    /**
     * @var boolean
     */
    private $usingBasketApi = false;

    /**
     * @var string
     */
    protected $_formBlockType = HgwAbstract::class;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_requestHttp;

    /**
     * @var \Heidelpay\Gateway\Helper\Payment
     */
    protected $_paymentHelper;

    /**
     * @var \Heidelpay\Gateway\Helper\BasketHelper
     */
    protected $basketHelper;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localResolver;

    /**
     * The used heidelpay payment method
     *
     * @var PaymentMethodInterface $_heidelpayPaymentMethod
     */
    protected $_heidelpayPaymentMethod;

    /**
     * @var \Magento\Payment\Model\Method\Logger
     */
    protected $logger;

    /**
     * Encryption & Hashing
     *
     * @var \Magento\Framework\Encryption\Encryptor
     */
    protected $_encryptor;

    /**
     * Product Metadata to receive Magento information
     *
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /** @var \Magento\Sales\Helper\Data */
    protected $salesHelper;

    /**
     * Resource information about modules
     *
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $moduleResource;

    /**
     * Factory for heidelpay payment information
     *
     * @var \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory
     */
    protected $paymentInformationCollectionFactory;

    /**
     * @var \Heidelpay\Gateway\Model\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var HeidelpayTransactionCollectionFactory
     */
    protected $transactionCollectionFactory;

    /**
     * @var HgwMainConfigInterface
     */
    protected $mainConfig;

    /**
     * @var HgwBasePaymentConfigInterface
     */
    private $paymentConfig;

    /**
     * heidelpay Abstract Payment method constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param HgwMainConfigInterface $mainConfig
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\UrlInterface $urlinterface
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface $moduleResource
     * @param \Heidelpay\Gateway\Helper\Payment $paymentHelper
     * @param \Heidelpay\Gateway\Helper\BasketHelper $basketHelper
     * @param \Magento\Sales\Helper\Data $salesHelper
     * @param PaymentInformationCollectionFactory $paymentInformationCollectionFactory
     * @param \Heidelpay\Gateway\Model\TransactionFactory $transactionFactory
     * @param HeidelpayTransactionCollectionFactory $transactionCollectionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param HgwBasePaymentConfigInterface $paymentConfig
     * @param array $data
     */
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
        \Heidelpay\Gateway\Helper\Payment $paymentHelper,
        \Heidelpay\Gateway\Helper\BasketHelper $basketHelper,
        \Magento\Sales\Helper\Data $salesHelper,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        \Heidelpay\Gateway\Model\TransactionFactory $transactionFactory,
        HeidelpayTransactionCollectionFactory $transactionCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        $paymentConfig = null /* do not add Type or this wont work */,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $mainConfig->getScopeConfig(),
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->urlBuilder = $urlinterface;
        $this->logger = $logger;
        $this->_requestHttp = $request;
        $this->_paymentHelper = $paymentHelper;
        $this->salesHelper = $salesHelper;
        $this->basketHelper = $basketHelper;

        $this->_encryptor = $encryptor;
        $this->_localResolver = $localeResolver;
        $this->productMetadata = $productMetadata;
        $this->moduleResource = $moduleResource;

        $this->paymentInformationCollectionFactory = $paymentInformationCollectionFactory;
        $this->transactionFactory = $transactionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->mainConfig = $mainConfig;
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * Active redirect
     *
     * This function will return false, if the used payment method needs additional
     * customer payment data to pursue.
     * @return boolean
     */

    public function activeRedirect()
    {
        return true;
    }

    /**
     * override getConfig to change the configuration path
     *
     * @param string  $field
     * @param integer $storeId
     *
     * @return string config value
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfigData($field, $storeId = null)
    {
        // in order to avoid the order mail to be sent twice,
        // once by us and once by the SubmitObserver.
        if ($field === 'order_place_redirect_url') {
            return 'dummy_redirect_url';
        }

        $path = 'payment/' . $this->getCode() . '/' . $field;

        return $this->_scopeConfig->getValue($path, StoreScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @inheritdoc
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }

        // skip the bottom part, if the booking mode is not authorization.
        if ($this->getBookingMode() !== BookingMode::AUTHORIZATION) {
            return $this;
        }

        // create the transactioncollection factory to get the parent authorization.
        $factory = $this->transactionCollectionFactory->create();
        /** @var \Heidelpay\Gateway\Model\Transaction $transactionInfo */
        $transactionInfo = $factory->loadByTransactionId($payment->getParentTransactionId());

        // if there is no heidelpay transaction, something went wrong.
        if ($transactionInfo === null || $transactionInfo->isEmpty()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('heidelpay - No transaction data available'));
        }

        // we can only Capture on Pre-Authorization payment types.
        // so is the payment type of this Transaction is no PA, we won't capture anything.
        if ($transactionInfo->getPaymentType() !== 'PA') {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('heidelpay - Cannot capture this transaction.')
            );
        }

        // set authentification data
        $this->performAuthentication();

        // set basket data
        $this->_heidelpayPaymentMethod->getRequest()->basketData(
            $payment->getOrder()->getQuoteId(),
            $this->_paymentHelper->format($amount),
            $payment->getOrder()->getOrderCurrencyCode(),
            $this->_encryptor->exportKeys()
        );

        // send the capture request
        $this->_heidelpayPaymentMethod->capture($transactionInfo->getUniqueId());

        $this->_logger->debug(
            'heidelpay - Capture Response: ' . print_r($this->_heidelpayPaymentMethod->getResponse(), 1)
        );

        // if the heidelpay Request wasn't successful, throw an Exception with the heidelpay message
        if (!$this->_heidelpayPaymentMethod->getResponse()->isSuccess()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('heidelpay - ' . $this->_heidelpayPaymentMethod->getResponse()->getProcessing()->getReturn())
            );
        }

        list($paymentMethod, $paymentType) = $this->_paymentHelper->splitPaymentCode(
            $this->_heidelpayPaymentMethod->getResponse()->getPayment()->getCode()
        );

        // Create a new heidelpay Transaction
        $this->saveHeidelpayTransaction(
            $this->_heidelpayPaymentMethod->getResponse(),
            $paymentMethod,
            $paymentType,
            'RESPONSE',
            []
        );

        // create a child transaction.
        $payment->setTransactionId($this->_heidelpayPaymentMethod->getResponse()->getPaymentReferenceId());
        $payment->setParentTransactionId($transactionInfo->getUniqueId());
        $payment->setIsTransactionClosed(true);
        $payment->addTransaction(TransactionInterface::TYPE_CAPTURE, null, true);

        // set the last transaction id to the Pre-Authorization.
        $payment->setLastTransId($this->_heidelpayPaymentMethod->getResponse()->getPaymentReferenceId());
        $payment->save();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }

        // create the transactioncollection to get the parent authorization.
        $collection = $this->transactionCollectionFactory->create();

        /** @var \Heidelpay\Gateway\Model\Transaction $transactionInfo */
        $transactionInfo = $collection->loadByTransactionId($payment->getParentTransactionId());

        // if there is no heidelpay transaction, something went wrong.
        if ($transactionInfo === null || $transactionInfo->isEmpty()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('heidelpay - No transaction data available'));
        }

        // we can only refund on transaction where money has been credited/debited
        // so is the payment type of this Transaction none of them, we won't refund anything.
        if (!$this->_paymentHelper->isRefundable($transactionInfo->getPaymentType())) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('heidelpay - Cannot refund this transaction.')
            );
        }

        // set authentication data
        $this->performAuthentication();

        // set basket data
        $this->_heidelpayPaymentMethod->getRequest()->basketData(
            $payment->getOrder()->getQuoteId(),
            $this->_paymentHelper->format($amount),
            $payment->getOrder()->getOrderCurrencyCode(),
            $this->_encryptor->exportKeys()
        );

        // send the refund request
        $this->_heidelpayPaymentMethod->refund($transactionInfo->getUniqueId());

        $this->_logger->debug(
            'heidelpay - Refund Response: ' . print_r($this->_heidelpayPaymentMethod->getResponse(), 1)
        );

        // if the heidelpay Request wasn't successful, throw an Exception with the heidelpay message
        if (!$this->_heidelpayPaymentMethod->getResponse()->isSuccess()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('heidelpay - ' . $this->_heidelpayPaymentMethod->getResponse()->getProcessing()->getReturn())
            );
        }

        list($paymentMethod, $paymentType) = $this->_paymentHelper->splitPaymentCode(
            $this->_heidelpayPaymentMethod->getResponse()->getPayment()->getCode()
        );

        // Create a new heidelpay Transaction
        $this->saveHeidelpayTransaction(
            $this->_heidelpayPaymentMethod->getResponse(),
            $paymentMethod,
            $paymentType,
            'RESPONSE',
            []
        );

        // create a child transaction.
        $payment->setTransactionId($this->_heidelpayPaymentMethod->getResponse()->getPaymentReferenceId());
        $payment->setParentTransactionId($transactionInfo->getUniqueId());
        $payment->setIsTransactionClosed(true);
        $payment->addTransaction(TransactionInterface::TYPE_REFUND, null, true);

        // set the last transaction id to the Pre-Authorization.
        $payment->setLastTransId($this->_heidelpayPaymentMethod->getResponse()->getPaymentReferenceId());
        $payment->save();

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @throws \Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function getHeidelpayUrl($quote)
    {
        $this->setupInitialRequest();

        $user = $this->getUser($quote);
        $this->_heidelpayPaymentMethod->getRequest()->customerAddress(
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

        $this->_heidelpayPaymentMethod->getRequest()->getCriterion()
            ->set('guest', $user['CRITERION.GUEST']);

        $this->_heidelpayPaymentMethod->getRequest()->basketData(
            $quote->getId(),                                        // Reference Id of your application
            $this->_paymentHelper->format($quote->getGrandTotal()), // Amount of this request
            $quote->getQuoteCurrencyCode(),                         // Currency code of this request
            $this->_encryptor->exportKeys()                         // A secret passphrase from your application
        );

        // add the customer ip address
        $this->_heidelpayPaymentMethod->getRequest()->getContact()->set('ip', $quote->getRemoteIp());

        // add the Magento Version to the heidelpay request
        $this->_heidelpayPaymentMethod->getRequest()->getCriterion()->set(
            'SHOP.TYPE',
            $this->productMetadata->getName() . ' ' . $this->productMetadata->getVersion() . '-' .
            $this->productMetadata->getEdition()
        );

        // add the module version to the heidelpay request
        $this->_heidelpayPaymentMethod->getRequest()->getCriterion()->set(
            'SHOPMODULE.VERSION',
            'Heidelpay Gateway ' . $this->moduleResource->getDataVersion('Heidelpay_Gateway')
        );

        // add a push url to the criterion object for future push responses from heidelpay
        $this->_heidelpayPaymentMethod->getRequest()->getCriterion()->set(
            'PUSH_URL',
            $this->urlBuilder->getUrl('hgw/index/push', [
                '_forced_secure' => true,
                '_scope_to_url' => true,
                '_nosid' => true
            ])
        );

        // submit the Quote to the Basket API if the payment method needs one.
        if ($this->isUsingBasketApi()) {
            $basketId = $this->basketHelper->submitQuoteToBasketApi($quote);

            if ($basketId === null) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Error!'));
            }

            $this->_logger->debug('Heidelpay: New basket id is ' . $basketId);

            /** @var BasketParameterGroup $basketParameterGroup */
            $basketParameterGroup = $this->_heidelpayPaymentMethod->getRequest()->getBasket();
            $basketParameterGroup->set('id', $basketId);
        }
    }

    /**
     * @param Response $response
     * @param $paymentMethod
     * @param $paymentType
     * @param string $source
     * @param array $data
     */
    public function saveHeidelpayTransaction(Response $response, $paymentMethod, $paymentType, $source, array $data)
    {

        $transaction = $this->transactionFactory->create();
        $transaction->setPaymentMethod($paymentMethod)
            ->setPaymentType($paymentType)
            ->setTransactionId($response->getIdentification()->getTransactionId())
            ->setUniqueId($response->getIdentification()->getUniqueId())
            ->setShortId($response->getIdentification()->getShortId())
            ->setStatusCode($response->getProcessing()->getStatusCode())
            ->setResult($response->getProcessing()->getResult())
            ->setReturnMessage($response->getProcessing()->getReturn())
            ->setReturnCode($response->getProcessing()->getReturnCode())
            ->setJsonResponse(json_encode($data))
            ->setSource($source)
            ->save();
    }

    /**
     * Returns the heidelpay PhpPaymentApi Paymentmethod Instance.
     *
     * @return AbstractPaymentMethod|PaymentMethodInterface
     */
    public function getHeidelpayPaymentMethodInstance()
    {
        return $this->_heidelpayPaymentMethod;
    }

    /**
     * returns shop language and response url
     *
     * @return array shop language and response url
     */
    public function getFrontend()
    {
        $langCode = explode('_', (string)$this->_localResolver->getLocale());
        $lang = strtoupper($langCode[0]);

        return [
            'LANGUAGE' => $lang,
            'RESPONSE_URL' => $this->urlBuilder->getUrl('hgw/index/response', [
                '_forced_secure' => true,
                '_scope_to_url' => true,
                '_nosid' => true
            ]),
        ];
    }

    /**
     * extract customer information from magento order object
     *
     * @param  \Magento\Quote\Api\Data\CartInterface $order object
     *
     * @return array customer information
     */
    public function getUser($order)
    {
        $user = [];
        $billing = $order->getBillingAddress();
        $email = $order->getBillingAddress()->getEmail();

        $billingStreet = '';

        foreach ($billing->getStreet() as $street) {
            $billingStreet .= $street . ' ';
        }

        $user['CRITERION.GUEST'] = $order->getCustomer()->getId() == 0 ? 'true' : 'false';

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
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string|null                $message
     */
    public function cancelledTransactionProcessing(&$order, $message = null)
    {
        if ($order->canCancel()) {
            $order->cancel()
                ->setState(Order::STATE_CANCELED)
                ->addStatusHistoryComment('heidelpay - ' . $message, Order::STATE_CANCELED)
                ->setIsCustomerNotified(false);
        }
    }

    /**
     *
     * @param array                      $data
     * @param \Magento\Sales\Model\Order $order
     * @param string|null                $message
     */
    public function pendingTransactionProcessing($data, &$order, $message = null)
    {
        $order->getPayment()->setTransactionId($data['IDENTIFICATION_UNIQUEID']);
        $order->getPayment()->setIsTransactionClosed(false);
        $order->getPayment()->addTransaction(TransactionInterface::TYPE_AUTH, null, true);

        $order->setState(Order::STATE_PENDING_PAYMENT)
            ->addStatusHistoryComment('heidelpay - ' . $message, Order::STATE_PENDING_PAYMENT)
            ->setIsCustomerNotified(true);
    }

    /**
     * @param array $data
     * @param \Magento\Sales\Model\Order $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processingTransactionProcessing($data, &$order)
    {
        $message = __('ShortId : %1', $data['IDENTIFICATION_SHORTID']);

        $order->getPayment()
            ->setTransactionId($data['IDENTIFICATION_UNIQUEID'])
            ->setParentTransactionId($order->getPayment()->getLastTransId())
            ->setIsTransactionClosed(true);

        // if the total sum of the order matches the presentation amount of the heidelpay response...
        if ($this->_paymentHelper->isMatchingAmount($order, $data)
            && $this->_paymentHelper->isMatchingCurrency($order, $data)
        ) {
            $order->setState(Order::STATE_PROCESSING)
                ->addStatusHistoryComment('heidelpay - ' . $message, Order::STATE_PROCESSING)
                ->setIsCustomerNotified(true);
        } else {
            // in case rc is ack and amount is to low/heigh or curreny missmatch
            $message = __(
                'Amount or currency missmatch : %1',
                $data['PRESENTATION_AMOUNT'] . ' ' . $data['PRESENTATION_CURRENCY']
            );

            $order->setState(Order::STATE_PAYMENT_REVIEW)
                ->addStatusHistoryComment('heidelpay - ' . $message, Order::STATE_PAYMENT_REVIEW)
                ->setIsCustomerNotified(true);
        }

        // if the order can be invoiced and is no Pre-Authorization,
        // create one and save it into a transaction.
        if ($order->canInvoice() && !$this->_paymentHelper->isPreAuthorization($data)) {
            $invoice = $order->prepareInvoice();

            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->setTransactionId($data['IDENTIFICATION_UNIQUEID']);
            $invoice->register()->pay();

            $this->_paymentHelper->saveTransaction($invoice);
        }

        $order->getPayment()->addTransaction(TransactionInterface::TYPE_CAPTURE, null, true);
    }

    /**
     * Returns the booking mode.
     * This is needed for payment methods with multiple booking modes like 'debit' or 'preauthorization' and 'capture'.
     *
     * @param int $storeId
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBookingMode($storeId = null)
    {
        $store = $storeId === null ? $this->getStore() : $storeId;

        return $this->_scopeConfig->getValue(
            'payment/' . $this->getCode() . '/bookingmode',
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Additional payment information
     *
     * This function will return a text message used to show payment information
     * to your customer on the checkout success page
     *
     * @param array $response
     *
     * @return \Magento\Framework\Phrase|null
     */
    public function additionalPaymentInformation($response)
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isUsingBasketApi()
    {
        return $this->usingBasketApi;
    }

    /**
     * @param bool $usingBasketApi
     */
    public function setUsingBasketApi($usingBasketApi)
    {
        $this->usingBasketApi = $usingBasketApi;
    }

    /**
     * Set request authentication
     */
    private function performAuthentication()
    {
        $this->_heidelpayPaymentMethod->getRequest()->authentification(
            $this->mainConfig->getSecuritySender(),
            $this->mainConfig->getUserLogin(),
            $this->mainConfig->getUserPasswd(),
            $this->paymentConfig->getChannel(),
            $this->mainConfig->isSandboxModeActive()
        );
    }

    /**
     * Function to provide additional form data.
     * Should be overwritten by child classes if needed.
     * @param Response $response
     * @return array
     */
    public function prepareAdditionalFormData(Response $response)
    {
        return [];
    }

    /*
     * Setup initialreques without customer data.
     */
    public function setupInitialRequest()
    {
        $this->performAuthentication();
        $this->setAsync();
    }

    /**
     * will return the main configuration
     *
     * @return HgwMainConfigInterface
     */
    public function getMainConfig()
    {
        return $this->mainConfig;
    }

    /**
     * will return the payment config
     *
     * @return HgwBasePaymentConfigInterface
     */
    public function getConfig()
    {
        return $this->paymentConfig;
    }

    /**
     * Set the parameter for async modus.
     */
    public function setAsync()
    {
        $frontend = $this->getFrontend();

        $this->_heidelpayPaymentMethod->getRequest()->async(
            $frontend['LANGUAGE'],                 // Language code for the Frame
            $frontend['RESPONSE_URL']              // Response url from your application
        );
    }

    /**
     * @param $transactionID string
     * @return bool
     */
    public function heidelpayTransactionExists($transactionID)
    {
        $collection = $this->transactionCollectionFactory->create();

        /** @var \Heidelpay\Gateway\Model\Transaction $heidelpayTransaction */
        $heidelpayTransaction = $collection->loadByTransactionId($transactionID);

        return !$heidelpayTransaction === null && !$heidelpayTransaction->isEmpty();
    }
}
