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
            return json_encode(null);
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

        // if we have no data at all, set additional data that could be making it
        // a bit easier for the customer, by helping him to fill some fields.
        if ($result === null) {
            $result = $this->getAdditionalDataForPaymentMethod($paymentMethod, $quote);
        }

        return json_encode($result);
    }

    /**
     * @inheritdoc
     */
    public function saveAdditionalPaymentInfo($cartId, $method, $additionalData)
    {
        // get the quote information by cart id
        $quote = $this->quoteRepository->get($cartId);

        // if the quote is empty, there is no relation that
        // we can work with... so we return false.
        if ($quote->isEmpty()) {
            return json_encode(false);
        }

        // save the information with the given quote and additional data.
        // if there is nothing stored, we'll return false...
        if (!$this->savePaymentInformation($quote, $method, $additionalData)) {
            return json_encode(false);
        }

        // ... if it was successful, we return true.
        return json_encode(true);
    }

    /**
     * @inheritdoc
     */
    public function saveGuestAdditionalPaymentInfo($cartId, $method, $additionalData)
    {
        // get the real quote id by guest cart id (masked random string serves as guest cart id)
        /** @var \Magento\Quote\Model\QuoteIdMask $quoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quoteId = $quoteIdMask->getQuoteId();

        // get the quote information by cart id
        $quote = $this->quoteRepository->get($quoteId);

        // if the quote is empty, there is no relation that
        // we can work with... so we return false.
        if ($quote->isEmpty()) {
            return json_encode(false);
        }

        // save the information with the given quote and additional data.
        // if there is nothing stored, we'll return false...
        if (!$this->savePaymentInformation($quote, $method, $additionalData)) {
            return json_encode(false);
        }

        // ... if it was successful, we return true.
        return json_encode(true);
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
     * Returns information for a given payment method, if there is
     * no additional data stored yet. This is just for making it
     * the customer a bit easier, because he is not in need
     * of entering every needed data again for a given
     * payment method.
     * For example, the bank account holder in Direct Debits
     * tends to be the same person as the one mentioned in
     * the Billing address.
     *
     * @param string $method
     * @param \Magento\Quote\Model\Quote $quote
     * @return array|null
     */
    private function getAdditionalDataForPaymentMethod($method, $quote)
    {
        $result = [];

        switch ($method) {
            case 'hgwdd':
                $result['hgw_holder'] = $quote->getBillingAddress()->getName();     // full billing name
                break;

            case 'hgwdds':
                $result['hgw_birthdate'] = $quote->getCustomer()->getDob();         // date of birth
                $result['hgw_holder'] = $quote->getBillingAddress()->getName();     // full billing name
                break;

            default:
                $result = null;
                break;
        }

        return $result;
    }

    /**
     * Saves the payment information into the database.
     *
     * If a data set with the given information exists, it will just
     * be updated. Else, a new data set will be created.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param string $method
     * @param array $additionalData
     * @param string $paymentRef
     * @return \Heidelpay\Gateway\Api\Data\PaymentInformationInterface
     */
    private function savePaymentInformation($quote, $method, $additionalData, $paymentRef = null)
    {
        // create a new instance for the payment information collection.
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load payment information by the customer's quote.
        /** @var \Heidelpay\Gateway\Model\PaymentInformation $paymentInfo */
        $paymentInformation = $paymentInfoCollection->loadByCustomerInformation(
            $quote->getStoreId(),
            $quote->getCustomerEmail(),
            $method
        );

        // if there is no payment information data set, we create a new one...
        if ($paymentInformation->isEmpty()) {
            $paymentInformation = $this->paymentInformationFactory->create();
        }

        // TODO: customerEmail is empty?

        return $paymentInformation
            ->setStoreId($quote->getStoreId())
            ->setCustomerEmail($quote->getCustomerEmail())
            ->setPaymentMethod($method)
            ->setShippingHash($this->createShippingHash($quote->getShippingAddress()))
            ->setAdditionalData($additionalData)
            ->setHeidelpayPaymentReference($paymentRef)
            ->save();
    }
}
