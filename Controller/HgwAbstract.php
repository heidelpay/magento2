<?php

namespace Heidelpay\Gateway\Controller;

use Heidelpay\Gateway\Helper\Payment AS HeidelpayHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Url\Helper\Data as UrlHelperData;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

/**
 * Abstract controller class
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
abstract class HgwAbstract extends Action
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
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Quote
     */
    protected $_quote = false;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var HeidelpayHelper
     */
    protected $paymentHelper;

    /**
     * @var CartRepositoryInterface
     */
    protected $_quoteObject;

    /**
     * \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    /**
     * \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;

    /**
     * \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender
     */
    protected $orderCommentSender;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var Url
     */
    private $customerUrl;

    /**
     * @var UrlHelperData
     */
    private $urlHelper;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * HgwAbstract constructor.
     * @param Context $context
     * @param Session $customerSession
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param UrlHelperData $urlHelper
     * @param LoggerInterface $logger
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $quoteObject
     * @param PageFactory $resultPageFactory
     * @param HeidelpayHelper $paymentHelper
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param OrderCommentSender $orderCommentSender
     * @param Encryptor $encryptor
     * @param Url $customerUrl
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        UrlHelperData $urlHelper,
        LoggerInterface $logger,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $quoteObject,
        PageFactory $resultPageFactory,
        HeidelpayHelper $paymentHelper,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        OrderCommentSender $orderCommentSender,
        Encryptor $encryptor,
        Url $customerUrl
    )
    {
        $this->_quoteObject = $quoteObject;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->urlHelper = $urlHelper;
        $this->encryptor = $encryptor;
        $this->customerUrl = $customerUrl;
        $this->_logger = $logger;
        $this->paymentHelper = $paymentHelper;
        $this->cartManagement = $cartManagement;
        $this->orderSender = $orderSender;
        $this->orderCommentSender = $orderCommentSender;
        $this->_resultPageFactory = $resultPageFactory;
        $this->invoiceSender = $invoiceSender;

        parent::__construct($context);
    }


    /**
     * Return checkout session object
     *
     * @return CheckoutSession
     */
    protected function getCheckout()
    {
        return $this->checkoutSession;
    }

    /**
     * Return checkout quote object
     *
     * @return Quote
     */
    protected function getQuote()
    {
        $quote = $this->_quote;
        if (!$quote) {
            $session = $this->getCheckout();
            $quote = $session->getQuote();
        }

        return $quote;
    }
}