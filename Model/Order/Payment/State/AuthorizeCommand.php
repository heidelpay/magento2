<?php
namespace Heidelpay\Gateway\Model\Order\Payment\State;

/**
 * Override authorize command
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  https://dev.heidelpay.de/magento
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
        $order->setStatus(Order::STATE_NEW)
            ->setState(Order::STATE_NEW)
            ->setIsCustomerNotified(false);

        return __('heidelpay - Saving order');
    }
}
