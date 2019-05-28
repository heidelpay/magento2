<?php

namespace Heidelpay\Gateway\Controller\Index;

use Exception;
use Heidelpay\Gateway\Controller\HgwAbstract;
use Heidelpay\Gateway\Helper\Payment as HeidelpayHelper;
use Heidelpay\Gateway\Model\PaymentInformation;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;
use Heidelpay\Gateway\Model\TransactionFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Sales\Model\OrderFactory;
use Heidelpay\PhpPaymentApi\Constants\PaymentMethod;
use Heidelpay\PhpPaymentApi\Exceptions\HashVerificationException;
use Heidelpay\PhpPaymentApi\Response as HeidelpayResponse;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Heidelpay\PhpPaymentApi\Constants\TransactionType;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;

/**
 * Notification handler for the payment response
 *
 * The heidelpay payment server will call this page directly after the payment
 * process to send the result of the payment to your shop. Please make sure
 * that this page is reachable form the Internet without any authentication.
 *
 * The controller use cryptographic methods to protect your shop in case of
 * fake payment responses. The plugin can not take care of man in the middle attacks,
 * so please make sure that you use https for the checkout process.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/magento2
 *
 * @author Jens Richter
 *
 * @package heidelpay\magento2\controllers
 */
class Response extends HgwAbstract
{
    /** @var QuoteRepository */
    private $quoteRepository;

    /** @var HeidelpayResponse The heidelpay response object */
    private $heidelpayResponse;

    /** @var TransactionFactory */
    private $transactionFactory;

    /** @var CollectionFactory */
    private $paymentInformationCollectionFactory;

    /** @var SalesHelper  */
    private $salesHelper;

    /**
     * heidelpay Response constructor.
     *
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
     * @param RawFactory $rawResultFactory
     * @param QuoteRepository $quoteRepository
     * @param PaymentInformationCollectionFactory $paymentInformationCollectionFactory ,
     * @param SalesHelper $salesHelper
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
        RawFactory $rawResultFactory,
        QuoteRepository $quoteRepository,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        SalesHelper $salesHelper
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

        $this->resultFactory = $rawResultFactory;
        $this->quoteRepository = $quoteRepository;
        $this->paymentInformationCollectionFactory = $paymentInformationCollectionFactory;
        $this->salesHelper = $salesHelper;
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function execute()
    {
        // the url where the payment will redirect the customer to.
        $redirectUrl = $this->_url->getUrl('hgw/index/redirect', [
            '_forced_secure' => true,
            '_scope_to_url' => true,
            '_nosid' => true
        ]);

        // initialize the Raw Response object from the factory.
        $result = $this->resultFactory->create();
        // we just want the response to return a plain url, so we set the header to text/plain.
        $result->setHeader('Content-Type', 'text/plain');
        // the payment just wants an url as result, so we set the content to the redirectUrl.
        $result->setContents($redirectUrl);

        // if there is no post request, just redirect to the cart instantly and show an error message to the customer.
        if (!$this->getRequest()->isPost()) {
            $this->_logger->warning(
                'Heidelpay - Response: There has been an error fetching the redirect url by the payment API.'
                . ' Please make sure the response url (' . $this->_url->getCurrentUrl()
                . ') is accessible from the internet.'
            );

            $this->messageManager->addErrorMessage(
                __('An unexpected error occurred. Please contact us to get further information.')
            );

            // no further processing and redirect.
            return $this->_redirect('checkout/cart/', ['_secure' => true]);
        }

        // initialize the Response object with data from the request.
        try {
            $this->heidelpayResponse = HeidelpayResponse::fromPost($this->getRequest()->getParams());
        } catch (Exception $e) {
            $this->_logger->error(
                'Heidelpay - Response: Cannot initialize response object from Post Request. ' . $e->getMessage()
            );

            // return the result now, no further processing.
            return $result;
        }

        if(!$this->validateSecurityHash($this->heidelpayResponse)) {
            return $result;
        }

        $this->_logger->debug(
            'Heidelpay - Response: Response object: '
            . print_r($this->heidelpayResponse, true)
        );

        /** @var Order $order */
        $order = null;

        /** @var Quote $quote */
        $quote = null;

        $data = $this->getRequest()->getParams();
        $this->_paymentHelper->saveHeidelpayTransaction($this->heidelpayResponse, $data, 'RESPONSE');

        // if something went wrong, return the redirect url without processing the order.
        if ($this->heidelpayResponse->isError()) {
            $message = sprintf(
                'Heidelpay - Response is NOK. Message: [%s], Reason: [%s] (%d), Code: [%s], Status: [%s] (%d)',
                $this->heidelpayResponse->getError()['message'],
                $this->heidelpayResponse->getProcessing()->reason,
                $this->heidelpayResponse->getProcessing()->reason_code,
                $this->heidelpayResponse->getError()['code'],
                $this->heidelpayResponse->getProcessing()->status,
                $this->heidelpayResponse->getProcessing()->getStatusCode()
            );

            $this->_logger->debug($message);

            // return the heidelpay response url as raw response instead of echoing it out.
            return $result;
        }

        if ($paymentType === TransactionType::INITIALIZE && $paymentMethod === PaymentMethod::HIRE_PURCHASE) {
            if ($this->heidelpayResponse->isSuccess()) {
                $redirectUrl = $this->_url->getUrl('checkout/', [
                    '_forced_secure' => true,
                    '_scope_to_url' => true,
                    '_nosid' => true
                ]) . '#payment';
            }

            // return the heidelpay response url as raw response instead of echoing it out.
            $result->setContents($redirectUrl);
            return $result;
        }

        // Create order if transaction is successful
        if ($this->heidelpayResponse->isSuccess()) {
            try {
                $identificationTransactionId = $this->heidelpayResponse->getIdentification()->getTransactionId();
                // get the quote by transactionid from the heidelpay response
                /** @var Quote $quote */
                $quote = $this->quoteRepository->get($identificationTransactionId);
                $order = $this->_paymentHelper->createOrderFromQuote($quote);
            } catch (Exception $e) {
                $this->_logger->error('Heidelpay - Response: Cannot submit the Quote. ' . $e->getMessage());

                return $result;
            }

            $data['ORDER_ID'] = $order->getIncrementId();

            $this->_paymentHelper->mapStatus($data, $order);
            $this->handleOrderMail($order);
            $this->handleInvoiceMails($order);
            $order->save();
        }

        $this->handleAdditionalPaymentInformation($quote);
        $this->_logger->debug('Heidelpay - Response: redirectUrl is ' . $redirectUrl);

        // return the heidelpay response url as raw response instead of echoing it out.
        return $result;
    }

    //<editor-fold desc="Helpers">

    /**
     * Send order confirmation to the customer
     * @param $order
     */
    protected function handleOrderMail($order)
    {
        try {
            if ($order && $order->getId()) {
                $this->_orderSender->send($order);
            }
        } catch (\Exception $e) {
            $this->_logger->error(
                'Heidelpay - Response: Cannot send order confirmation E-Mail. ' . $e->getMessage()
            );
        }
    }

    /**
     * Send invoice mails to the customer
     * @param $order
     */
    protected function handleInvoiceMails($order)
    {
        if (!$order->canInvoice() && $this->salesHelper->canSendNewInvoiceEmail($order->getStore()->getId())) {
            $invoices = $order->getInvoiceCollection();

            foreach ($invoices as $invoice) {
                $this->_invoiceSender->send($invoice);
            }
        }
    }

    /**
     * If the customer is a guest, we'll delete the additional payment information, which
     * is only used for customer recognition.
     * @param Quote $quote
     * @throws Exception
     */
    protected function handleAdditionalPaymentInformation($quote)
    {
        if ($quote !== null && $quote->getCustomerIsGuest()) {
            // create a new instance for the payment information collection.
            $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

            // load the payment information and delete it.
            /** @var PaymentInformation $paymentInfo */
            $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
                $quote->getStoreId(),
                $quote->getBillingAddress()->getEmail(),
                $quote->getPayment()->getMethod()
            );

            if (!$paymentInfo->isEmpty()) {
                $paymentInfo->delete();
            }
        }
    }

    /**
     * Validate Hash to prevent manipulation
     * @param HeidelpayResponse $response
     * @return bool
     */
    protected function validateSecurityHash($response)
    {
        $secret = $this->_encryptor->exportKeys();
        $identificationTransactionId = $response->getIdentification()->getTransactionId();

        $this->_logger->debug('Heidelpay secret: ' . $secret);
        $this->_logger->debug('Heidelpay identificationTransactionId: ' . $identificationTransactionId);

        try {
            $response->verifySecurityHash($secret, $identificationTransactionId);
            return true;
        } catch (HashVerificationException $e) {
            $this->_logger->critical('Heidelpay Response - HashVerification Exception: ' . $e->getMessage());
            $this->_logger->critical(
                'Heidelpay Response - Received request form server '
                . $this->getRequest()->getServer('REMOTE_ADDR')
                . ' with an invalid hash. This could be some kind of manipulation.'
            );
            $this->_logger->critical(
                'Heidelpay Response - Reference secret hash: '
                . $response->getCriterion()->getSecretHash()
            );
            return false;
        }
    }

    //</editor-fold>
}
