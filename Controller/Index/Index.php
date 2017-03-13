<?php
namespace Heidelpay\Gateway\Controller\Index;

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

class Index extends \Heidelpay\Gateway\Controller\HgwAbstract
{
    protected $resultPageFactory;
    protected $logger;


    public function execute()
    {
        $session = $this->getCheckout();
        $quote = $session->getQuote();

        if (!$quote->getId()) {
            $message = __("An unexpected error occurred. Please contact us to get further information.");
            $this->messageManager->addErrorMessage(
                $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($message)
            );

            $this->_redirect('checkout/cart/', ['_secure' => true]);

            return;
        }
        $payment = $quote->getPayment()->getMethodInstance();

        // start the communication with heidelpay payment
        $data = $payment->getHeidelpayUrl($quote);

        $this->_logger->debug('Heidelpay init respose : ' . print_r($data, 1));

        // response is acknowledged
        if ($data['POST_VALIDATION'] == 'ACK' and $data['PROCESSING_RESULT'] == 'ACK') {
            // redirect to payment url
            if ($payment->activeRedirct() === true) {
                $this->_redirect($data['FRONTEND_REDIRECT_URL']);
                return;
            }

            $resultPage = $this->_resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(__('Please confirm your payment:'));
            $resultPage->getLayout()->getBlock('heidelpay_gateway')->setHgwUrl($data['FRONTEND_PAYMENT_FRAME_URL']);
            $resultPage->getLayout()->getBlock('heidelpay_gateway')->setHgwCode($payment->getCode());

            return $resultPage;
        }

        $error_code = (array_key_exists('PROCESSING_RETURN_CODE', $data)) ? $data['PROCESSING_RETURN_CODE'] : null;
        $this->_logger->error('Heidelpay init error : ' . $data['PROCESSING_RETURN']);
        $message = $this->_paymentHelper->handleError($error_code);
        $this->messageManager->addErrorMessage(
            $message
        );

        $this->_redirect('checkout/cart/', ['_secure' => true]);

        return;
    }
}
