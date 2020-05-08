<?php
/**
 * Trait to handle payment methods that can ship before payment e.g. Invoice.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 * @link http://dev.heidelpay.com/magento2
 * @author David Owusu
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */

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