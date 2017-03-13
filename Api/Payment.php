<?php

namespace Heidelpay\Gateway\Api;

use Heidelpay\Gateway\Model\Config\Source\Recognition;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;

/**
 * Payment API Processor
 *
 * This is the controller where API REST requests to the heidelpay module are being processed.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class Payment implements PaymentInterface
{
    /** @var \Magento\Framework\Encryption\EncryptorInterface */
    public $encryptor;

    /** @var \Psr\Log\LoggerInterface */
    public $logger;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    public $scopeConfig;

    /** @var \Magento\Quote\Model\QuoteRepository */
    public $quoteRepository;

    /** @var \Magento\Quote\Model\QuoteIdMaskFactory */
    public $quoteIdMaskFactory;

    /** @var \Heidelpay\Gateway\Model\PaymentInformationFactory */
    public $paymentInformationFactory;

    /** @var \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory */
    public $paymentInformationCollectionFactory;

    /**
     * Payment Information API constructor.
     *
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Heidelpay\Gateway\Model\PaymentInformationFactory $paymentInformationFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param PaymentInformationCollectionFactory $paymentInformationCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Heidelpay\Gateway\Model\PaymentInformationFactory $paymentInformationFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->paymentInformationFactory = $paymentInformationFactory;
        $this->paymentInformationCollectionFactory = $paymentInformationCollectionFactory;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalPaymentInformation($quoteId, $paymentMethod)
    {
        $result = null;

        // get the quote information by cart id
        $quote = $this->quoteRepository->get($quoteId);

        // get the recognition configuration for the given payment method and store id.
        $allowRecognition = $this->scopeConfig->getValue(
            'payment/' . $paymentMethod . '/recognition',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $quote->getStoreId()
        );

        // if recognition is set to 'never', we don't return any data.
        if ($allowRecognition == Recognition::RECOGNITION_NEVER) {
            $result = null;
        }

        // get the customer payment information by given data from the request.
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();
        /** @var \Heidelpay\Gateway\Model\PaymentInformation $paymentInfo */
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
            $quote->getStoreId(),
            $quote->getCustomerEmail(),
            $paymentMethod
        );

        // if there is payment information stored, we can work with it.
        if (!$paymentInfo->isEmpty()) {
            // if recognition is set to 'always', we always return the additional data.
            if ($allowRecognition === Recognition::RECOGNITION_ALWAYS) {
                $result = $paymentInfo->getAdditionalData();
            }

            // we only return additional payment data, if the shipping data is the same (to prevent fraud)
            if ($allowRecognition === Recognition::RECOGNITION_SAME_SHIPPING_ADDRESS) {
                // if the shipping hashes are the same, we can safely return the addtional payment data.
                if ($this->createShippingHash($quote->getShippingAddress()) == $paymentInfo->getShippingHash()) {
                    $result = $paymentInfo->getAdditionalData();
                }
            }
        }

        // if we have no data at all, set the account owner to the billing address name.
        if ($result === null) {
            $result = [
                'hgw_holder' => $quote->getBillingAddress()->getName()
            ];
        }

        return json_encode($result);
    }

    /**
     * @inheritdoc
     */
    public function saveDirectDebitInfo($cartId, $hgwIban, $hgwHolder)
    {
        $additionalData = [
            'hgw_iban' => $hgwIban,
            'hgw_holder' => $hgwHolder
        ];

        // get the quote information by cart id
        $quote = $this->quoteRepository->get($cartId);

        // create a new instance for the payment information collection.
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load payment information by the customer's quote.
        /** @var \Heidelpay\Gateway\Model\PaymentInformation $paymentInfo */
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
            $quote->getStoreId(),
            $quote->getCustomerEmail(),
            $quote->getPayment()->getMethod()
        );

        // if there is no payment information data set, we create a new one...
        if ($paymentInfo->isEmpty()) {
            // create a new instance for the payment information data.
            $paymentInfoFactory = $this->paymentInformationFactory->create();

            // save the payment information
            if ($this->savePaymentInformation($paymentInfoFactory, $quote, $additionalData)) {
                return true;
            }

            return false;
        }

        // ... else, we update the data and save the model.
        if ($this->savePaymentInformation($paymentInfo, $quote, $additionalData)) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function saveGuestDirectDebitInfo($cartId, $hgwIban, $hgwHolder)
    {
        $additionalData = [
            'hgw_iban' => $hgwIban,
            'hgw_holder' => $hgwHolder
        ];

        // get the real quote id by guest cart id (masked random string serves as guest cart id)
        /** @var \Magento\Quote\Model\QuoteIdMask $quoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quoteId = $quoteIdMask->getQuoteId();

        // get the quote information by cart id
        $quote = $this->quoteRepository->get($quoteId);

        // create a new instance for the payment information collection.
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load payment information by the customer's quote.
        /** @var \Heidelpay\Gateway\Model\PaymentInformation $paymentInfo */
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
            $quote->getStoreId(),
            $quote->getCustomerEmail(),
            $quote->getPayment()->getMethod()
        );

        // if there is no payment information data set, we create a new one...
        if ($paymentInfo->isEmpty()) {
            // create a new instance for the payment information data.
            $paymentInfoFactory = $this->paymentInformationFactory->create();

            // save the payment information
            if ($this->savePaymentInformation($paymentInfoFactory, $quote, $additionalData)) {
                return true;
            }

            return false;
        }

        // ... else, we update the data and save the model.
        if ($this->savePaymentInformation($paymentInfo, $quote, $additionalData)) {
            return true;
        }

        return false;
    }

    /**
     * Create a shipping hash.
     *
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return string
     */
    private function createShippingHash(\Magento\Quote\Model\Quote\Address $address)
    {
        return $this->encryptor->hash(
            implode('', [
                $address->getFirstname(),
                $address->getLastname(),
                implode('', $address->getStreet()), // getStreet returns an array
                $address->getCity(),
                $address->getPostcode(),
                $address->getCountryId()
            ])
        );
    }

    /**
     * Saves the payment information into the database.
     *
     * @param \Heidelpay\Gateway\Model\PaymentInformation $paymentInformation
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $additionalData
     * @param string $paymentRef
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     */
    private function savePaymentInformation($paymentInformation, $quote, $additionalData, $paymentRef = null)
    {
        return $paymentInformation
            ->setStoreId($quote->getStoreId())
            ->setCustomerEmail($quote->getCustomer()->getEmail())
            ->setPaymentMethod($quote->getPayment()->getMethod())
            ->setShippingHash($this->createShippingHash($quote->getShippingAddress()))
            ->setAdditionalData($additionalData)
            ->setHeidelpayPaymentReference($paymentRef)
            ->save();
    }
}
