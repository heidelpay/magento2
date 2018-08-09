<?php

namespace Heidelpay\Gateway\Controller\Index;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;

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
class Push extends \Heidelpay\Gateway\Controller\HgwAbstract
{
    /** @var OrderRepository $orderRepository */
    private $orderRepository;

    /** @var \Heidelpay\PhpPaymentApi\Push */
    private $heidelpayPush;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteObject
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Heidelpay\Gateway\Helper\Payment $paymentHelper
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param OrderCommentSender $orderCommentSender
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param OrderRepository $orderRepository
     * @param \Heidelpay\PhpPaymentApi\Push $heidelpayPush
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteObject,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Heidelpay\Gateway\Helper\Payment $paymentHelper,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        OrderCommentSender $orderCommentSender,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Customer\Model\Url $customerUrl,
        OrderRepository $orderRepository,
        \Heidelpay\PhpPaymentApi\Push $heidelpayPush,
        SearchCriteriaBuilder $searchCriteriaBuilder
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
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Heidelpay\PhpPaymentApi\Exceptions\XmlResponseParserException
     */
    public function execute()
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();

        if (!$request->isPost()) {
            $this->_logger->debug('Heidelpay - Push: Response is not post.');
            return;
        }

        if ($request->getHeader('Content-Type') != 'application/xml') {
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

        $this->_logger->debug('Push Response: ' . print_r($this->heidelpayPush->getResponse(), true));

        list($paymentMethod, $paymentType) = $this->_paymentHelper->splitPaymentCode(
            $this->heidelpayPush->getResponse()->getPayment()->getCode()
        );

        // in case of receipts, we process the push message for receipts.
        if ($this->_paymentHelper->isReceiptAble($paymentMethod, $paymentType)) {
            // only when the Response is ACK.
            if ($this->heidelpayPush->getResponse()->isSuccess()) {
                // load the referenced order to receive the order information.
                $criteria = $this->searchCriteriaBuilder
                    ->addFilter(
                        'quote_id',
                        $this->heidelpayPush->getResponse()->getIdentification()->getTransactionId()
                    )->create();

                /** @var Collection $orderList */
                $orderList = $this->orderRepository->getList($criteria);

                /** @var Order $order */
                $order = $orderList->getFirstItem();

                $paidAmount = (float)$this->heidelpayPush->getResponse()->getPresentation()->getAmount();
                $dueLeft = $order->getTotalDue() - $paidAmount;

                $state = Order::STATE_PROCESSING;
                $comment = 'heidelpay - Purchase Complete';

                // if payment is not complete
                if ($dueLeft > 0.00) {
                    $state = Order::STATE_PAYMENT_REVIEW;
                    $comment = 'heidelpay - Partly Paid ('
                        . $this->_paymentHelper->format(
                            $this->heidelpayPush->getResponse()->getPresentation()->getAmount()
                        )
                        . ' ' . $this->heidelpayPush->getResponse()->getPresentation()->getCurrency() . ')';
                }

                // set the invoice states to 'paid', if no due is left.
                if ($dueLeft <= 0.00) {
                    /** @var \Magento\Sales\Model\Order\Invoice $invoice */
                    foreach ($order->getInvoiceCollection() as $invoice) {
                        $invoice->setState(Invoice::STATE_PAID)->save();
                    }
                }

                $order->setTotalPaid($order->getTotalPaid() + $paidAmount)
                    ->setBaseTotalPaid($order->getBaseTotalPaid() + $paidAmount)
                    ->setState($state)
                    ->addStatusHistoryComment($comment, $state);

                // create a heidelpay Transaction.
                $order->getPayment()->getMethodInstance()->saveHeidelpayTransaction(
                    $this->heidelpayPush->getResponse(),
                    $paymentMethod,
                    $paymentType,
                    'PUSH',
                    []
                );

                // create a child transaction.
                $order->getPayment()->setTransactionId($this->heidelpayPush->getResponse()->getPaymentReferenceId());
                $order->getPayment()->setParentTransactionId(
                    $this->heidelpayPush->getResponse()->getIdentification()->getReferenceId()
                );
                $order->getPayment()->setIsTransactionClosed(true);

                $order->getPayment()->addTransaction(Transaction::TYPE_CAPTURE, null, true);

                $order->save();
            }
        }
    }
}
