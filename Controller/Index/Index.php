<?php

namespace Heidelpay\Gateway\Controller\Index;

use Heidelpay\Gateway\Controller\HgwAbstract;
use Magento\Framework\View\Result\Page;

/**
 * Customer redirect to heidelpay payment or used to display the payment frontend to the customer
 *
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link  https://dev.heidelpay.de/magento
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
class Index extends HgwAbstract
{
    /**
     * @var
     */
    protected $resultPageFactory;

    /**
     * @var
     */
    protected $logger;

    /**
     * @return Page|void
     */
    public function execute()
    {
        $session = $this->getCheckout();
        $quote = $session->getQuote();
        $quoteId = $quote->getId();
        $messageManager = $this->messageManager;

        if (!$quoteId) {
            $message = __("An unexpected error occurred. Please contact us to get further information.");
            $objectManager = $this->_objectManager;
            /** @TODO direct ObjectManager Access should be avoided */
            $escaper = $objectManager->get('Magento\Framework\Escaper');
            $escapeHtml = $escaper->escapeHtml($message);
            $messageManager->addError($escapeHtml);
            $this->_redirect('checkout/cart/', ['_secure' => true]);

            return;
        }
        $payment = $quote->getPayment()->getMethodInstance();

        /**
         * start the communication with heidelpay payment
         */
        $data = $payment->getHeidelpayUrl($quote);

        $logger = $this->_logger;
        $logger->addDebug('Heidelpay init respose : ' . print_r($data, 1));

        if ($data['POST_VALIDATION'] == 'ACK' and $data['PROCESSING_RESULT'] == 'ACK') {
            /**
             * Rediret to payment url
             */
            $activeRedirect = $payment->activeRedirct();
            if ($activeRedirect === true) {
                $frontendRedirectUrl = $data['FRONTEND_REDIRECT_URL'];
                $this->_redirect($frontendRedirectUrl);

                return;
            }

            $pageFactory = $this->_resultPageFactory;
            $resultPage = $pageFactory->create();
            $config = $resultPage->getConfig();
            $title = $config->getTitle();
            $prefix = __('Please confirm your payment:');
            $title->prepend($prefix);
            $layout = $resultPage->getLayout();
            $paymentCode = $payment->getCode();
            $heidelpayGatewayBlock = $layout->getBlock('heidelpay_gateway');

            $heidelpayGatewayBlock->setHgwUrl($data['FRONTEND_PAYMENT_FRAME_URL']);
            $heidelpayGatewayBlock->setHgwCode($paymentCode);

            return $resultPage;

        } else {
            $returnCodeExists = array_key_exists('PROCESSING_RETURN_CODE', $data);
            $errorCode = ($returnCodeExists) ? $data['PROCESSING_RETURN_CODE'] : null;
            $logger->error('Heidelpay init error : ' . $data['PROCESSING_RETURN']);
            $paymentHelper = $this->paymentHelper;
            $message = $paymentHelper->handleError($errorCode);
            $messageManager->addError($message);

            $this->_redirect('checkout/cart/', ['_secure' => true]);

            return;
        }
    }
}