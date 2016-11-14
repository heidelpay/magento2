<?php
/**
 * Override capture command
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

namespace Heidelpay\Gateway\Model\Order\Payment\State;

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
     * @param string|float|int $amount
     * @param OrderInterface $order
     * @return string
     */
    public function execute(OrderPaymentInterface $payment, $amount, OrderInterface $order)
    {
        $state = Order::STATE_PROCESSING;
        $status = Order::STATE_PENDING_PAYMENT;

        $message = __('Capture: redirect to Heidelpay Gateway ');
        $this->setOrderStateAndStatus($order, $status, $state);

        return $message;
    }

    /**
     * @param Order $order
     * @param string $status
     * @param string $state
     * @return void
     */
    protected function setOrderStateAndStatus(Order $order, $status, $state)
    {
        if (!$status) {
            $config = $order->getConfig();
            $status = $config->getStateDefaultStatus($state);
        }

        $order->setState($state);
        $order->setStatus($status);
    }
}
