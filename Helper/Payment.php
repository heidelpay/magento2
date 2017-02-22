<?php
namespace Heidelpay\Gateway\Helper;

/**
 * Heidelpay payment helper
 *
 * The payment helper is a collection of function to prepare an send
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
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 */
class Payment extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_invoiceOrderEmail = true;
    protected $_debug              = false;
    
    
    protected $httpClientFactory;
    protected $log;
    

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;
    
    /**
     * @param ZendClientFactory $httpClientFactory
     * @param Random            $mathRandom
     * @param Logger            $logger
     */
    public function __construct(
            ZendClientFactory $httpClientFactory,
            Logger $logger,
            \Magento\Framework\DB\TransactionFactory $transactionFactory
            ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->log = $logger;
        $this->transactionFactory = $transactionFactory;
    }
    
    public function splitPaymentCode($PAYMENT_CODE)
    {
        return preg_split('/\./', $PAYMENT_CODE);
    }
    
    public function doRequest($url, $params=array())
    {
        $client = $this->httpClientFactory->create();
        
        $client->setUri(
                trim($url)
                );
        
        if (array_key_exists('raw', $params)) {
            $client->setRawData(json_encode($params['raw']), 'application/json');
        } else {
            $client->setParameterPost($params);
        }
        
        
        $response = $client->request('POST');
        $res = $response->getBody();
        
        
        if ($response->isError()) {
            $this->log("Request fail. Http code : ".$response->getStatus().' Message : '.$res, 'ERROR');
            $this->log("Request data : ".print_r($params, 1), 'ERROR');
            if (array_key_exists('raw', $params)) {
                return $response;
            }
        }
        
        if (array_key_exists('raw', $params)) {
            return json_decode($res, true);
        }
        
        $result = null;
        parse_str($res, $result);
        
        return $result;
    }
    
    public function preparePostData($config    = array(),
        $frontend    = array(),
            $userData    = array(),
                $basketData = array(),
                    $criterion = array())
    {
        $params = array();
        /*
         * configurtation part of this function
         */
        $params['SECURITY.SENDER']    = $config['SECURITY.SENDER'];
        $params['USER.LOGIN']        = $config['USER.LOGIN'];
        $params['USER.PWD']        = $config['USER.PWD'];
        
        switch ($config['TRANSACTION.MODE']) {
            case 'INTEGRATOR_TEST':
                $params['TRANSACTION.MODE'] = 'INTEGRATOR_TEST';
                break;
            case 'CONNECTOR_TEST':
                $params['TRANSACTION.MODE'] = 'CONNECTOR_TEST';
                break;
            default:
                $params['TRANSACTION.MODE'] = 'LIVE';
        }
        $params['TRANSACTION.CHANNEL']    = $config['TRANSACTION.CHANNEL'];
        
        
        /* Set payment methode */
        switch ($config['PAYMENT.METHOD']) {
        /* sofortbanking */
            case 'sue':
                $params['ACCOUNT.BRAND']            = "SOFORT";
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT.".$type;
                break;
                /* griopay */
            case 'gp':
                $params['ACCOUNT.BRAND']            = "GIROPAY";
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT.".$type;
                break;
                /* ideal */
            case 'ide':
                $params['ACCOUNT.BRAND']            = "IDEAL";
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT.".$type;
                break;
                /* eps */
            case 'eps':
                $params['ACCOUNT.BRAND']            = "EPS";
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT.".$type;
                break;
                /* przelewy24 */
            case 'p24':
                $params['ACCOUNT.BRAND']            = "PRZELEWY24";
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT.".$type;
                break;
                /* postfinace */
            case 'pf':
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT.".$type;
                break;
                /* paypal */
            case 'pal':
            $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'DB' : $config['PAYMENT.TYPE'];
            $params['PAYMENT.CODE'] = "VA.".$type;
            $params['ACCOUNT.BRAND'] = "PAYPAL";
            break;
            /* prepayment */
            case 'pp':
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "PP.".$type;
                break;
                /* invoce */
            case 'iv':
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "IV.".$type;
                break;
                /* BillSafe */
            case 'bs':
            $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
            $params['PAYMENT.CODE'] = "IV.".$type;
            $params['ACCOUNT.BRAND']    = "BILLSAFE";
            $params['FRONTEND.ENABLED']            =    "false";
            break;
            /* MangirKart */
            case 'mk':
            $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
            $params['PAYMENT.CODE'] = "PC.".$type;
            $params['ACCOUNT.BRAND'] = "MANGIRKART";
            $params['FRONTEND.ENABLED']            =    "false";
            break;
            /* MasterPass */
            case 'mpa':
            $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'DB' : $config['PAYMENT.TYPE'];
            
            // masterpass as a payment methode
            if (!array_key_exists('IDENTIFICATION.REFERENCEID', $userData) and($type == 'DB' or $type == 'PA')) {
                $params['WALLET.DIRECT_PAYMENT'] = "true";
                $params['WALLET.DIRECT_PAYMENT_CODE'] = "WT.".$type;
                $type = 'IN';
            }
            
            $params['PAYMENT.CODE']    = "WT.".$type;
            $params['ACCOUNT.BRAND']    = "MASTERPASS";
            break;
            /* default */
            default:
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = strtoupper($config['PAYMENT.METHOD']).'.'.$type;
            break;
        }
        
        /* Debit on registration */
        if (array_key_exists('ACCOUNT.REGISTRATION', $config)) {
            $params['ACCOUNT.REGISTRATION'] = $config['ACCOUNT.REGISTRATION'];
            $params['FRONTEND.ENABLED']        =    "false";
        }
        
        if (array_key_exists('SHOP.TYPE', $config)) {
            $params['SHOP.TYPE'] = $config['SHOP.TYPE'];
        }
        if (array_key_exists('SHOPMODUL.VERSION', $config)) {
            $params['SHOPMODUL.VERSION'] = $config['SHOPMODUL.VERSION'];
        }
        
        /* frontend configuration */
        
        /* override FRONTEND.ENABLED if nessessary */
        if (array_key_exists('FRONTEND.ENABLED', $frontend)) {
            $params['FRONTEND.ENABLED'] = $frontend['FRONTEND.ENABLED'];
            unset($frontend['FRONTEND.ENABLED']);
        }
        
        if (array_key_exists('FRONTEND.MODE', $frontend)) {
            $params['FRONTEND.MODE'] = $frontend['FRONTEND.MODE'];
            unset($frontend['FRONTEND.MODE']);
        } else {
            $params['FRONTEND.MODE'] = "WHITELABEL";
            $params['TRANSACTION.RESPONSE'] = "SYNC";
            $params['FRONTEND.ENABLED'] = 'true';
        }
        
        
        $params = array_merge($params, $frontend);
        
        /* costumer data configuration */
        $params = array_merge($params, $userData);
        
        /* basket data configuration */
        $params = array_merge($params, $basketData);
        
        /* criterion data configuration */
        $params = array_merge($params, $criterion);
        
        $params['REQUEST.VERSION']            =    "1.0";
        
        return $params;
    }
    
    public function mapStatus($data, $order, $message=false)
    {
        $PaymentCode = $this->splitPaymentCode($data['PAYMENT_CODE']);
        $totalypaid = false;
        
        $message = (!empty($message))  ? $message : $data['PROCESSING_RETURN'];
        
        $quoteID = ($order->getLastQuoteId() === false) ? $order->getQuoteId() : $order->getLastQuoteId(); // last_quote_id workaround for trusted shop buyerprotection
        
        /**
         * If an order has been canceled, cloesed or complete do not change order status
         */
        if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_CANCELED or
            $order->getStatus() == \Magento\Sales\Model\Order::STATE_CLOSED   or
            $order->getStatus() == \Magento\Sales\Model\Order::STATE_COMPLETE
            ) {
                
            // you can use this event for example to get a notification when a canceled order has been paid
            return;
        }
        
        if ($data['PROCESSING_RESULT'] == 'NOK') {
            if ($order->canCancel()) {
                $order->cancel();
                
                $state = \Magento\Sales\Model\Order::STATE_CANCELED;
                $status = \Magento\Sales\Model\Order::STATE_CANCELED;
        
                $order    ->setState($state)
                        ->addStatusHistoryComment($message, $status)
                        ->setIsCustomerNotified(false);
            }
        } elseif (($PaymentCode[1] == 'CP' or    $PaymentCode[1] == 'DB' or $PaymentCode[1] == 'FI' or $PaymentCode[1] == 'RC')
            and    ($data['PROCESSING_RESULT'] == 'ACK' and $data['PROCESSING_STATUS_CODE'] != 80)) {
            $message = __('ShortId : %1', $data['IDENTIFICATION_SHORTID']);
            
            $order->getPayment()->setTransactionId($data['IDENTIFICATION_UNIQUEID'])
                                ->setParentTransactionId($order->getPayment()->getLastTransId())
                                ->setIsTransactionClosed(true);
            
            if ($this->format($order->getGrandTotal()) == $data['PRESENTATION_AMOUNT'] and $order->getOrderCurrencyCode() == $data['PRESENTATION_CURRENCY']) {
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
        
                $order    ->setState($state)
                        ->addStatusHistoryComment($message, $status)
                        ->setIsCustomerNotified(true);
                $totalypaid = true;
            } else {
                /*
                 * in case rc is ack and amount is to low/heigh or curreny missmatch
                 */
                $message = __('Amount or currency missmatch : %1', $data['PRESENTATION_AMOUNT'] .' '. $data['PRESENTATION_CURRENCY']);
                $state = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
                $status = \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW;
                $order    ->setState($state)
                        ->addStatusHistoryComment($message, $status)
                        ->setIsCustomerNotified(true);
            }
            
            
            
            if ($order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->setIsPaid(true);
                $invoice->pay();
                $transaction = $this->transactionFactory->create();
                $transaction->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();
            }
            
            $order->getPayment()->addTransaction(
                Transaction::TYPE_CAPTURE,
                null,
                true
            );
        } else {
            $order->getPayment()->setTransactionId($data['IDENTIFICATION_UNIQUEID']);
            $order->getPayment()->setIsTransactionClosed(false);
                
            $order->getPayment()->addTransaction(
                        Transaction::TYPE_AUTH,
                        null,
                        true
                );
            $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            $status = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            $order    ->setState($state)
                ->addStatusHistoryComment($message, $status)
                ->setIsCustomerNotified(true);
        }
    }
    
    /**
     * function to format amount
     *
     * @param mixed $number
     */
    public function format($number)
    {
        return number_format($number, 2, '.', '');
    }
    
    public function getLang($default='en')
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        if (!empty($locale)) {
            return strtoupper($locale[0]);
        }
        return strtoupper($default); //TOBO falses Module
    }
    
    /**
     * helper to generate customer payment error messages
     *
     * @param null|mixed $errorCode
     */
    public function handleError($errorCode=null)
    {
        // default is return generic error message
        
        if ($errorCode !== null) {
            if (!preg_match('/HPError-[0-9]{3}\.[0-9]{3}\.[0-9]{3}/', __('HPError-'.$errorCode), $matches)) { //JUST return when snipet exists
                return __('HPError-'.$errorCode);
            }
        }
        
        return __('An unexpected error occurred. Please contact us to get further information.');
    }
}
