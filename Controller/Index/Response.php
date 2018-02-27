<?php

namespace Heidelpay\Gateway\Controller\Index;

use Heidelpay\Gateway\Helper\Payment as HeidelpayHelper;
use Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory as PaymentInformationCollectionFactory;
use Heidelpay\PhpPaymentApi\Exceptions\HashVerificationException;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

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
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento
 *
 * @author Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class Response extends \Heidelpay\Gateway\Controller\HgwAbstract
{
    protected $resultPageFactory;
    protected $logger;

    /** @var \Magento\Quote\Model\QuoteRepository */
    protected $quoteRepository;

    /** @var \Magento\Framework\Controller\Result\RawFactory */
    protected $resultFactory;

    /** @var \Heidelpay\PhpPaymentApi\Response The heidelpay response object */
    protected $heidelpayResponse;

    /** @var \Heidelpay\Gateway\Model\TransactionFactory */
    protected $transactionFactory;

    /** @var \Heidelpay\Gateway\Model\ResourceModel\PaymentInformation\CollectionFactory */
    protected $paymentInformationCollectionFactory;

    /**
     * heidelpay Response constructor.
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
     * @param \Magento\Framework\Controller\Result\RawFactory $rawResultFactory
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Heidelpay\PhpPaymentApi\Response $heidelpayResponse
     * @param PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
     * @param \Heidelpay\Gateway\Model\TransactionFactory $transactionFactory
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
        \Magento\Framework\Controller\Result\RawFactory $rawResultFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Heidelpay\PhpPaymentApi\Response $heidelpayResponse,
        PaymentInformationCollectionFactory $paymentInformationCollectionFactory,
        \Heidelpay\Gateway\Model\TransactionFactory $transactionFactory
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
        $this->heidelpayResponse = $heidelpayResponse;
        $this->paymentInformationCollectionFactory = $paymentInformationCollectionFactory;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        // initialize the Raw Response object from the factory.
        $result = $this->resultFactory->create();

        // we just want the response to return a plain url, so we set the header to text/plain.
        $result->setHeader('Content-Type', 'text/plain');

        // the url where the payment will redirect the customer to.
        $redirectUrl = $this->_url->getUrl('hgw/index/redirect', [
            '_forced_secure' => true,
            '_scope_to_url' => true,
            '_nosid' => true
        ]);

        // the payment just wants a url as result, so we set the content to the redirectUrl.
        $result->setContents($redirectUrl);

        // if there is no post request, just do nothing and return the redirectUrl instantly, so an
        // error message can be shown to the customer (which will be created in the redirect controller)
        if (!$this->getRequest()->isPost()) {
            $this->_logger->warning('Heidelpay - Response: Request is not POST.');

            // return the result now, no further processing.
            return $result;
        }

        // initialize the Response object with data from the request.
        try {
            $this->heidelpayResponse->splitArray($this->getRequest()->getParams());
        } catch (\Exception $e) {
            $this->_logger->error(
                'Heidelpay - Response: Cannot initialize response object from Post Request. ' . $e->getMessage()
            );

            // return the result now, no further processing.
            return $result;
        }

        $secret = $this->_encryptor->exportKeys();
        $identificationTransactionId = $this->heidelpayResponse->getIdentification()->getTransactionId();

        $this->_logger->debug('Heidelpay secret: ' . $secret);
        $this->_logger->debug('Heidelpay identificationTransactionId: ' . $identificationTransactionId);

        // validate Hash to prevent manipulation
        try {
            $this->heidelpayResponse->verifySecurityHash($secret, $identificationTransactionId);
        } catch (HashVerificationException $e) {
            $this->_logger->critical("Heidelpay Response - HashVerification Exception: " . $e->getMessage());
            $this->_logger->critical(
                "Heidelpay Response - Received request form server "
                . $this->getRequest()->getServer('REMOTE_ADDR')
                . " with an invalid hash. This could be some kind of manipulation."
            );
            $this->_logger->critical(
                'Heidelpay Response - Reference secret hash: '
                . $this->heidelpayResponse->getCriterion()->getSecretHash()
            );

            return $result;
        }

        $this->_logger->debug(
            'Heidelpay - Response: Response object: '
            . print_r($this->heidelpayResponse, true)
        );

        /** @var \Magento\Sales\Model\Order $order */
        $order = null;

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = null;

        $data = $this->getRequest()->getParams();

        // save the heidelpay transaction data
        list($paymentMethod, $paymentType) = $this->_paymentHelper->splitPaymentCode(
            $this->heidelpayResponse->getPayment()->getCode()
        );

        try {
            // save the response details into the heidelpay Transactions table.
            $transaction = $this->transactionFactory->create();
            $transaction->setPaymentMethod($paymentMethod)
                ->setPaymentType($paymentType)
                ->setTransactionId($this->heidelpayResponse->getIdentification()->getTransactionId())
                ->setUniqueId($this->heidelpayResponse->getIdentification()->getUniqueId())
                ->setShortId($this->heidelpayResponse->getIdentification()->getShortId())
                ->setStatusCode($this->heidelpayResponse->getProcessing()->getStatusCode())
                ->setResult($this->heidelpayResponse->getProcessing()->getResult())
                ->setReturnMessage($this->heidelpayResponse->getProcessing()->getReturn())
                ->setReturnCode($this->heidelpayResponse->getProcessing()->getReturnCode())
                ->setJsonResponse(json_encode($data))
                ->setSource('RESPONSE')
                ->save();
        } catch (\Exception $e) {
            $this->_logger->error('Heidelpay - Response: Save transaction error. ' . $e->getMessage());
        }

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
            $result->setContents($redirectUrl);
            return $result;
        }

        if ($this->heidelpayResponse->isSuccess()) {
            try {
                // get the quote by transactionid from the heidelpay response
                /** @var \Magento\Quote\Model\Quote $quote */
                $quote = $this->quoteRepository->get($this->heidelpayResponse->getIdentification()->getTransactionId());
                $quote->collectTotals();

                // in case of guest checkout, set some customer related data.
                if ($this->getRequest()->getPost('CRITERION_GUEST') === 'true') {
                    $quote->setCustomerId(null)
                        ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                        ->setCustomerIsGuest(true)
                        ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
                }

                // create an order by submitting the quote.
                $order = $this->_cartManagement->submit($quote);
            } catch (\Exception $e) {
                $this->_logger->error('Heidelpay - Response: Cannot submit the Quote. ' . $e->getMessage());

                return $result;
            }

            $data['ORDER_ID'] = $order->getIncrementId();

            $this->_paymentHelper->mapStatus(
                $data,
                $order
            );

            $order->save();
        }

        // if the customer is a guest, we'll delete the additional payment information, which
        // is only used for customer recognition.
        if (isset($quote) && $quote->getCustomerIsGuest()) {
            // create a new instance for the payment information collection.
            $paymentInfoCollection = $this->paymentInformationCollectionFactory->create();

            // load the payment information and delete it.
            /** @var \Heidelpay\Gateway\Model\PaymentInformation $paymentInfo */
            $paymentInfo = $paymentInfoCollection->loadByCustomerInformation(
                $quote->getStoreId(),
                $quote->getBillingAddress()->getEmail(),
                $quote->getPayment()->getMethod()
            );

            if (!$paymentInfo->isEmpty()) {
                $paymentInfo->delete();
            }
        }

        $this->_logger->debug('Heidelpay - Response: redirectUrl is ' . $redirectUrl);

        // return the heidelpay response url as raw response instead of echoing it out.
        $result->setContents($redirectUrl);
        return $result;
    }
}
