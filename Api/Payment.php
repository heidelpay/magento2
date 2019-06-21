<?php
/**
 * This is the controller where API REST requests to the heidelpay module are being processed.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay/magento2
 */
namespace Heidelpay\Gateway\Api;

use Exception;
use Heidelpay\Gateway\Api\Data\PaymentInformationInterface;
use Heidelpay\Gateway\Api\Data\TransactionInterface;
use Heidelpay\Gateway\Model\Config\Source\Recognition;
use Heidelpay\Gateway\Model\PaymentInformation;
use Heidelpay\Gateway\Model\PaymentInformationFactory;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;
use Heidelpay\Gateway\Model\Transaction;
use Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod;
use Heidelpay\PhpPaymentApi\Constants\PaymentMethod;
use Heidelpay\PhpPaymentApi\Constants\ProcessingResult;
use Heidelpay\PhpPaymentApi\Constants\TransactionType;
use Heidelpay\PhpPaymentApi\Response;
use Klarna\Kp\Api\QuoteRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\TransactionSearchResultInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Payment implements PaymentInterface
{
    /** @var EncryptorInterface */
    public $encryptor;

    /** @var LoggerInterface */
    public $logger;

    /** @var ScopeConfigInterface */
    public $scopeConfig;

    /** @var QuoteRepositoryInterface */
    public $quoteRepository;

    /** @var QuoteIdMaskFactory */
    public $quoteIdMaskFactory;

    /** @var PaymentInformationFactory */
    public $paymentInformationFactory;

    /** @var PaymentInformationCollectionFactory */
    public $paymentInformationCollectionFactory;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var TransactionRepositoryInterface */
    private $transactionRepository;

    /** @var SortOrderBuilder*/
    private $sortOrderBuilder;

    /**
     * Payment Information API constructor.
     *
     * @param QuoteRepository $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param PaymentInformationFactory $paymentInformationFactory
     * @param EncryptorInterface $encryptor
     * @param PaymentInformationCollectionFactory $paymentInformationCollectionFactory
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransactionRepositoryInterface $transactionRepository
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        PaymentInformationFactory $paymentInformationFactory,
        EncryptorInterface $encryptor,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransactionRepositoryInterface $transactionRepository,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;

        $this->paymentInformationFactory = $paymentInformationFactory;
        $this->paymentInformationCollectionFactory = $paymentInformationCollectionFactory;

        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
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
            ScopeInterface::SCOPE_STORE,
            $quote->getStoreId()
        );

        // if recognition is set to 'never', we don't return any data.
        if ($allowRecognition === Recognition::RECOGNITION_NEVER) {
            return json_encode(null);
        }

        // get the customer payment information by given data from the request.
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();
        /** @var PaymentInformation $paymentInfo */
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
                // if the shipping hashes are the same, we can safely return the additional payment data.
                if ($this->createShippingHash($quote->getShippingAddress()) === $paymentInfo->getShippingHash()) {
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
        $returnValue = true;

        // get the quote information by cart id
        $quote = $this->quoteRepository->get($cartId);

        // if the quote is empty, there is no relation that we can work with... so we return false.
        if ($quote->isEmpty()) {
            $this->logger->warning('Heidelpay: Could not find quote with id ' . $cartId . '.');
            $returnValue = false;
        }

        // save the information with the given quote and additional data.
        // if there is nothing stored, we'll return false...
        if ($returnValue && !$this->savePaymentInformation($quote, $method, $quote->getCustomerEmail(), $additionalData)) {
            $this->logger->warning('Heidelpay: Could not save payment information for quote id ' . $cartId . '.');
            $returnValue = false;
        }

        // ... if it was successful, we return true.
        return json_encode($returnValue);
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
     */
    public function saveGuestAdditionalPaymentInfo($cartId, $method, $additionalData)
    {
        $returnValue = true;

        // get the real quote id by guest cart id (masked random string serves as guest cart id)
        /** @var QuoteIdMask $quoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quoteId = $quoteIdMask->getQuoteId();

        // get the quote information by cart id
        $quote = $this->quoteRepository->get($quoteId);

        // if the quote is empty, there is no relation that
        // we can work with... so we return false.
        if ($quote->isEmpty()) {
            $returnValue = false;
        }

        // save the information with the given quote and additional data.
        // if there is nothing stored, we'll return false...
        // - since guest email is stored in the billing information, we have to pull it from there.
        if ($returnValue && !$this->savePaymentInformation($quote, $method, $quote->getBillingAddress()->getEmail(), $additionalData)) {
            $returnValue = false;
        }

        // ... if it was successful, we return true.
        return json_encode($returnValue);
    }

    /**
     * Create a shipping hash.
     *
     * @param AddressInterface $address
     * @return string
     */
    private function createShippingHash(AddressInterface $address)
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
     * @param Quote $quote
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

            case 'hgwivs':
                $result['hgw_birthdate'] = $quote->getCustomer()->getDob();
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
     * @param Quote $quote
     * @param string $method
     * @param string $email
     * @param array $additionalData
     * @param string $paymentRef
     *
     * @return PaymentInformationInterface
     *
     * @throws Exception
     */
    private function savePaymentInformation($quote, $method, $email, $additionalData, $paymentRef = null)
    {
        // make some additional data changes, if necessary
        array_walk($additionalData, static function (&$value, $key) {
            // make sure the country code in the IBAN is uppercase.
            if ($key === 'hgw_iban') {
                $value = strtoupper($value);
            }
        });

        // create a new instance for the payment information collection.
        $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

        // load payment information by the customer's quote.
        /** @var PaymentInformationInterface $paymentInfo */
        $paymentInformation = $paymentInfoCollection->loadByCustomerInformation(
            $quote->getStoreId(),
            $email,
            $method
        );

        // if there is no payment information data set, we create a new one...
        if ($paymentInformation->isEmpty()) {
            $paymentInformation = $this->paymentInformationFactory->create();
        }

        return $paymentInformation
            ->setStoreId($quote->getStoreId())
            ->setCustomerEmail($email)
            ->setPaymentMethod($method)
            ->setShippingHash($this->createShippingHash($quote->getShippingAddress()))
            ->setAdditionalData($additionalData)
            ->setHeidelpayPaymentReference($paymentRef)
            ->save();
    }

    /**
     * Fetches and returns the selected installment plan if there is one.
     *
     * @param string $quoteId
     * @param string $paymentMethod The payment method code
     *
     * @return string
     *
     * @throws NoSuchEntityException
     */
    public function getInstallmentPlan($quoteId, $paymentMethod)
    {
        $response = [];

        // do nothing if the payment method does not belong to heidelpay
        $quote = $this->quoteRepository->get($quoteId);
        $methodInstance = $quote->getPayment()->getMethodInstance();
        if (!$methodInstance instanceof HeidelpayAbstractPaymentMethod) {
            return json_encode([]);
        }

        // fetch the latest installment plan for the selected HP-method
        $paymentMethodInstance = $methodInstance->getHeidelpayPaymentMethodInstance();
        if ($paymentMethodInstance->getPaymentCode() === PaymentMethod::HIRE_PURCHASE) {
            /** @var TransactionSearchResultInterface $results */
            $results = $this->getAllHpInsForThisQuote($quoteId);
            foreach ($results->getItems() as $item) {
                /** @var TransactionInterface $item */
                $heidelpayResponse = new Response($item->getJsonResponse());
                if ($heidelpayResponse->getAccount()->getBrand() === $paymentMethodInstance->getBrand()) {
                    $contractUrlField = $paymentMethodInstance->getBrand() . '_PDF_URL';
                    $response['hgw_installment_plan_url'] = $heidelpayResponse->getCriterion()->get($contractUrlField);
                    break;
                }
            }
        }
        return json_encode($response);
    }

    /**
     * @param $quoteId
     * @param string $direction
     * @return TransactionSearchResultInterface
     */
    private function getAllHpInsForThisQuote($quoteId, $direction = SortOrder::SORT_DESC)
    {
        $sortOrder = $this->sortOrderBuilder->setField(Transaction::ID)->setDirection($direction)->create();
        $criteria  = $this->searchCriteriaBuilder
            ->addFilter(Transaction::QUOTE_ID, $quoteId)
            ->addFilter(Transaction::PAYMENT_TYPE, TransactionType::INITIALIZE)
            ->addFilter(Transaction::PAYMENT_METHOD, PaymentMethod::HIRE_PURCHASE)
            ->addFilter(Transaction::RESULT, ProcessingResult::ACK)
            ->addSortOrder($sortOrder)
            ->create();

        /** @var TransactionSearchResultInterface $results */
        $results = $this->transactionRepository->getList($criteria);
        return $results;
    }
}
