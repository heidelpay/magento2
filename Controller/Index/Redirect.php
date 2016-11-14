<?php

namespace Heidelpay\Gateway\Controller\Index;

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
 * @link  https://dev.heidelpay.de/magento
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
use Heidelpay\Gateway\Controller\HgwAbstract;
use Heidelpay\PhpApi\Response AS HeidelpayResponse;

class Redirect extends HgwAbstract
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
     * @return void
     */
    public function execute()
    {
        $session = $this->getCheckout();
        $quoteId = $session->getQuoteId();

        $logger = $this->_logger;
        if (empty($quoteId)) {
            $logger->addDebug('Heidelpay call redirect with empty  quoteId');
            $this->_redirect('checkout/cart/', ['_secure' => true]);

            return;
        }

        $data = null;

        try {
            /** @TODO direct ObjectManager Access should be avoided */
            $objectManager = $this->_objectManager;
            $transaction = $objectManager->create('Heidelpay\Gateway\Model\Transaction');
            $transaction = $transaction->loadLastTransactionByQuoteId($quoteId, 'transactionid');
            $jsonResponse = $transaction->getJsonresponse();
            $data = json_decode($jsonResponse, true);
            $logger->addDebug('Heidelpay redirect data ' . print_r($data, 1));
        } catch (\Exception $e) {
            $logger->error('Heidelpay Redirect load transactions fail. ' . $e->getMessage());
        }

        $heidelpayResponse = new HeidelpayResponse($data);

        $quote = $session->getQuote();
        if ($data !== null && $heidelpayResponse->isSuccess()) {

            /**
             * Set Parameters for Success page
             */
            $quote->setIsActive(false);
            /** @TODO this method call should be changed, because it is deprecated */
            $quote->save();
            $orderFactory = $this->orderFactory;
            $order = $orderFactory->create();

            try {
                $order = $order->loadByAttribute('quote_id', $quoteId);
                /** Send Invoice main to customer */
                $orderSender = $this->orderSender;
                $orderSender->send($order);
            } catch (\Exception $e) {
                $logger->error('Heidelpay Redirect load order fail. ' . $e->getMessage());
            }

            /** Send Invoice main to customer */
            $canInvoice = $order->canInvoice();
            if (!$canInvoice) {
                $invoices = $order->getInvoiceCollection();

                foreach ($invoices AS $invoice) {
                    $invoiceSender = $this->invoiceSender;
                    $invoiceSender->send($invoice);
                }
            }

            $session->clearHelperData();

            /** set QouteIds */
            $session->setLastQuoteId($quoteId);
            $session->setLastSuccessQuoteId($quoteId);

            /** set OrderIds */
            $orderId = $order->getId();
            $incrementId = $order->getIncrementId();
            $status = $order->getStatus();
            $session->setLastOrderId($orderId);
            $session->setLastRealOrderId($incrementId);
            $session->setLastOrderStatus($status);

            $payment = $order->getPayment();
            $methodInstance = $payment->getMethodInstance();
            $additionalPaymentInformation = $methodInstance->additionalPaymentInformation($data);

            $logger->addDebug('Additional Payment Information : ' . $additionalPaymentInformation);

            $checkoutSession = $this->checkoutSession;
            $checkoutSession->setHeidelpayInfo($additionalPaymentInformation);

            $logger->addDebug('Heidelpay redircet to success');
            $this->_redirect('checkout/onepage/success', ['_secure' => true]);

            return;
        } else {
            $quote->setIsActive(true);
            $quote->save();
            $returnCodeExists = array_key_exists('PROCESSING_RETURN_CODE', $data);
            $error_code = ($data !== null && $returnCodeExists) ? $data['PROCESSING_RETURN_CODE'] : null;
            $error_message = ($data !== null && array_key_exists('PROCESSING_RETURN', $data)) ? $data['PROCESSING_RETURN'] : '';
            $logger->error('Heidelpay redircet with error to basket. ' . $error_message);
            $paymentHelper = $this->paymentHelper;
            $message = $paymentHelper->handleError($error_code);
            $this->messageManager->addError($message);

            $this->_redirect('checkout/cart/', ['_secure' => true]);

            return;
        }
    }
}