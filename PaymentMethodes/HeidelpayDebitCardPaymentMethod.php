<?php
namespace Heidelpay\Gateway\PaymentMethodes;

/**
 * Heidelpay dibit card payment method
 *
 * This is the payment class for heidelpay debit card
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
use Heidelpay\PhpApi\PaymentMethodes\DebitCardPaymentMethod as HeidelpayPhpApiDebitCard;
use Heidelpay\Gateway\PaymentMethodes\HeidelpayAbstractPaymentMethod;

class HeidelpayDebitCardPaymentMethod extends HeidelpayAbstractPaymentMethod
{
    /**
     * Payment Code
     *
     * @var string PayentCode
     */
    const CODE = 'hgwdc';
    /**
     * Payment Code
     *
     * @var string PayentCode
     */
    protected $_code = 'hgwdc';
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
     * Active redirect
     *
     * This function will return false, if the used payment method needs additional
     * customer payment data to pursue.
     *
     * @return boolean
     */
    public function activeRedirct()
    {
        return false;
    }
    
    /**
     * Initial Request to heidelpay payment server to get the form / iframe url
     * {@inheritDoc}
     *
     * @see \Heidelpay\Gateway\PaymentMethodes\HeidelpayAbstractPaymentMethod::getHeidelpayUrl()
     */
     public function getHeidelpayUrl($quote)
     {
         $this->_heidelpayPaymentMethod = new HeidelpayPhpApiDebitCard();
         
         parent::getHeidelpayUrl($quote);
         
         /** Force PhpApi to just generate the request instead of sending it directly */
         $this->_heidelpayPaymentMethod->_dryRun = true;
         
         
         $url = explode('/', $this->urlBuilder->getUrl('/', array('_secure' => true)));
         $PaymentFrameOrigin = $url[0].'//'.$url[2];
         $PreventAsyncRedirect = 'FALSE';
         $CssPath = $this->_scopeConfig->getValue("payment/hgwmain/default_css", \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->getStore());
         
         
         /** Set payment type to debit */
         $this->_heidelpayPaymentMethod->debit($PaymentFrameOrigin, $PreventAsyncRedirect, $CssPath);
         
         /** Prepare and send request to heidelpay */
         $request =   $this->_heidelpayPaymentMethod->getRequest()->prepareRequest();
         $response =  $this->_heidelpayPaymentMethod->getRequest()->send($this->_heidelpayPaymentMethod->getPaymentUrl(), $request);
          
         return $response[0];
     }
}
