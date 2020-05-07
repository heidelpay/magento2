<?php


namespace Heidelpay\Gateway\Traits;


use Magento\Sales\Model\Order;

trait CanShipBeforePayment
{
    /**
     * @param Order $order
     */
    public function setShippedOrderState(&$order)
    {
        if ($order->getTotalPaid() < $order->getGrandTotal()) {
            $state = Order::STATE_PENDING_PAYMENT;
            $status = $order->getConfig()->getStateDefaultStatus($state);
            $order->setState($state)->setStatus($status);
        }
    }
}