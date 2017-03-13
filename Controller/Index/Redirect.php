<?php

namespace Heidelpay\Gateway\Controller\Index;

use Heidelpay\Gateway\Helper\Payment as HeidelpayHelper;
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
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/magento
 * @author Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class Redirect extends \Heidelpay\Gateway\Controller\HgwAbstract
{
    protected $resultPageFactory;
    protected $logger;

    /** @var \Heidelpay\PhpApi\Response The heidelpay response class */
    protected $heidelpayResponse;

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
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param OrderCommentSender $orderCommentSender
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Heidelpay\PhpApi\Response $heidelpayResponse
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
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        OrderCommentSender $orderCommentSender,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Customer\Model\Url $customerUrl,
        \Heidelpay\PhpApi\Response $heidelpayResponse
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $orderFactory, $urlHelper, $logger,
            $cartManagement, $quoteObject, $resultPageFactory, $paymentHelper, $orderSender, $invoiceSender,
            $orderCommentSender, $encryptor, $customerUrl);

        $this->heidelpayResponse = $heidelpayResponse;
    }

    public function execute()
    {
        $session = $this->getCheckout();
        $quoteId = $session->getQuoteId();

        if (empty($quoteId)) {
            $this->_logger->debug('Heidelpay call redirect with empty quoteId');
            $this->_redirect('checkout/cart/', ['_secure' => true]);

            return;
        }

        $data = null;

        try {
            $transaction = $this->_objectManager->create('Heidelpay\Gateway\Model\Transaction')
                ->loadLastTransactionByQuoteId($quoteId, 'transactionid');

            $data = json_decode($transaction->getJsonresponse(), true);
            $this->_logger->debug('Heidelpay redirect data ' . print_r($data, 1));
        } catch (\Exception $e) {
            $this->_logger->error('Heidelpay Redirect load transactions fail. ' . $e->getMessage());
        }

        // initialize the Response object with data from the transaction.
        $this->heidelpayResponse = $this->heidelpayResponse->splitArray($data);

        if ($data !== null && $this->heidelpayResponse->isSuccess()) {
            // set Parameters for success page
            $session->getQuote()->setIsActive(false)->save();

            try {
                $order = $this->_orderFactory->create()->loadByAttribute('quote_id', $quoteId);
                /** Sende Invoice main to customer */
                $this->_orderSender->send($order);
            } catch (\Exception $e) {
                $this->_logger->error('Cannot create order or send invoice e-mail. ' . $e->getMessage());
            }

            /** Sende Invoice main to customer */
            if (!$order->canInvoice()) {
                $invoices = $order->getInvoiceCollection();

                foreach ($invoices as $invoice) {
                    $this->_invoiceSender->send($invoice);
                }
            }

            $session->clearHelperData();

            /* set QouteIds */
            $session->setLastQuoteId($quoteId)
                ->setLastSuccessQuoteId($quoteId);
            //->clearHelperData();

            /* set OrderIds */
            $session->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderStatus($order->getStatus());

            $additionalPaymentInformation = $order->getPayment()
                ->getMethodInstance()
                ->additionalPaymentInformation($data);

            $this->_checkoutSession->setHeidelpayInfo($additionalPaymentInformation);

            $this->_logger->debug('Heidelpay redirect to success page');
            $this->_redirect('checkout/onepage/success', ['_secure' => true]);

            return;
        } else {
            $session->getQuote()->setIsActive(true)->save();

            $error_code = ($data !== null && array_key_exists('PROCESSING_RETURN_CODE', $data))
                ? $data['PROCESSING_RETURN_CODE']
                : null;

            $error_message = ($data !== null && array_key_exists('PROCESSING_RETURN', $data))
                ? $data['PROCESSING_RETURN']
                : '';

            $this->_logger->error('Heidelpay redirect with error to basket. ' . $error_message);
            $message = $this->_paymentHelper->handleError($error_code);
            $this->messageManager->addErrorMessage($message);

            $this->_redirect('checkout/cart/', ['_secure' => true]);
            return;
        }
    }
}
