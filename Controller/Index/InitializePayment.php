<?php
/**
 * This controller calls the initialization transaction if existing and necessary for the selected payment method.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/magento
 * @author Simon Gabriel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */

namespace Heidelpay\Gateway\Controller\Index;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Command\CommandException;
use Psr\Log\LoggerInterface;
use Heidelpay\PhpPaymentApi\Response as PaymentApiResponse;

class InitializePayment extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * InitializePayment constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LoggerInterface $logger,
        Session $checkoutSession,
        Escaper $escaper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->escaper = $escaper;
        parent::__construct($context);
    }

    /**
     * {@inheritDoc}
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \RuntimeException
     * @throws CommandException
     */
    public function execute()
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        $error_message = __('An unexpected error occurred. Please contact us to get further information.');

        $session = $this->getCheckoutSession();
        $quote = $session->getQuote();

        $this->logger->debug('Heidelpay: Issue initial payment request...');

        if (!$quote->getId()) {
            $this->logger->error('Heidelpay: Quote not found in session.');
            throw new NotFoundException(new Phrase($this->escaper->escapeHtml($error_message)));
        }

        // create result object for this request
        $result = $this->resultJsonFactory->create();

        // if there is no post request, just do nothing and return the redirectUrl instantly, so an
        // error message can be shown to the customer (which will be created in the redirect controller)
        if (!$this->getRequest()->isPost()) {
            $this->logger->warning('Heidelpay - Response: Request is not POST.');

            // return the result now, no further processing.
            return $result;
        }

        $postData = $this->getRequest()->getParams();
        $this->logger->debug('Heidelpay: postData - ' . print_r($postData, 1));

        if (!isset($postData['method'])) {
            throw new CommandException(new Phrase('Heidelpay: '));
        }
        $payment = $quote->getPayment()->setMethod($postData['method']);
        $paymentMethodInstance = $payment->getMethodInstance();

        // get the response object from the initial request.
        try {
            // todo-simon: payment methods wieder abstrahieren und dann hier die abstrakte klasse type hinten
            /** @var PaymentApiResponse $response */
            $response = $paymentMethodInstance->initMethod($quote);
            $this->logger->debug('initialResponse ' . print_r($response, 1));
        } catch (CommandException $e) {
            $postData = [
                $e->getLogMessage()
            ];
            $this->logger->debug('Request failed: ');
            return $result->setData($postData)->setHttpResponseCode(500);
        }

        //TODO: remove brand-check when initial request for iDeal is async.
        if ((!$response instanceof PaymentApiResponse || !$response->isSuccess()) && empty($response->getConfig()->getBrands())) {
            $this->logger->error('Heidelpay: Initial request did not succeed.');
            throw new \RuntimeException($this->escaper->escapeHtml($error_message));
        }

        // todo-simon: what to do. if not redirect?
//        // redirect to payment url, if it uses redirecting
//        if ($paymentMethodInstance->activeRedirect() === true) {
//            return $this->_redirect($response->getPaymentFormUrl());
//        }
//
//            $resultPage = $this->_resultPageFactory->create();
//            $resultPage->getConfig()->getTitle()->prepend(__('Please confirm your payment:'));
//            $resultPage->getLayout()->getBlock('heidelpay_gateway')->setHgwUrl(
//                $response->getPaymentFormUrl()
//            );
//            $resultPage->getLayout()->getBlock('heidelpay_gateway')->setHgwCode($payment->getCode());
//
//            return $resultPage;

        $brands = $response->getConfig()->getBrands();
        $this->logger->debug('brand origin: ' . print_r($brands, 1));

        $bankNamesList = [];
        $bankValueList = [];

        foreach ($brands as $brandValue => $brandName) {
            $bankValueList[] = $brandValue;
            $bankNamesList[] = $brandName;
        }

        $this->logger->debug('brand values: ' . print_r($bankValueList, 1));
        $this->logger->debug('brand names: ' . print_r($bankNamesList, 1));

        $postData = [
            'brandValues' => $bankValueList,
            'brandNames' => $bankNamesList
        ];
        $this->logger->debug('postData: ' . print_r($postData, 1));
        $this->logger->debug('postData: json' . print_r(json_encode($postData), 1));

        return $result->setData(json_encode($postData));
    }

    /**
     * @return Session
     */
    private function getCheckoutSession()
    {
        return $this->checkoutSession;
    }
}
