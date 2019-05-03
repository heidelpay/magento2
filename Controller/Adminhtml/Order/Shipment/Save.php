<?php

namespace Heidelpay\Gateway\Controller\Adminhtml\Order\Shipment;

use Heidelpay\Gateway\Gateway\Config\HgwBasePaymentConfigInterface;
use Heidelpay\Gateway\Gateway\Config\HgwMainConfigInterface;
use Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod;
use Heidelpay\PhpPaymentApi\TransactionTypes\FinalizeTransactionType;
use Magento\Sales\Model\Order;

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
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderResository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Encryption\Encryptor
     */
    protected $encryptor;

    /**
     * @var \Heidelpay\Gateway\Helper\Payment
     */
    protected $paymentHelper;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader
     * @param \Magento\Shipping\Model\Shipping\LabelGenerator $labelGenerator
     * @param \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Encryption\Encryptor $encryptor
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Heidelpay\Gateway\Helper\Payment $paymentHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
        \Magento\Shipping\Model\Shipping\LabelGenerator $labelGenerator,
        \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Encryption\Encryptor $encryptor,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Heidelpay\Gateway\Helper\Payment $paymentHelper
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
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderResository->get($this->getRequest()->getParam('order_id'));

        // get the payment method instance and the heidelpay method instance
        /** @var \Heidelpay\Gateway\PaymentMethods\HeidelpayAbstractPaymentMethod $method */
        $method = $order->getPayment()->getMethodInstance();

        // only fire the shipping when a heidelpay payment method is used.
        if ($method instanceof HeidelpayAbstractPaymentMethod) {
            // get the php-payment-api instance.
            $heidelpayMethod = $method->getHeidelpayPaymentMethodInstance();

            // if the payment method uses the Finalize Transaction type, we'll send a FIN request to the payment api.
            if (in_array(FinalizeTransactionType::class, class_uses($heidelpayMethod))) {
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
                /** @var \Heidelpay\PhpPaymentApi\Response $response */
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
                $order->setState($state)
                    ->addStatusHistoryComment('heidelpay - Finalizing Order', true);

                $this->orderResository->save($order);

                $this->messageManager->addSuccessMessage(__('Shipping Notification has been sent to Heidelpay.'));
            }
        }
    }
}
