<?php

namespace Heidelpay\Gateway\Controller\Index;

use Heidelpay\Gateway\Controller\HgwAbstract;
use Heidelpay\Gateway\Helper\Order as orderHelper;
use Heidelpay\Gateway\Helper\Payment as PaymentHelper;
use Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod;
use Heidelpay\PhpPaymentApi\Exceptions\XmlResponseParserException;
use Heidelpay\PhpPaymentApi\Push as heidelpayPush;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url;
use Heidelpay\Gateway\Helper\Response as ResponseHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

/**
 * heidelpay Push Controller
 *
 * Receives XML Push requests from the heidelpay Payment API and processes them.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link http://dev.heidelpay.com/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay\magento2\controllers
 */
class Push extends HgwAbstract
{
    /** @var OrderRepository $orderRepository */
    private $orderRepository;

    /** @var heidelpayPush */
    private $heidelpayPush;

    /** @var QuoteRepository */
    private $quoteRepository;
    /** @var orderHelper */
    private $orderHelper;

    /** @var ResponseHelper */
    private $repsonseHelper;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param Data $urlHelper
     * @param LoggerInterface $logger
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $quoteObject
     * @param PageFactory $resultPageFactory
     * @param PaymentHelper $paymentHelper
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param OrderCommentSender $orderCommentSender
     * @param Encryptor $encryptor
     * @param Url $customerUrl
     * @param OrderRepository $orderRepository
     * @param heidelpayPush $heidelpayPush
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param QuoteRepository $quoteRepository
     * @param orderHelper $orderHelper
     * @param ResponseHelper $repsonseHelper
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        Data $urlHelper,
        LoggerInterface $logger,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $quoteObject,
        PageFactory $resultPageFactory,
        PaymentHelper $paymentHelper,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        OrderCommentSender $orderCommentSender,
        Encryptor $encryptor,
        Url $customerUrl,
        OrderRepository $orderRepository,
        heidelpayPush $heidelpayPush,
        QuoteRepository $quoteRepository,
        orderHelper $orderHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResponseHelper $repsonseHelper
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

        $this->orderRepository = $orderRepository;
        $this->heidelpayPush = $heidelpayPush;
        $this->quoteRepository = $quoteRepository;
        $this->orderHelper = $orderHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->repsonseHelper = $repsonseHelper;
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws XmlResponseParserException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        /** @var Http $request */
        $request = $this->getRequest();

        if (!$request->isPost()) {
            $this->_logger->debug('Heidelpay - Push: Response is not post.');
            return;
        }

        if ($request->getHeader('Content-Type') !== 'application/xml') {
            $this->_logger->debug('Heidelpay - Push: Content-Type is not "application/xml"');
        }

        if ($request->getHeader('X-Push-Timestamp') != '' && $request->getHeader('X-Push-Retries') != '') {
            $this->_logger->debug('Heidelpay - Push: Timestamp: "' . $request->getHeader('X-Push-Timestamp') . '"');
            $this->_logger->debug('Heidelpay - Push: Retries: "' . $request->getHeader('X-Push-Retries') . '"');
        }

        try {
            // getContent returns php://input, if no other content is set.
            $this->heidelpayPush->setRawResponse($request->getContent());
        } catch (\Exception $e) {
            $this->_logger->critical(
                'Heidelpay - Push: Cannot parse XML Push Request into Response object. '
                . $e->getMessage()
            );
        }

        $pushResponse = $this->heidelpayPush->getResponse();
        $this->_logger->debug('Push Response: ' . print_r($pushResponse, true));

        // Stop processing if hash validation fails.
        $remoteAddress = $this->getRequest()->getServer('REMOTE_ADDR');
        if(!$this->repsonseHelper->validateSecurityHash($pushResponse, $remoteAddress)) {
            return;
        }

        list($paymentMethod, $paymentType) = $this->_paymentHelper->splitPaymentCode(
            $pushResponse->getPayment()->getCode()
        );

        // Only process transactions that might potentially create new order, this includes receipts.
        if (
            $pushResponse->isSuccess() &&
            !$pushResponse->isPending() &&
            $this->_paymentHelper->isNewOrderType($paymentType)
        ) {
            $transactionId = $pushResponse->getIdentification()->getTransactionId();
            $order = $this->orderHelper->fetchOrder($transactionId);
            $quote = $this->quoteRepository->get($transactionId);

            // create order if it doesn't exists already.
            if ($order === null || $order->isEmpty()) {
                $transactionData = $this->_paymentHelper->getDataFromResponse($pushResponse);
                $this->_paymentHelper->saveHeidelpayTransaction($pushResponse, $transactionData, 'PUSH');
                $this->_logger->debug('heidelpay Push - Order does not exist for transaction. heidelpay transaction id: '
                    . $transactionId);

                try {
                    $order = $this->_paymentHelper->createOrderFromQuote($quote);
                    if ($order === null || $order->isEmpty())
                    {
                        $this->_logger->error('Heidelpay - Response: Cannot submit the Quote. ' . $e->getMessage());
                        return;
                    }
                } catch (Exception $e) {
                    $this->_logger->error('Heidelpay - Response: Cannot submit the Quote. ' . $e->getMessage());
                    return;
                }

                $this->_paymentHelper->mapStatus($transactionData, $order);
                $this->_logger->debug('order status: ' . $order->getStatus());
                $this->orderHelper->handleOrderMail($order);
                $this->orderHelper->handleInvoiceMails($order);
                $this->orderRepository->save($order);
            }
            $this->_paymentHelper->handleAdditionalPaymentInformation($quote);


            if ($this->_paymentHelper->isReceiptAble($paymentMethod, $paymentType)) {
                // load the referenced order to receive the order information.
                $payment = $order->getPayment();

                /** @var HeidelpayAbstractPaymentMethod $methodInstance */
                $methodInstance = $payment->getMethodInstance();
                $uniqueId = $pushResponse->getPaymentReferenceId();

                /** @var bool $transactionExists Flag to identify new Transaction */
                $transactionExists = $methodInstance->heidelpayTransactionExists($uniqueId);

                // If Transaction already exists, push wont be processed.
                if ($transactionExists) {
                    $this->_logger->debug('heidelpay - Push Response: ' . $uniqueId . ' already exists');
                    return;
                }

                $paidAmount = (float)$pushResponse->getPresentation()->getAmount();
                $dueLeft = $order->getTotalDue() - $paidAmount;

                $state = Order::STATE_PROCESSING;
                $comment = 'heidelpay - Purchase Complete';

                // if payment is not complete
                if ($dueLeft > 0.00) {
                    $state = Order::STATE_PAYMENT_REVIEW;
                    $comment = 'heidelpay - Partly Paid ('
                        . $this->_paymentHelper->format(
                            $pushResponse->getPresentation()->getAmount()
                        )
                        . ' ' . $pushResponse->getPresentation()->getCurrency() . ')';
                }

                // set the invoice states to 'paid', if no due is left.
                if ($dueLeft <= 0.00) {
                    /** @var Invoice $invoice */
                    foreach ($order->getInvoiceCollection() as $invoice) {
                        $invoice->setState(Invoice::STATE_PAID)->save();
                    }
                }

                $order->setTotalPaid($order->getTotalPaid() + $paidAmount)
                    ->setBaseTotalPaid($order->getBaseTotalPaid() + $paidAmount)
                    ->setState($state)
                    ->addStatusHistoryComment($comment, $state);

                // create a heidelpay Transaction.
                $methodInstance->saveHeidelpayTransaction(
                    $pushResponse,
                    $paymentMethod,
                    $paymentType,
                    'PUSH',
                    []
                );

                // create a child transaction.
                $payment->setTransactionId($uniqueId)
                    ->setParentTransactionId($pushResponse->getIdentification()->getReferenceId())
                    ->setIsTransactionClosed(true)
                    ->addTransaction(Transaction::TYPE_CAPTURE, null, true);

                $this->orderRepository->save($order);
            }
        }
    }
}
