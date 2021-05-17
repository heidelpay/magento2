<?php
namespace Heidelpay\Gateway\Model\Order\Payment\State;

/**
 * Override capture command
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/magento2
 *
 * @author  Jens Richter
 *
 * @package  Heidelpay
 * @subpackage Magento2
 * @category Magento2
 */
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;

/**
 * Class Capture
 */
class CaptureCommand extends \Magento\Sales\Model\Order\Payment\State\CaptureCommand
{
    /**
     * Run command
     *
     * @param OrderPaymentInterface $payment
     * @param string|float|int      $amount
     * @param OrderInterface        $order
     *
     * @return string
     */
    public function execute(OrderPaymentInterface $payment, $amount, OrderInterface $order)
    {
        if (strpos($payment->getMethod(), 'hgw') !== 0) {
            return parent::execute($payment, $amount, $order);
        }

        $state = Order::STATE_PROCESSING;
        $status = Order::STATE_PENDING_PAYMENT;
        
        $message = __('Capture: redirect to Heidelpay Gateway ');
        $this->setOrderStateAndStatus($order, $status, $state);

        return $message;
    }

    /**
     * @param Order  $order
     * @param string $status
     * @param string $state
     *
     * @return void
     */
    protected function setOrderStateAndStatus(Order $order, $status, $state)
    {
        if (!$status) {
            $status = $order->getConfig()->getStateDefaultStatus($state);
        }

        $order->setState($state)->setStatus($status);
    }
}
