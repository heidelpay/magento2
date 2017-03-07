<?php

namespace Heidelpay\Gateway\Api;

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

    /** @var \Magento\Quote\Model\QuoteRepository */
    public $quoteRepository;

    /** @var \Heidelpay\Gateway\Model\PaymentInformationFactory */
    public $paymentInformationFactory;

    /** @var \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory */
    public $paymentInformationCollectionFactory;

    /**
     * Payment constructor.
     *
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Heidelpay\Gateway\Model\PaymentInformationFactory $paymentInformationFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param PaymentInformationCollectionFactory $paymentInformationCollectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Heidelpay\Gateway\Model\PaymentInformationFactory $paymentInformationFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->paymentInformationFactory = $paymentInformationFactory;
        $this->paymentInformationCollectionFactory = $paymentInformationCollectionFactory;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
    }

    /**
     * @param int $cartId
     * @param string $hgwIban
     * @param string $hgwHolder
     * @return mixed
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
        $paymentInfo = $paymentInfoCollection->loadByCustomerInformation($quote);

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
     * @return \Heidelpay\Gateway\Model\PaymentInformationInterface
     */
    private function savePaymentInformation($paymentInformation, $quote, $additionalData, $paymentRef = null)
    {
        return $paymentInformation
            ->setStore($quote->getStoreId())
            ->setCustomerEmail($quote->getCustomer()->getEmail())
            ->setPaymentMethod($quote->getPayment()->getMethod())
            ->setShippingHash($this->createShippingHash($quote->getShippingAddress()))
            ->setAdditionalData($additionalData)
            ->setHeidelpayPaymentReference($paymentRef)
            ->save();
    }
}
