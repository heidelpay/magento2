<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory as HeidelpayTransactionCollectionFactory;
use Heidelpay\PhpApi\PaymentMethods\InvoicePaymentMethod;

/**
 * Heidelpay prepayment payment method
 *
 * This is the payment class for heidelpay prepayment
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
class HeidelpayInvoicePaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     * @var string
     */
    const CODE = 'hgwiv';

    /**
     * Payment Code
     * @var string
     */
    protected $_code = 'hgwiv';

    /**
     * Info Block Class (used for Order/Invoice details)
     * @var string
     */
    protected $_infoBlockType = 'Heidelpay\Gateway\Block\Info\Invoice';

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
     * HeidelpayPrepaymentPaymentMethod constructor.
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
     * @param \Heidelpay\PhpApi\PaymentMethods\InvoicePaymentMethod $invoicePaymentMethod
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
        \Heidelpay\PhpApi\PaymentMethods\InvoicePaymentMethod $invoicePaymentMethod,
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

        $this->_heidelpayPaymentMethod = $invoicePaymentMethod;
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

    /**
     * @inheritdoc
     */
    public function additionalPaymentInformation($response)
    {
        return __(
            'Please transfer the amount of <strong>%1 %2</strong> '
            . 'to the following account after your order has arrived:<br /><br />'
            . 'Holder: %3<br/>IBAN: %4<br/>BIC: %5<br/><br/><i>'
            . 'Please use only this identification number as the descriptor :</i><br/><strong>%6</strong>',
            $this->_paymentHelper->format($response['PRESENTATION_AMOUNT']),
            $response['PRESENTATION_CURRENCY'],
            $response['CONNECTOR_ACCOUNT_HOLDER'],
            $response['CONNECTOR_ACCOUNT_IBAN'],
            $response['CONNECTOR_ACCOUNT_BIC'],
            $response['IDENTIFICATION_SHORTID']
        );
    }

    /**
     * @inheritdoc
     */
    public function pendingTransactionProcessing($data, &$order, $message = null)
    {
        $order->getPayment()->setTransactionId($data['IDENTIFICATION_UNIQUEID']);
        $order->getPayment()->setIsTransactionClosed(false);
        $order->getPayment()->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true);

        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
            ->addStatusHistoryComment($message, \Magento\Sales\Model\Order::STATE_PROCESSING)
            ->setIsCustomerNotified(true);

        // payment is pending at the beginning, so we set the total paid sum to 0.
        $order->setTotalPaid(0.00)->setBaseTotalPaid(0.00);

        // if the order can be invoiced, create one and save it into a transaction.
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE)
                ->setTransactionId($data['IDENTIFICATION_UNIQUEID'])
                ->setIsPaid(false)
                ->register();

            $this->_paymentHelper->saveTransaction($invoice);
        }
    }
}
