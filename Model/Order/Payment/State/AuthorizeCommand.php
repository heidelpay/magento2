<?php
namespace Heidelpay\Gateway\Model\Order\Payment\State;

/**
 * Override authorize command
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

class AuthorizeCommand extends \Magento\Sales\Model\Order\Payment\State\AuthorizeCommand
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
        $state = Order::STATE_NEW;
        $status = $order->getConfig()->getStateDefaultStatus($state);
        $order->setStatus($status)
            ->setState($state)
            ->setIsCustomerNotified(false);

        return __('heidelpay - Saving order');
    }
}
