<?php
namespace Heidelpay\Gateway\PaymentMethodes;

/**
 * Heidelpay prepayment payment method
 *
 * This is the payment class for heidelpay prepayment
 *
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/magento
 *
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
use Heidelpay\PhpApi\PaymentMethodes\PrepaymentPaymentMethod as HeidelpayPhpApiPrepayment;

class HeidelpayPrepaymentPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     *
     * @var string PayentCode
     */
    const CODE = 'hgwpp';
    /**
     * Payment Code
     *
     * @var string PayentCode
     */
    protected $_code = 'hgwpp';
    /**
     * isGateway
     *
     * @var boolean
     */
    protected $_isGateway                   = true;
    /**
     * canAuthorize
     *
     * @var boolean
     */
    protected $_canAuthorize                = true;
    
    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     *
     * @see \Heidelpay\Gateway\PaymentMethodes\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
     public function getHeidelpayUrl($quote)
     {
         $this->_heidelpayPaymentMethod = new HeidelpayPhpApiPrepayment();
         
         parent::getHeidelpayUrl($quote);
         
         /** Force PhpApi to just generate the request instead of sending it directly */
         $this->_heidelpayPaymentMethod->_dryRun = true;
         
         
         /** Set payment type to authorize */
         $this->_heidelpayPaymentMethod->authorize();
         
         /** Prepare and send request to heidelpay */
         $request =   $this->_heidelpayPaymentMethod->getRequest()->prepareRequest();
         $response =  $this->_heidelpayPaymentMethod->getRequest()->send($this->_heidelpayPaymentMethod->getPaymentUrl(), $request);
          
         return $response[0];
     }
     
     /**
      * Additional payment information
      *
      * This function will return a text message used to show payment information
      * to your customer on the checkout success page
      *
      * @param unknown $Response
      *
      * @return string|boolean payment information or false
      */
     public function additionalPaymentInformation($Response)
     {
         return __('Please transfer the amount of <strong>%1 %2</strong> to the following account<br /><br />Holder: %3<br/>IBAN: %4<br/>BIC: %5<br/><br/><i>Please use only this identification number as the descriptor :</i><br/><strong>%6</strong>',
             $Response['PRESENTATION_AMOUNT'],
             $Response['PRESENTATION_CURRENCY'],
             $Response['CONNECTOR_ACCOUNT_HOLDER'],
             $Response['CONNECTOR_ACCOUNT_IBAN'],
             $Response['CONNECTOR_ACCOUNT_BIC'],
             $Response['IDENTIFICATION_SHORTID']);
     }
}
