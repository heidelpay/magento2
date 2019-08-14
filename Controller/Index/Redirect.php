<?php

namespace Heidelpay\Gateway\Controller\Index;

use Exception;
use Heidelpay\Gateway\Controller\HgwAbstract;
use Heidelpay\Gateway\Helper\Payment as HeidelpayHelper;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory;
use Heidelpay\Gateway\Model\Transaction as HeidelpayTransaction;
use Heidelpay\MessageCodeMapper\Exceptions\MissingLocaleFileException;
use Heidelpay\PhpPaymentApi\Response;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\Url;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

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
class Redirect extends HgwAbstract
{
    /** @var Response The heidelpay response class */
    private $heidelpayResponse;

    /** @var CollectionFactory */
    private $transactionCollectionFactory;

    /**
     * heidelpay Redirect constructor.
     *
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param UrlHelper $urlHelper
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
     * @param CollectionFactory $transactionCollectionFactory
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        UrlHelper $urlHelper,
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
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws MissingLocaleFileException
     */
    public function execute()
    {
        $quoteId = $this->getCheckout()->getQuoteId();
        $redirect = $this->_redirect('checkout/cart/', ['_secure' => true]);

        if (empty($quoteId)) {
            $this->_logger->error('Heidelpay - Redirect: Called with empty quoteId');
            return $redirect;
        }

        $transactionData = null;

        try {
            /** @var HeidelpayTransaction $transaction */
            $transaction = $this->transactionCollectionFactory->create()->loadByQuoteId($quoteId);
            $transactionData = $transaction->getJsonResponse();
        } catch (Exception $e) {
            $this->_logger->error('Heidelpay - Redirect: Load transaction fail. ' . $e->getMessage());
        }

        // if our data is still null, we got no transaction data - so nothing to work with.
        // - redirect the user back to the checkout cart.
        if ($transactionData === null) {
            $this->_logger->error(
                'Heidelpay - Redirect: Empty transaction data->jsonResponse. (no data was stored in Response?)'
            );

            // display the customer-friendly message for the customer
            $this->messageManager->addErrorMessage(
                __('An unexpected error occurred. Please contact us to get further information.')
            );

            return $redirect;
        }

        // initialize the Response object with data from the transaction.
        $this->heidelpayResponse = Response::fromPost($transactionData);

        // set Parameters for success page
        if ($this->heidelpayResponse->isSuccess()) {
            /** @var Order $order */
            $order = null;
            try {
                $order = $this->_orderFactory->create()->loadByAttribute('quote_id', $quoteId);
            } catch (Exception $e) {
                $this->_logger->error(
                    'Heidelpay - Redirect: Cannot receive order.' . $e->getMessage()
                );
            }

            // Check whether order was loaded correctly
            if($order === null || $order->isEmpty()) {
                $this->_logger->error(
                    'Heidelpay - Redirect: Cannot receive order. Order creation might have failed.'
                );
                $this->messageManager->addErrorMessage(
                    __('An unexpected error occurred. Please contact us to get further information.')
                );

                return $redirect;
            }

            $this->updateSessionData($quoteId, $order, $transactionData);
            $this->_logger->debug('Heidelpay - Redirect: Redirecting customer to success page.');

            return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
        }

        $this->_logger->error(
            'Heidelpay - Redirect: Redirect with error to cart: ' . $this->heidelpayResponse->getError()['message']
        );

        // display the customer-friendly message for the customer
        $this->messageManager->addErrorMessage(
            $this->_paymentHelper->handleError($this->heidelpayResponse->getError()['code'])
        );

        return $redirect;
    }

    /** Update customer's session Information.
     * @param int $quoteId
     * @param Order $order
     * @param array $data
     *
     * @return void
     */
    protected function updateSessionData($quoteId, Order $order, array $data)
    {
        $checkoutSession = $this->getCheckout();
        $checkoutSession->clearHelperData();

        // set QuoteIds
        $checkoutSession->setLastQuoteId($quoteId)
            ->setLastSuccessQuoteId($quoteId);

        // set OrderIds
        $checkoutSession->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());

        $additionalPaymentInformation = $order->getPayment()
            ->getMethodInstance()
            ->additionalPaymentInformation($data);

        $checkoutSession->setHeidelpayInfo($additionalPaymentInformation);
    }
}
