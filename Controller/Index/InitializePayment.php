<?php
/**
 * This controller calls the initialization transaction if existing and necessary for the selected payment method.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link https://dev.heidelpay.de/magento2
 * @author David Owusu
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
            /** @var PaymentApiResponse $response */
            $response = $paymentMethodInstance->initMethod($quote);
        } catch (\Exception $e) {
            $postData = [
                $e->getLogMessage()
            ];
            return $result->setData($postData)->setHttpResponseCode(500);
        }

        if ((!$response instanceof PaymentApiResponse || !$response->isSuccess())) {
            $this->logger->error('Heidelpay: Initial request did not succeed.');
            throw new \RuntimeException($this->escaper->escapeHtml($error_message));
        }

        $postData = $paymentMethodInstance->prepareAdditionalFormData($response);

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
