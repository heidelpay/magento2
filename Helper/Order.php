<?php

namespace Heidelpay\Gateway\Helper;


use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Helper\Data as SalesHelper;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Heidelpay order helper
 *
 * The payment helper is a collection of function to prepare an send
 *
 * @license    Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright  Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link       https://dev.heidelpay.de/magento
 *
 * @author     David Owusu
 *
 * @package    Heidelpay
 * @subpackage Magento2
 * @category   Magento2
 */
class Order extends AbstractHelper
{
    /**
     * @var SalesHelper
     */
    private $salesHelper;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Order constructor.
     * @param Context $context
     * @param SalesHelper $salesHelper
     * @param OrderRepository $orderRepository
     * @param InvoiceSender $invoiceSender
     * @param OrderSender $orderSender
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        Context $context,
        SalesHelper $salesHelper,
        OrderRepository $orderRepository,
        InvoiceSender $invoiceSender,
        OrderSender $orderSender,
        SearchCriteriaBuilder $criteriaBuilder
    )
    {
        parent::__construct($context);
        $this->salesHelper = $salesHelper;
        $this->invoiceSender = $invoiceSender;
        $this->orderSender = $orderSender;
        $this->searchCriteriaBuilder = $criteriaBuilder;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Send invoice mails to the customer.
     * @param MagentoOrder $order
     */
    public function handleInvoiceMails($order)
    {
        $debugArray = [
            'canInvoice' => $order->canInvoice(),
            'canSendNewInvoiceEmail' => $this->salesHelper->canSendNewInvoiceEmail($order->getStore()->getId())
            ];
        $this->_logger->debug('handling invoices... ' . print_r(
            $debugArray, 1
            )
        );

        if (!$order->canInvoice() && $this->salesHelper->canSendNewInvoiceEmail($order->getStore()->getId())) {
            $invoices = $order->getInvoiceCollection();

            $this->_logger->debug('sending invoices...');
            foreach ($invoices as $invoice) {
                $this->invoiceSender->send($invoice);
            }
        }
    }

    /**
     * Send order confirmation to the customer.
     * @param MagentoOrder $order
     */
    public function handleOrderMail($order)
    {
        try {
            if ($order && $order->getId() && !$order->getEmailSent()) {
                $this->orderSender->send($order);
            }
        } catch (\Exception $e) {
            $this->_logger->error(
                'Heidelpay - Response: Cannot send order confirmation E-Mail. ' . $e->getMessage()
            );
        }
    }

    /**
     * @param $transactionId
     * @return MagentoOrder
     */
    public function fetchOrder($transactionId)
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(
                'quote_id',
                $transactionId
            )->create();

        /** @var Collection $orderList */
        $orderList = $this->orderRepository->getList($criteria);

        return $orderList->getFirstItem();
    }
}