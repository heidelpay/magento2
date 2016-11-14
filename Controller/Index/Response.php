<?php

namespace Heidelpay\Gateway\Controller\Index;

/**
 * Notification handler for the payment response
 *
 * The heidelpay payment server will call this page directly after the payment
 * process to send the result of the payment to your shop. Please make sure
 * that this page is reachable form the Internet without any authentication.
 *
 * The controller use cryptographic methods to protect your shop in case of
 * fake payment responses. The plugin can not take care of man in the middle attacks,
 * so please make sure that you use https for the checkout process.
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
use Magento\Customer\Model\Group;

class Response extends HgwAbstract
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
        $request = $this->getRequest();
        $data = [];

        /**
         * Quit processing on an empty post response
         */
        $data['PROCESSING_RESULT'] = $request->getPost('PROCESSING_RESULT');
        $data['CRITERION_SECRET'] = $request->getPost('CRITERION_SECRET');
        $data['IDENTIFICATION_TRANSACTIONID'] = $request->getPost('IDENTIFICATION_TRANSACTIONID');

        /** @TODO this should be included by dependency injection */
        $heidelpayResponse = new HeidelpayResponse($data);
        $encryptor = $this->encryptor;
        $secret = $encryptor->exportKeys();
        $identificationParameterGroup = $heidelpayResponse->getIdentification();
        $identificationTransactionId = $identificationParameterGroup->getTransactionId();
        $logger = $this->_logger;
        $logger->addDebug('Heidelpay response postdata : ' . print_r($heidelpayResponse, 1));
        $logger->addDebug('Heidelpay $secret: ' . print_r($secret, 1));
        $logger->addDebug('Heidelpay $$identificationTransactionId: ' . print_r($identificationTransactionId, 1));

        /**
         * validate Hash to prevent manipulation
         */
        $url = $this->_url;
        try {
            $heidelpayResponse->verifySecurityHash($secret, $identificationTransactionId);
        } catch (\Exception $e) {
            $logger->critical("Heidelpay response object fail " . $e->getMessage());
            $logger->critical("Heidelpay response object form server " . $request->getServer('REMOTE_ADDR') . " with an invalid hash. This could be some kind of manipulation.");
            $criterionParameterGroup = $heidelpayResponse->getCriterion();
            $logger->critical('Heidelpay reference object hash ' . $criterionParameterGroup->getSecretHash());

            /** @TODO there shouldn't be a reason to echo something. Let's find a better solution */
            echo $url->getUrl('hgw/index/redirect', $routeParams);

            return;
        }

        $data['IDENTIFICATION_TRANSACTIONID'] = (int)$request->getPost('IDENTIFICATION_TRANSACTIONID');
        $data['PROCESSING_STATUS_CODE'] = (int)$request->getPost('PROCESSING_STATUS_CODE');
        $data['PROCESSING_RETURN'] = $request->getPost('PROCESSING_RETURN');
        $data['PROCESSING_RETURN_CODE'] = $request->getPost('PROCESSING_RETURN_CODE');
        $data['PAYMENT_CODE'] = $request->getPost('PAYMENT_CODE');
        $data['IDENTIFICATION_UNIQUEID'] = $request->getPost('IDENTIFICATION_UNIQUEID');
        $data['IDENTIFICATION_SHORTID'] = $request->getPost('IDENTIFICATION_SHORTID');
        $data['IDENTIFICATION_SHOPPERID'] = (int)$request->getPost('IDENTIFICATION_SHOPPERID');
        $data['CRITERION_GUEST'] = $request->getPost('CRITERION_GUEST');

        /**
         * information
         */
        $data['TRANSACTION_MODE'] = ($request->getPost('TRANSACTION_MODE') == 'LIVE') ? 'LIVE' : 'CONNECTOR_TEST';
        $data['PRESENTATION_CURRENCY'] = $request->getPost('PRESENTATION_CURRENCY');
        $data['PRESENTATION_AMOUNT'] = floatval($request->getPost('PRESENTATION_AMOUNT'));
        $data['ACCOUNT_BRAND'] = $request->getPost('ACCOUNT_BRAND');

        $paymentHelper = $this->paymentHelper;
        $paymentCode = $paymentHelper->splitPaymentCode($data['PAYMENT_CODE']);
        $data['SOURCE'] = 'RESPONSE';

        if ($data['PAYMENT_CODE'] == "PP.PA") {
            $data['CONNECTOR_ACCOUNT_HOLDER'] = $request->getPost('CONNECTOR_ACCOUNT_HOLDER');
            $data['CONNECTOR_ACCOUNT_IBAN'] = $request->getPost('CONNECTOR_ACCOUNT_IBAN');
            $data['CONNECTOR_ACCOUNT_BIC'] = $request->getPost('CONNECTOR_ACCOUNT_BIC');
        }

        $heidelpayResponse->splitArray($data);
        $paymentMethode = $paymentCode[0];
        $paymentType = $paymentCode[1];
        $logger->addDebug('Heidelpay response postdata : ' . print_r($heidelpayResponse, 1));

        $isSuccess = $heidelpayResponse->isSuccess();
        $objectManager = $this->_objectManager;
        $incrementId = null;
        if ($isSuccess) {
            try {
                /** @TODO direct ObjectManager Access should be avoided */
                $quote = $objectManager->create('Magento\Quote\Model\Quote');
                /** @TODO load is deprecated */
                /** @var \Magento\Quote\Model\Quote $quote */
                $quote = $quote->load($data['IDENTIFICATION_TRANSACTIONID']);
                $quote->collectTotals();

                /** in case of quest checkout */
                if ($data['CRITERION_GUEST'] === 'true') {
                    $quote->setCustomerId(null);
                    $billingAddress = $quote->getBillingAddress();
                    $email = $billingAddress->getEmail();
                    $quote->setCustomerEmail($email);
                    $quote->setCustomerIsGuest(true);
                    $quote->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
                }
                $cartManagement = $this->cartManagement;
                $order = $cartManagement->submit($quote);
                $incrementId = $order->getIncrementId();
            } catch (\Exception $e) {
                $logger->addDebug('Heidelpay Response save order. ' . $e->getMessage());
            }

            /**
             * @TODO $order might not be defined!!! Potential bug!
             */
            $data['ORDER_ID'] = $incrementId;
            $paymentHelper->mapStatus($data, $order);
            $order->save();
        }

        $routeParams = [
            '_forced_secure' => true,
            '_store_to_url'  => true,
            '_nosid'         => true,
        ];
        $url = $url->getUrl('hgw/index/redirect', $routeParams);

        $logger->addDebug('Heidelpay respose url : ' . $url);

        /** @TODO There shouldn't be a reason to echo something in a Controller */
        echo $url;

        try {
            $transaction = $objectManager->create('Heidelpay\Gateway\Model\Transaction');
            $transaction->setData('payment_methode', $paymentMethode);
            $transaction->setData('payment_type', $paymentType);
            $transaction->setData('transactionid', $data['IDENTIFICATION_TRANSACTIONID']);
            $transaction->setData('uniqeid', $data['IDENTIFICATION_UNIQUEID']);
            $transaction->setData('shortid', $data['IDENTIFICATION_SHORTID']);
            $transaction->setData('statuscode', $data['PROCESSING_STATUS_CODE']);
            $transaction->setData('result', $data['PROCESSING_RESULT']);
            $transaction->setData('return', $data['PROCESSING_RETURN']);
            $transaction->setData('returncode', $data['PROCESSING_RETURN_CODE']);
            $transaction->setData('jsonresponse', json_encode($data));
            $transaction->setData('source', $data['SOURCE']);
            $transaction->save();
        } catch (\Exception $e) {
            $logger->error('Heidelpay Response save transaction error. ' . $e->getMessage());
        }
    }
}