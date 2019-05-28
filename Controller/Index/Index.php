<?php

namespace Heidelpay\Gateway\Controller\Index;

use Exception;
use Heidelpay\Gateway\Controller\HgwAbstract;
use Heidelpay\Gateway\Block\Hgw;
use Heidelpay\Gateway\Helper\Payment as HeidelpayHelper;
use Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod;
use Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Heidelpay\PhpPaymentApi\Response as HeidelpayResponse;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Customer redirect to heidelpay payment or used to display the payment frontend to the customer
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link http://dev.heidelpay.com/magento2
 * @author Jens Richter
 *
 * @package heidelpay\magento2\controllers
 */
class Index extends HgwAbstract
{
    /** @var Escaper */
    private $escaper;

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
        Escaper $escaper
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

    /**
     * {@inheritDoc}
     * @throws InvalidBasketitemPositionException
     * @throws LocalizedException
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

        /** @var HeidelpayAbstractPaymentMethod $payment */
        $payment = $quote->getPayment()->getMethodInstance();

        // get the response object from the initial request.
        /** @var HeidelpayResponse $response */
        $response = $payment->getHeidelpayUrl($quote);

        $this->_logger->debug('Heidelpay init response : ' . print_r($response, 1));

        if ($response->isSuccess()) {
            // redirect to payment url, if it uses redirecting
            if ($payment->activeRedirect() === true) {
                return $this->_redirect($response->getPaymentFormUrl());
            }

            $resultPage = $this->_resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('Please confirm your payment:'));

            /** @var Hgw $hgwBlock */
            $hgwBlock = $resultPage->getLayout()->getBlock('heidelpay_gateway');
            $hgwBlock->setHgwUrl($response->getPaymentFormUrl())->setHgwCode($payment->getCode());

            return $resultPage;
        }

        $this->_logger->error('Heidelpay init error : ' . $response->getError()['message']);

        // get an error message for the given error code, and add it to the message container.
        $message = $this->_paymentHelper->handleError($response->getError()['code']);
        $this->messageManager->addErrorMessage($this->escaper->escapeHtml($message));

        return $this->_redirect('checkout/cart/', ['_secure' => true]);
    }
}
