<?php

namespace Heidelpay\Gateway\Controller\Index;

use Heidelpay\Gateway\Helper\Payment as HeidelpayHelper;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

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
 *
 * @link https://dev.heidelpay.de/magento
 *
 * @author Jens Richter
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class Response extends \Heidelpay\Gateway\Controller\HgwAbstract
{
    protected $resultPageFactory;
    protected $logger;

    /** @var \Heidelpay\PhpApi\Response The heidelpay response object */
    protected $heidelpayResponse;

    /**
     * heidelpay Response constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteObject
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param HeidelpayHelper $paymentHelper
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param OrderCommentSender $orderCommentSender
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Heidelpay\PhpApi\Response $heidelpayResponse
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteObject,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        HeidelpayHelper $paymentHelper,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        OrderCommentSender $orderCommentSender,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Customer\Model\Url $customerUrl,
        \Heidelpay\PhpApi\Response $heidelpayResponse
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $orderFactory, $urlHelper, $logger,
            $cartManagement, $quoteObject, $resultPageFactory, $paymentHelper, $orderSender, $invoiceSender,
            $orderCommentSender, $encryptor, $customerUrl);

        $this->heidelpayResponse = $heidelpayResponse;
    }

    public function execute()
    {
        $request = $this->getRequest();
        $data = [];

        // Quit processing on an empty post response
        $data['PROCESSING_RESULT'] = $request->getPOST('PROCESSING_RESULT');
        $data['CRITERION_SECRET'] = $request->getPost('CRITERION_SECRET');
        $data['IDENTIFICATION_TRANSACTIONID'] = $request->getPOST('IDENTIFICATION_TRANSACTIONID');

        // initialize the Response object with data from the transaction.
        $this->heidelpayResponse = $this->heidelpayResponse->splitArray($data);

        $secret = $this->_encryptor->exportKeys();
        $identificationTransactionId = $this->heidelpayResponse->getIdentification()->getTransactionId();

        $this->_logger->debug('Heidelpay response postdata : ' . print_r($this->heidelpayResponse, 1));
        $this->_logger->debug('Heidelpay $secret: ' . print_r($secret, 1));
        $this->_logger->debug('Heidelpay $$identificationTransactionId: ' . print_r($identificationTransactionId, 1));

        // validate Hash to prevent manipulation
        try {
            $this->heidelpayResponse->verifySecurityHash($secret, $identificationTransactionId);
        } catch (\Exception $e) {
            $this->_logger->critical("Heidelpay response object fail " . $e->getMessage());
            $this->_logger->critical(
                "Heidelpay response object form server "
                . $request->getServer('REMOTE_ADDR')
                . " with an invalid hash. This could be some kind of manipulation."
            );
            $this->_logger->critical(
                'Heidelpay reference object hash ' . $this->heidelpayResponse->getCriterion()->getSecretHash()
            );
            echo $this->_url->getUrl('hgw/index/redirect', [
                '_forced_secure' => true,
                '_store_to_url' => true,
                '_nosid' => true
            ]);
            return;
        }

        $data['IDENTIFICATION_TRANSACTIONID'] = (int)$request->getPOST('IDENTIFICATION_TRANSACTIONID');
        $data['PROCESSING_STATUS_CODE'] = (int)$request->getPOST('PROCESSING_STATUS_CODE');
        $data['PROCESSING_RETURN'] = $request->getPOST('PROCESSING_RETURN');
        $data['PROCESSING_RETURN_CODE'] = $request->getPOST('PROCESSING_RETURN_CODE');
        $data['PAYMENT_CODE'] = $request->getPOST('PAYMENT_CODE');
        $data['IDENTIFICATION_UNIQUEID'] = $request->getPOST('IDENTIFICATION_UNIQUEID');
        $data['IDENTIFICATION_SHORTID'] = $request->getPOST('IDENTIFICATION_SHORTID');
        $data['IDENTIFICATION_SHOPPERID'] = (int)$request->getPOST('IDENTIFICATION_SHOPPERID');
        $data['CRITERION_GUEST'] = $request->getPOST('CRITERION_GUEST');

        /**
         * information
         */
        $data['TRANSACTION_MODE'] = ($request->getPOST('TRANSACTION_MODE') == 'LIVE') ? 'LIVE' : 'CONNECTOR_TEST';
        $data['PRESENTATION_CURRENCY'] = $request->getPOST('PRESENTATION_CURRENCY');
        $data['PRESENTATION_AMOUNT'] = floatval($request->getPOST('PRESENTATION_AMOUNT'));
        $data['ACCOUNT_BRAND'] = $request->getPOST('ACCOUNT_BRAND');

        $paymentCode = $this->_paymentHelper->splitPaymentCode($data['PAYMENT_CODE']);

        $data['SOURCE'] = 'RESPONSE';

        if ($data['PAYMENT_CODE'] == "PP.PA") {
            $data['CONNECTOR_ACCOUNT_HOLDER'] = $request->getPOST('CONNECTOR_ACCOUNT_HOLDER');
            $data['CONNECTOR_ACCOUNT_IBAN'] = $request->getPOST('CONNECTOR_ACCOUNT_IBAN');
            $data['CONNECTOR_ACCOUNT_BIC'] = $request->getPOST('CONNECTOR_ACCOUNT_BIC');
        }

        // in case of direct debit
        if ($data['PAYMENT_CODE'] == 'DD.DB') {
            $data['ACCOUNT_IBAN'] = $request->getPOST('ACCOUNT_IBAN');
            $data['ACCOUNT_IDENTIFICATION'] = $request->getPOST('ACCOUNT_IDENTIFICATION');
            $data['IDENTIFICATION_CREDITOR_ID'] = $request->getPOST('IDENTIFICATION_CREDITOR_ID');
        }

        $this->heidelpayResponse = $this->heidelpayResponse->splitArray($data);

        $paymentMethode = $paymentCode[0];
        $paymentType = $paymentCode[1];

        $this->_logger->debug('Heidelpay response postdata : ' . print_r($this->heidelpayResponse, 1));

        if ($this->heidelpayResponse->isSuccess()) {
            try {
                $quote = $this->_objectManager->create('Magento\Quote\Model\Quote')->load($data['IDENTIFICATION_TRANSACTIONID']);
                $quote->collectTotals();
                //$this->_quoteObject()->save($quote);

                /** in case of guest checkout */
                if ($data['CRITERION_GUEST'] === 'true') {
                    $quote->setCustomerId(null)
                        ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                        ->setCustomerIsGuest(true)
                        ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
                }

                $order = $this->_cartManagement->submit($quote);
            } catch (\Exception $e) {
                $this->_logger->debug('Heidelpay: Cannot submit the Quote. ' . $e->getMessage());
            }

            $data['ORDER_ID'] = $order->getIncrementId();

            $this->_paymentHelper->mapStatus(
                $data,
                $order
            );
            $order->save();
        }

        // TODO: Clear data when customer is a guest.

        $url = $this->_url->getUrl('hgw/index/redirect', array(
            '_forced_secure' => true,
            '_store_to_url' => true,
            '_nosid' => true
        ));

        $this->_logger->addDebug('Heidelpay respose url : ' . $url);
        echo $url;

        try {
            $model = $this->_objectManager->create('Heidelpay\Gateway\Model\Transaction');
            $model->setData('payment_methode', $paymentMethode);
            $model->setData('payment_type', $paymentType);
            $model->setData('transactionid', $data['IDENTIFICATION_TRANSACTIONID']);
            $model->setData('uniqeid', $data['IDENTIFICATION_UNIQUEID']);
            $model->setData('shortid', $data['IDENTIFICATION_SHORTID']);
            $model->setData('statuscode', $data['PROCESSING_STATUS_CODE']);
            $model->setData('result', $data['PROCESSING_RESULT']);
            $model->setData('return', $data['PROCESSING_RETURN']);
            $model->setData('returncode', $data['PROCESSING_RETURN_CODE']);
            $model->setData('jsonresponse', json_encode($data));
            $model->setData('source', $data['SOURCE']);
            $model->save();
        } catch (\Exception $e) {
            $this->_logger->error('Heidelpay Response save transaction error. ' . $e->getMessage());
        }
    }
}
