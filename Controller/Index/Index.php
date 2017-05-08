<?php

namespace Heidelpay\Gateway\Controller\Index;

use Heidelpay\Gateway\Helper\Payment as HeidelpayHelper;
use Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

/**
 * Customer redirect to heidelpay payment or used to display the payment frontend to the customer
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
class Index extends \Heidelpay\Gateway\Controller\HgwAbstract
{
    protected $resultPageFactory;
    protected $logger;

    /** @var \Magento\Framework\Escaper */
    protected $escaper;


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
        \Magento\Framework\Escaper $escaper
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
    }

    public function execute()
    {
        $session = $this->getCheckout();
        $quote = $session->getQuote();

        if (!$quote->getId()) {
            $message = __("An unexpected error occurred. Please contact us to get further information.");
            $this->messageManager->addErrorMessage($this->escaper->escapeHtml($message));

            return $this->_redirect('checkout/cart/', ['_secure' => true]);
        }

        /** @var HeidelpayAbstractPaymentMethod $payment */
        $payment = $quote->getPayment()->getMethodInstance();

        // get the response object from the initial request.
        /** @var \Heidelpay\PhpApi\Response $response */
        $response = $payment->getHeidelpayUrl($quote);

        $this->_logger->debug('Heidelpay init respose : ' . print_r($response, 1));

        if ($response->isSuccess()) {
            // redirect to payment url, if it uses redirecting
            if ($payment->activeRedirect() === true) {
                return $this->_redirect($response->getPaymentFormUrl());
            }

            $resultPage = $this->_resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('Please confirm your payment:'));
            $resultPage->getLayout()->getBlock('heidelpay_gateway')->setHgwUrl(
                $response->getPaymentFormUrl()
            );
            $resultPage->getLayout()->getBlock('heidelpay_gateway')->setHgwCode($payment->getCode());

            return $resultPage;
        }

        $this->_logger->error('Heidelpay init error : ' . $response->getError()['message']);

        // get an error message for the given error code, and add it to the message container.
        $message = $this->_paymentHelper->handleError($response->getError()['code']);
        $this->messageManager->addErrorMessage($this->escaper->escapeHtml($message));

        return $this->_redirect('checkout/cart/', ['_secure' => true]);
    }
}
