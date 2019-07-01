<?php

namespace Heidelpay\Gateway\Controller\Adminhtml\Order\Shipment;

use Heidelpay\Gateway\Gateway\Config\HgwBasePaymentConfigInterface;
use Heidelpay\Gateway\Gateway\Config\HgwMainConfigInterface;
use Heidelpay\Gateway\Helper\Payment;
use Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod;
use Heidelpay\PhpPaymentApi\Response;
use Heidelpay\PhpPaymentApi\TransactionTypes\FinalizeTransactionType;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Encryption\Encryptor;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Psr\Log\LoggerInterface;

/**
 * Save Controller
 *
 * Manages the creating of shipment (overwrites the Magento 2 default Shipment Save Controller)
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class Save extends \Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save
{
    /** @var OrderRepository */
    protected $orderResository;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Encryptor */
    protected $encryptor;

    /** @var Payment */
    protected $paymentHelper;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param ShipmentLoader $shipmentLoader
     * @param LabelGenerator $labelGenerator
     * @param ShipmentSender $shipmentSender
     * @param LoggerInterface $logger
     * @param Encryptor $encryptor
     * @param OrderRepository $orderRepository
     * @param Payment $paymentHelper
     */
    public function __construct(
        Context $context,
        ShipmentLoader $shipmentLoader,
        LabelGenerator $labelGenerator,
        ShipmentSender $shipmentSender,
        LoggerInterface $logger,
        Encryptor $encryptor,
        OrderRepository $orderRepository,
        Payment $paymentHelper
    ) {
        $this->orderResository = $orderRepository;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->paymentHelper = $paymentHelper;

        parent::__construct($context, $shipmentLoader, $labelGenerator, $shipmentSender);
    }

    /**
     * Save shipment
     * We can save only new shipment. Existing shipments are not editable
     */
    public function beforeExecute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            $this->messageManager->addErrorMessage(__('We can\'t save the shipment right now.'));
            $this->_redirect('sales/order/index');
        }

        // get the order to receive heidelpay payment method instance
        /** @var Order $order */
        $order = $this->orderResository->get($this->getRequest()->getParam('order_id'));

        // get the payment method instance and the heidelpay method instance
        /** @var HeidelpayAbstractPaymentMethod $method */
        $method = $order->getPayment()->getMethodInstance();

        // only fire the shipping when a heidelpay payment method is used.
        if ($method instanceof HeidelpayAbstractPaymentMethod) {
            // get the php-payment-api instance.
            $heidelpayMethod = $method->getHeidelpayPaymentMethodInstance();

            // if the payment method uses the Finalize Transaction type, we'll send a FIN request to the payment api.
            if (in_array(FinalizeTransactionType::class, class_uses($heidelpayMethod), true)) {
                /** @var HgwMainConfigInterface $mainConfig */
                $mainConfig = $method->getMainConfig();

                /** @var HgwBasePaymentConfigInterface $methodConfig */
                $methodConfig = $method->getConfig();

                $heidelpayMethod->getRequest()->authentification(
                    $mainConfig->getSecuritySender(),
                    $mainConfig->getUserLogin(),
                    $mainConfig->getUserPasswd(),
                    $methodConfig->getChannel(),
                    $mainConfig->isSandboxModeActive()
                );

                // set the basket data (for amount and currency and a secret hash for fraud checking)
                $heidelpayMethod->getRequest()->basketData(
                    $order->getQuoteId(),
                    $this->paymentHelper->format($order->getGrandTotal()),
                    $order->getOrderCurrencyCode(),
                    $this->encryptor->exportKeys()
                );

                // send the finalize request
                /** @var Response $response */
                $heidelpayMethod->finalize($order->getPayment()->getLastTransId());

                // if the response isn't successful, redirect back to the order view.
                if (!$heidelpayMethod->getResponse()->isSuccess()) {
                    $this->messageManager->addErrorMessage(
                        __('Heidelpay Error at sending Finalize Request. The Shipment was not created.')
                        . ' Error Message: ' . $heidelpayMethod->getResponse()->getError()['message']
                    );

                    $this->logger->error(
                        'Heidelpay - Shipment Creation: Failure when sending finalize request. Error Message: '
                        . json_encode($heidelpayMethod->getResponse()->getError())
                    );

                    $this->_redirect('*/*/new', ['order_id' => $this->getRequest()->getParam('order_id')]);
                }

                // set order state to "pending payment"
                $state = Order::STATE_PENDING_PAYMENT;
                $order->setState($state)->addCommentToStatusHistory('heidelpay - Finalizing Order', true);

                $this->orderResository->save($order);

                $this->messageManager->addSuccessMessage(__('Shipping Notification has been sent to Heidelpay.'));
            }
        }
    }
}
