<?php

namespace Heidelpay\Gateway\PaymentMethods;

use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;

/**
 * Heidelpay Direct Debit
 *
 * The heidelpay Direct Debit payment method.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class HeidelpayDirectDebitPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /** @var string heidelpay Gateway Paymentcode */
    protected $_code = 'hgwdd';

    /** @var bool */
    protected $_canAuthorize = true;

    /**
     * HeidelpayDirectDebitPaymentMethod constructor.
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
     * @param \Heidelpay\PhpApi\PaymentMethods\DirectDebitPaymentMethod $directDebitPaymentMethod
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
        \Heidelpay\PhpApi\PaymentMethods\DirectDebitPaymentMethod $directDebitPaymentMethod,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig,
            $request, $urlinterface, $encryptor, $logger, $localeResolver, $productMetadata, $moduleResource,
            $paymentHelper, $paymentInformationCollectionFactory, $resource, $resourceCollection, $data);

        // initialize the Direct Debit payment method
        $this->_heidelpayPaymentMethod = $directDebitPaymentMethod;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return mixed
     */
    public function getHeidelpayUrl($quote)
    {
        // create the collection factory
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load the payment information by store id, customer email address and payment method
        /** @var \Heidelpay\Gateway\Model\PaymentInformation $paymentInfo */
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation($quote);

        // make an initial request to the heidelpay payment.
        parent::getHeidelpayUrl($quote);

        // Force PhpApi to just generate the request instead of sending it directly
        $this->_heidelpayPaymentMethod->_dryRun = true;

        // add IBAN and Bank account owner to the request.
        $this->_heidelpayPaymentMethod
            ->getRequest()->getAccount()
            ->set('iban', $paymentInfo->getAdditionalData()->hgw_iban)
            ->set('holder', $paymentInfo->getAdditionalData()->hgw_holder);

        // Set payment type to debit
        $this->_heidelpayPaymentMethod->debit();

        // Prepare and send request to heidelpay
        $response = $this->_heidelpayPaymentMethod->getRequest()->send(
            $this->_heidelpayPaymentMethod->getPaymentUrl(),
            $this->_heidelpayPaymentMethod->getRequest()->convertToArray()
        );

        return $response[0];
    }

    /**
     * Prints out a message with information for
     * the customer about the direct debit.
     *
     * @param array $response
     * @return \Magento\Framework\Phrase
     */
    public function additionalPaymentInformation($response)
    {
        return __(
            'The amount of <strong>%1 %2</strong> will be debited from this account within the next days:'
            . '<br /><br />IBAN: %3<br /><br /><i>The booking contains the mandate reference ID: %4'
            . '<br >and the creditor identifier: %5</i><br /><br />'
            . 'Please ensure that there will be sufficient funds on the corresponding account.',
            $response['PRESENTATION_AMOUNT'],
            $response['PRESENTATION_CURRENCY'],
            $response['ACCOUNT_IBAN'],
            $response['ACCOUNT_IDENTIFICATION'],
            $response['IDENTIFICATION_CREDITOR_ID']
        );
    }
}
