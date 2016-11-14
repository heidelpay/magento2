<?php

namespace Heidelpay\Gateway\Model\Order\Payment\State;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;

/**
 * Override authorize command
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
class AuthorizeCommand extends \Magento\Sales\Model\Order\Payment\State\AuthorizeCommand
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
        $state = Order::STATE_NEW;

        $message = __('Heidelpay save order');

        $order->setState($state);
        $order->setIsCustomerNotified(false);;

        return $message;
    }
}
