<?php

namespace Heidelpay\Gateway\Controller\Index;

use Heidelpay\Gateway\Helper\Payment as HeidelpayHelper;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory;
use Heidelpay\PhpPaymentApi\Response;
use Magento\Sales\Helper\Data;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Redirect customer back to shops success or error page
 *
 * The heidelpay payment server will always redirect the customer back to
 * this controller after payment process. This controller will check
 * the result of the payment process and redirects the customer to error
 * or success page.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link http://dev.heidelpay.com/magento2
 * @author Jens Richter
 *
 * @package heidelpay\magento2\controllers
 */
class Redirect extends \Heidelpay\Gateway\Controller\HgwAbstract
{
    /** @var Response The heidelpay response class */
    private $heidelpayResponse;

    /** @var CollectionFactory */
    private $transactionCollectionFactory;

    /** @var Data */
    private $salesHelper;

    /**
     * heidelpay Redirect constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteObject
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param HeidelpayHelper $paymentHelper
     * @param Data $salesHelper
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param OrderCommentSender $orderCommentSender
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param CollectionFactory $transactionCollectionFactory
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
        HeidelpayHelper $paymentHelper,
        Data $salesHelper,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        OrderCommentSender $orderCommentSender,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Customer\Model\Url $customerUrl,
        CollectionFactory $transactionCollectionFactory
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

        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->salesHelper = $salesHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Heidelpay\MessageCodeMapper\Exceptions\MissingLocaleFileException
     */
    public function execute()
    {
        $session = $this->getCheckout();
        $quoteId = $session->getQuoteId();

        if (empty($quoteId)) {
            $this->_logger->warning('Heidelpay - Redirect: Called with empty quoteId');

            return $this->_redirect('checkout/cart/', ['_secure' => true]);
        }

        $data = null;

        try {
            /** @var \Heidelpay\Gateway\Model\Transaction $transaction */
            $transaction = $this->transactionCollectionFactory->create()->loadByQuoteId($quoteId);
            $data = $transaction->getJsonResponse();
        } catch (\Exception $e) {
            $this->_logger->error('Heidelpay - Redirect: Load transaction fail. ' . $e->getMessage());
        }

        // if our data is still null, we got no transaction data - so nothing to work with.
        // - redirect the user back to the checkout cart.
        if ($data === null) {
            $this->_logger->error(
                'Heidelpay - Redirect: Empty transaction data->jsonResponse. (no data was stored in Response?)'
            );

            // display the customer-friendly message for the customer
            $this->messageManager->addErrorMessage(
                __('An unexpected error occurred. Please contact us to get further information.')
            );

            return $this->_redirect('checkout/cart/', ['_secure' => true]);
        }

        // initialize the Response object with data from the transaction.
        $this->heidelpayResponse = Response::fromPost($data);

        // set Parameters for success page
        if ($this->heidelpayResponse->isSuccess()) {
            // lock the quote
            $session->getQuote()->setIsActive(false)->save();

            /** @var Order $order */
            $order = null;

            try {
                $order = $this->_orderFactory->create()->loadByAttribute('quote_id', $quoteId);

                // send order confirmation to the customer
                $this->_orderSender->send($order);
            } catch (\Exception $e) {
                $this->_logger->error(
                    'Heidelpay - Redirect: Cannot receive order or send order confirmation E-Mail. ' . $e->getMessage()
                );
            }

            // Check send Invoice Mail enabled
            if ($this->salesHelper->canSendNewInvoiceEmail($session->getQuote()->getStore()->getId())) {
                // send invoice(s) to the customer
                if (!$order->canInvoice()) {
                    $invoices = $order->getInvoiceCollection();

                    foreach ($invoices as $invoice) {
                        $this->_invoiceSender->send($invoice);
                    }
                }
            }

            $session->clearHelperData();

            // set QuoteIds
            $session->setLastQuoteId($quoteId)
                ->setLastSuccessQuoteId($quoteId);

            // set OrderIds
            $session->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            $additionalPaymentInformation = $order->getPayment()
                ->getMethodInstance()
                ->additionalPaymentInformation($data);

            $this->_checkoutSession->setHeidelpayInfo($additionalPaymentInformation);

            $this->_logger->debug('Heidelpay - Redirect: Redirecting customer to success page.');
            return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
        }

        // unlock the quote in case of error
        $session->getQuote()->setIsActive(true)->save();

        $this->_logger->error(
            'Heidelpay - Redirect: Redirect with error to cart: ' . $this->heidelpayResponse->getError()['message']
        );

        // display the customer-friendly message for the customer
        $this->messageManager->addErrorMessage(
            $this->_paymentHelper->handleError($this->heidelpayResponse->getError()['code'])
        );

        return $this->_redirect('checkout/cart/', ['_secure' => true]);
    }
}
