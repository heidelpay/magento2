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
use Heidelpay\PhpApi\Response AS HeidelpayResponse;

class Redirect extends \Heidelpay\Gateway\Controller\HgwAbstract
{
    protected $resultPageFactory;
    protected $logger;
    
 
    public function execute()
    {
    	
    	$session = $this->getCheckout();
    	
    	$quoteId = $session->getQuoteId();
    	
    	if(empty($quoteId))  {
    		$this->_logger->addDebug('Heidelpay call redirect with empty  quoteId');
    		$this->_redirect('checkout/cart/', array('_secure' => true));
    		return ;
    	}
    	
    	$data = NULL;
    	
    	try {
				$transaction = $this->_objectManager->create('Heidelpay\Gateway\Model\Transaction')->loadLastTransactionByQuoteId( $quoteId , 'transactionid');
				$data = json_decode($transaction->getJsonresponse(), true);
				$this->_logger->addDebug('Heidelpay redirect data '.print_r($data,1));
    	} catch (\Exception $e) {
    		$this->_logger->error('Heidelpay Redirect load transactions fail. '.$e->getMessage());
    	}
		
    	$HeidelpayResponse = new  HeidelpayResponse($data);
				
		if ($data !== NULL && $HeidelpayResponse->isSuccess()){
			
			/*
			 * Set Parameters for Success page
			 */
			
			$session->getQuote()->setIsActive(false)->save();
			try{
				$order = $this->_orderFactory->create()->loadByAttribute('quote_id',$quoteId);
				/** Sende Invoice main to customer */
				$this->_orderSender->send($order);
			} catch (\Exception $e) {
				$this->_logger->error('Heidelpay Redirect load order fail. '.$e->getMessage());
			}
			
			/** Sende Invoice main to customer */
			if (!$order->canInvoice()) {
				$invoices = $order->getInvoiceCollection();
				
				foreach ($invoices AS $invoice)
					$this->_invoiceSender->send($invoice);
				
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
			
			$additionalPaymentInformation = $order->getPayment()->getMethodInstance()->additionalPaymentInformation($data);
			
			$this->_logger->addDebug('Additional Payment Information : '.$additionalPaymentInformation);
			
			$this->_checkoutSession->setHeidelpayInfo($additionalPaymentInformation);
			
			$this->_logger->addDebug('Heidelpay redircet to success');
			$this->_redirect('checkout/onepage/success', array('_secure' => true));
			return;
			
			
		} else {
			$session->getQuote()->setIsActive(true)->save();
			$error_code  = ($data !== NULL && array_key_exists('PROCESSING_RETURN_CODE', $data)) ? $data['PROCESSING_RETURN_CODE'] : null;
			$error_message  = ($data !== NULL && array_key_exists('PROCESSING_RETURN', $data)) ? $data['PROCESSING_RETURN'] : '';
			$this->_logger->error('Heidelpay redircet with error to basket. '.$error_message);
			$message = $this->_paymentHelper->handleError($error_code);
			$this->messageManager->addError(
					$message
					);

			$this->_redirect('checkout/cart/', array('_secure' => true));
			return;
			
			
		}
		
	   	
    }
}