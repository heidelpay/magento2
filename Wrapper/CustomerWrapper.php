<?php
/**
 * This wrapper helps gathering information about a given customer.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  magento2
 */
namespace Heidelpay\Gateway\Wrapper;

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\Order;

class CustomerWrapper
{
    /** @var CustomerInterface */
    private $customer;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var DateTime */
    private $dateTime;

    /**
     * CustomerWrapper constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTime $dateTime
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DateTime $dateTime
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTime = $dateTime;
    }

    /**
     * @param CustomerInterface $customer
     * @return CustomerWrapper
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Returns true if the customer is a guest.
     *
     * @return bool
     */
    public function isGuest()
    {
        return empty($this->customer->getId());
    }

    /**
     * Returns the day the customer entity has been created or the current date if it is a guest.
     * The date is returned as string in the following format 'yyyy-mm-dd'.
     *
     * @return string
     * @throws Exception
     */
    public function customerSince()
    {
        $date = $this->customer->getCreatedAt();
        if ($this->isGuest()) {
            $date = true;
        }
        return $this->dateTime->formatDate($date, false);
    }

    /**
     * Returns the number of orders the given customer has created over time.
     *
     * @return int
     */
    public function numberOfOrders()
    {
        if ($this->isGuest()) {
            return 0;
        }

        /** @var SearchCriteriaInterface $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $this->customer->getId())
            ->addFilter('state', Order::STATE_COMPLETE)
            ->create();

        /** @var Collection $orderList */
        $orderList = $this->orderRepository->getList($searchCriteria);

        return $orderList->count();
    }
}
