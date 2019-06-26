<?php
/**
 * Show hp installment plan before place order
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 * @link http://dev.heidelpay.com/magento2
 * @author Simon Gabriel
 *
 * @package heidelpay\magento2\controllers
 */
namespace Heidelpay\Gateway\Controller\Index;

use Exception;
use Heidelpay\Gateway\Api\Data\TransactionInterface;
use Heidelpay\Gateway\Api\TransactionRepositoryInterface;
use Heidelpay\Gateway\Block\HgwInstallmentPlan;
use Heidelpay\Gateway\Controller\HgwAbstract;
use Heidelpay\Gateway\Helper\Payment as HeidelpayHelper;
use Heidelpay\Gateway\Model\Transaction;
use Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod;
use Heidelpay\PhpPaymentApi\Constants\PaymentMethod;
use Heidelpay\PhpPaymentApi\Constants\ProcessingResult;
use Heidelpay\PhpPaymentApi\Constants\TransactionType;
use Heidelpay\PhpPaymentApi\Response;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Escaper;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\Quote;

class InstallmentPlan extends HgwAbstract
{
    /** @var Escaper */
    private $escaper;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;
    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * InstallmentPlan constructor.
     * @param Context $context
     * @param Session $customerSession
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param Data $urlHelper
     * @param LoggerInterface $logger
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $quoteObject
     * @param PageFactory $resultPageFactory
     * @param HeidelpayHelper $paymentHelper
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param OrderCommentSender $orderCommentSender
     * @param Encryptor $encryptor
     * @param Url $customerUrl
     * @param Escaper $escaper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TransactionRepositoryInterface $transactionRepository
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        Data $urlHelper,
        LoggerInterface $logger,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $quoteObject,
        PageFactory $resultPageFactory,
        HeidelpayHelper $paymentHelper,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        OrderCommentSender $orderCommentSender,
        Encryptor $encryptor,
        Url $customerUrl,
        Escaper $escaper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TransactionRepositoryInterface $transactionRepository,
        SortOrderBuilder $sortOrderBuilder
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $orderFactory,
            $urlHelper,
            $logger,
            $cartManagement,
            $quoteObject,
            $resultPageFactory,
            $paymentHelper,
            $orderSender,
            $invoiceSender,
            $orderCommentSender,
            $encryptor,
            $customerUrl
        );

        $this->escaper = $escaper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function execute()
    {
        $session = $this->getCheckout();
        $quote = $session->getQuote();

        if (!$quote->getId()) {
            $message = __('An unexpected error occurred. Please contact us to get further information.');
            $this->messageManager->addErrorMessage($this->escaper->escapeHtml($message));

            return $this->_redirect('checkout/cart/', ['_secure' => true]);
        }

        /** @var HeidelpayAbstractPaymentMethod $methodInstance */
        $methodInstance = $quote->getPayment()->getMethodInstance();
        if (!$methodInstance instanceof HeidelpayAbstractPaymentMethod) {
            $message = __('An unexpected error occurred. Please contact us to get further information.');
            $this->messageManager->addErrorMessage($this->escaper->escapeHtml($message));

            return $this->_redirect('checkout/cart/', ['_secure' => true]);
        }

        $installmentPlanUrl = null;
        $initRefernceId = null;

        // fetch the latest installment plan for the selected HP-method
        $paymentMethodInstance = $methodInstance->getHeidelpayPaymentMethodInstance();
        if ($paymentMethodInstance->getPaymentCode() === PaymentMethod::HIRE_PURCHASE) {
            /** @var TransactionSearchResultInterface $results */
            $results = $this->getAllHpInsForThisQuote($quote);
            foreach ($results->getItems() as $item) {
                /** @var TransactionInterface $item */
                $heidelpayResponse = new Response($item->getJsonResponse());
                if ($heidelpayResponse->getAccount()->getBrand() === $paymentMethodInstance->getBrand()) {
                    $contractUrlField = $paymentMethodInstance->getBrand() . '_PDF_URL';
                    $installmentPlanUrl = $heidelpayResponse->getCriterion()->get($contractUrlField);
                    $initRefernceId = $heidelpayResponse->getPaymentReferenceId();
                    break;
                }
            }
        }

        if (!empty($installmentPlanUrl) && !empty($initRefernceId)) {
            $resultPage = $this->_resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('Please confirm your payment:'));

            /** @var HgwInstallmentPlan $hgwInstallmentPlan */
            $hgwInstallmentPlan = $resultPage->getLayout()->getBlock('InstallmentPlan');
            $hgwInstallmentPlan->setInstallmentPlanUrl($installmentPlanUrl);
            $hgwInstallmentPlan->setInitReferenceId($initRefernceId);

            return $resultPage;
        }

        $this->_logger->error('Heidelpay InstallmentPlan Error: Could not find installment plan URL.');

        // get an error message for the given error code and add it to the message container.
        $message = 'Heidelpay InstallmentPlan Error: Could not find installment plan URL.';
        $this->messageManager->addErrorMessage($this->escaper->escapeHtml($message));

        return $this->_redirect('checkout/cart/', ['_secure' => true]);
    }

    /**
     * @param Quote $quote
     * @param string $direction
     * @return TransactionSearchResultInterface
     */
    private function getAllHpInsForThisQuote(Quote $quote, $direction = SortOrder::SORT_DESC)
    {
        $sortOrder = $this->sortOrderBuilder->setField(Transaction::ID)->setDirection($direction)->create();
        $criteria  = $this->searchCriteriaBuilder
            ->addFilter(Transaction::QUOTE_ID, $quote->getId())
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
