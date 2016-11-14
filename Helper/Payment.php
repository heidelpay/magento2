<?php

namespace Heidelpay\Gateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\Phrase;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Heidelpay payment helper
 *
 * The payment helper is a collection of function to prepare an send
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
class Payment extends AbstractHelper
{
    /**
     * @var bool
     */
    protected $_invoiceOrderEmail = true;

    /**
     * @var bool
     */
    protected $_debug = false;

    /**
     * @var ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Logger
     */
    protected $log;


    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;


    /**
     * @param ZendClientFactory $httpClientFactory
     * @param Logger $logger
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        Logger $logger,
        TransactionFactory $transactionFactory
    )
    {
        $this->httpClientFactory = $httpClientFactory;
        $this->log = $logger;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param $PAYMENT_CODE string
     * @return array
     */
    public function splitPaymentCode($PAYMENT_CODE)
    {
        $splittedPaymentCode = preg_split('/\./', $PAYMENT_CODE);

        return $splittedPaymentCode;

    }

    /**
     * @param $url
     * @param array $params
     * @return mixed|null
     */
    public function doRequest($url, $params = [])
    {
        $httpClientFactory = $this->httpClientFactory;
        $client = $httpClientFactory->create();

        $client->setUri(trim($url));

        if (array_key_exists('raw', $params)) {
            $jsonEncodedData = json_encode($params['raw']);
            $client->setRawData($jsonEncodedData, 'application/json');
        } else {
            $client->setParameterPost($params);
        }

        $response = $client->request('POST');
        $res = $response->getBody();
        $isError = $response->isError();

        if ($isError) {
            $status = $response->getStatus();
            $this->log("Request fail. Http code : " . $status . ' Message : ' . $res, 'ERROR');
            $this->log("Request data : " . print_r($params, 1), 'ERROR');
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

    /**
     * @TODO Refactor this method. The cyclomatic complexity is realy high and it is hard to debug
     *
     * @param array $config
     * @param array $frontend
     * @param array $userData
     * @param array $basketData
     * @param array $criterion
     * @return array
     */
    public function preparePostData(
        $config = [],
        $frontend = [],
        $userData = [],
        $basketData = [],
        $criterion = []
    )
    {
        $params = [];
        /**
         * Configuration part of this function
         */
        $params['SECURITY.SENDER'] = $config['SECURITY.SENDER'];
        $params['USER.LOGIN'] = $config['USER.LOGIN'];
        $params['USER.PWD'] = $config['USER.PWD'];

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
        $params['TRANSACTION.CHANNEL'] = $config['TRANSACTION.CHANNEL'];


        /**
         * Set payment method
         */
        switch ($config['PAYMENT.METHOD']) {
            /** Sofortbanking */
            case 'sue':
                $params['ACCOUNT.BRAND'] = "SOFORT";
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT." . $type;
                break;
            /** Giropay */
            case 'gp':
                $params['ACCOUNT.BRAND'] = "GIROPAY";
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT." . $type;
                break;
            /** Ideal */
            case 'ide':
                $params['ACCOUNT.BRAND'] = "IDEAL";
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT." . $type;
                break;
            /** Eps */
            case 'eps':
                $params['ACCOUNT.BRAND'] = "EPS";
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT." . $type;
                break;
            /** przelewy24 */
            case 'p24':
                $params['ACCOUNT.BRAND'] = "PRZELEWY24";
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT." . $type;
                break;
            /** Postfinace */
            case 'pf':
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "OT." . $type;
                break;
            /** Paypal */
            case 'pal';
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'DB' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "VA." . $type;
                $params['ACCOUNT.BRAND'] = "PAYPAL";
                break;
            /** Prepayment */
            case 'pp' :
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "PP." . $type;
                break;
            /** Invoce */
            case 'iv' :
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "IV." . $type;
                break;
            /** BillSafe */
            case 'bs' :
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "IV." . $type;
                $params['ACCOUNT.BRAND'] = "BILLSAFE";
                $params['FRONTEND.ENABLED'] = "false";
                break;
            /** MangirKart */
            case 'mk' :
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = "PC." . $type;
                $params['ACCOUNT.BRAND'] = "MANGIRKART";
                $params['FRONTEND.ENABLED'] = "false";
                break;
            /** MasterPass */
            case 'mpa' :
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'DB' : $config['PAYMENT.TYPE'];

                // masterpass as a payment method
                if (!array_key_exists('IDENTIFICATION.REFERENCEID', $userData) and ($type == 'DB' or $type == 'PA')) {
                    $params['WALLET.DIRECT_PAYMENT'] = "true";
                    $params['WALLET.DIRECT_PAYMENT_CODE'] = "WT." . $type;
                    $type = 'IN';

                }

                $params['PAYMENT.CODE'] = "WT." . $type;
                $params['ACCOUNT.BRAND'] = "MASTERPASS";
                break;
            /** Default */
            default:
                $type = (!array_key_exists('PAYMENT.TYPE', $config)) ? 'PA' : $config['PAYMENT.TYPE'];
                $params['PAYMENT.CODE'] = strtoupper($config['PAYMENT.METHOD']) . '.' . $type;
                break;
        }

        /** Debit on registration */
        if (array_key_exists('ACCOUNT.REGISTRATION', $config)) {
            $params['ACCOUNT.REGISTRATION'] = $config['ACCOUNT.REGISTRATION'];
            $params['FRONTEND.ENABLED'] = "false";
        }

        if (array_key_exists('SHOP.TYPE', $config)) {
            $params['SHOP.TYPE'] = $config['SHOP.TYPE'];
        }

        if (array_key_exists('SHOPMODUL.VERSION', $config)) {
            $params['SHOPMODUL.VERSION'] = $config['SHOPMODUL.VERSION'];
        }

        /** frontend configuration */

        /** override FRONTEND.ENABLED if nessessary */
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
        };

        $params = array_merge($params, $frontend);

        /** costumer data configuration */
        $params = array_merge($params, $userData);

        /** basket data configuration */
        $params = array_merge($params, $basketData);

        /** criterion data configuration */
        $params = array_merge($params, $criterion);

        $params['REQUEST.VERSION'] = "1.0";

        return $params;
    }

    /**
     * @param $data
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param bool $message
     */
    public function mapStatus($data, $order, $message = false)
    {

        $paymentCode = $this->splitPaymentCode($data['PAYMENT_CODE']);
        $message = (!empty($message)) ? $message : $data['PROCESSING_RETURN'];

        /**
         * If an order has been canceled, cloesed or complete do not change order status
         */
        $orderStatus = $order->getStatus();
        if ($orderStatus == Order::STATE_CANCELED ||
            $orderStatus == Order::STATE_CLOSED ||
            $orderStatus == Order::STATE_COMPLETE
        ) {
            // you can use this event for example to get a notification when a canceled order has been paid
            /** @TODO Implement Event */
            return;
        }

        $payment = $order->getPayment();
        $transactionId = $data['IDENTIFICATION_UNIQUEID'];
        if ($data['PROCESSING_RESULT'] == 'NOK') {
            $canCancel = $order->canCancel();
            if ($canCancel) {
                $state = Order::STATE_CANCELED;
                $status = Order::STATE_CANCELED;
                $order->cancel();
                $order->setState($state);
                $order->addStatusHistoryComment($message, $status);
                $order->setIsCustomerNotified(false);
            }

        } elseif (
            ($paymentCode[1] == 'CP' || $paymentCode[1] == 'DB' || $paymentCode[1] == 'FI' || $paymentCode[1] == 'RC') &&
            ($data['PROCESSING_RESULT'] == 'ACK' && $data['PROCESSING_STATUS_CODE'] != 80)
        ) {
            $message = __('ShortId : %1', $data['IDENTIFICATION_SHORTID']);
            $payment->setTransactionId($transactionId);
            $lastTransId = $payment->getLastTransId();
            $payment->setParentTransactionId($lastTransId);
            $payment->setIsTransactionClosed(true);
            $grandTotal = $order->getGrandTotal();
            $orderCurrencyCode = $order->getOrderCurrencyCode();

            $formatedGrandTotal = $this->format($grandTotal);
            if ($formatedGrandTotal == $data['PRESENTATION_AMOUNT'] && $orderCurrencyCode == $data['PRESENTATION_CURRENCY']) {
                $state = Order::STATE_PROCESSING;
                $status = Order::STATE_PROCESSING;
                $order->setState($state);
                $order->addStatusHistoryComment($message, $status);
                $order->setIsCustomerNotified(true);
            } else {
                /**
                 * In case rc is ack and amount is to low/heigh or curreny missmatch
                 */
                $message = __('Amount or currency missmatch : %1', $data['PRESENTATION_AMOUNT'] . ' ' . $data['PRESENTATION_CURRENCY']);
                $state = Order::STATE_PAYMENT_REVIEW;
                $status = Order::STATE_PAYMENT_REVIEW;
                $order->setState($state);
                $order->addStatusHistoryComment($message, $status);
                $order->setIsCustomerNotified(true);
            }

            $canInvoice = $order->canInvoice();
            if ($canInvoice) {
                $invoice = $order->prepareInvoice();
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->setIsPaid(true);
                $invoice->pay();
                $transactionFactory = $this->transactionFactory;
                $transaction = $transactionFactory->create();
                $transaction->addObject($invoice);
                $transaction->addObject($invoice->getOrder());
                $transaction->save();
            };

            $payment->addTransaction(Transaction::TYPE_CAPTURE, null, true);
        } else {
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionClosed(false);
            $payment->addTransaction(Transaction::TYPE_AUTH, null, true);
            $state = Order::STATE_PENDING_PAYMENT;
            $status = Order::STATE_PENDING_PAYMENT;
            $order->setState($state);
            $order->addStatusHistoryComment($message, $status);
            $order->setIsCustomerNotified(true);
        }
    }

    /**
     * function to format amount
     * @param $number
     * @return string
     */
    public function format($number)
    {
        return number_format($number, 2, '.', '');
    }

    /**
     * @param string $default
     * @return string
     */
    public function getLang($default = 'en')
    {
        /**
         * @TODO The Mage::app() is Magento 1 Code and won't work in Mage2.
         * @TODO It could cause errors, so we better wrap it into a comment
         */
        //$locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        //if (!empty($locale)) {
        //    return strtoupper($locale[0]);
        //}

        return strtoupper($default); //TOBO falses Module
    }

    /**
     * helper to generate customer payment error messages
     * @param null $errorCode
     * @return Phrase
     */
    public function handleError($errorCode = null)
    {
        // default is return generic error message
        if ($errorCode !== null) {
            if (!preg_match('/HPError-[0-9]{3}\.[0-9]{3}\.[0-9]{3}/', __('HPError-' . $errorCode), $matches)) //JUST return when snipet exists
            {
                return __('HPError-' . $errorCode);
            }
        }

        $phrase = __('An unexpected error occurred. Please contact us to get further information.');

        return $phrase;
    }
}
