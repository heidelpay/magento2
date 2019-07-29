<?php
/**
 * Handles transaction objects.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/magento2
 *
 * @author Simon Gabriel
 *
 * @package heidelpay/magento2
 */
namespace Heidelpay\Gateway\Model;

use Exception;
use Heidelpay\Gateway\Api\Data\TransactionSearchResultInterface;
use Heidelpay\Gateway\Api\Data\TransactionSearchResultInterfaceFactory;
use Heidelpay\Gateway\Api\TransactionRepositoryInterface;
use Heidelpay\Gateway\Model\ResourceModel\Transaction as ResourceTransaction;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory as TransactionCollectionFactory;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\Collection;

class TransactionRepository implements TransactionRepositoryInterface
{
    /** @var TransactionFactory */
    private $transactionFactory;

    /** @var TransactionCollectionFactory */
    private $transactionCollectionFactory;

    /** @var TransactionSearchResultInterfaceFactory */
    private $searchResultFactory;

    /** @var ResourceTransaction */
    private $resource;

    /**
     * TransactionRepository constructor.
     *
     * @param TransactionFactory $transactionFactory
     * @param TransactionCollectionFactory $transactionCollectionFactory
     * @param TransactionSearchResultInterfaceFactory $transactionSearchResultInterfaceFactory
     * @param ResourceTransaction $resource
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        TransactionCollectionFactory $transactionCollectionFactory,
        TransactionSearchResultInterfaceFactory $transactionSearchResultInterfaceFactory,
        ResourceTransaction $resource
    ) {
        $this->transactionFactory           = $transactionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->searchResultFactory          = $transactionSearchResultInterfaceFactory;
        $this->resource = $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function save(Transaction $transaction)
    {
        $this->resource->save($transaction);
        return $transaction;
    }

    /**
     * {@inheritDoc}
     */
    public function getById($id)
    {
        /** @var Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $this->resource->load($transaction, $id);
        if (! $transaction->getId()) {
            throw new NoSuchEntityException(__('Unable to find transaction with ID "%1"', $id));
        }
        return $transaction;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Transaction $transaction)
    {
        try {
            $this->resource->delete($transaction);
        } catch (Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the transaction: %1', $e->getMessage()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->transactionCollectionFactory->create();

        $this->addFiltersToCollection($searchCriteria, $collection);
        $this->addSortOrdersToCollection($searchCriteria, $collection);
        $this->addPagingToCollection($searchCriteria, $collection);

        $collection->load();

        return $this->buildSearchResult($searchCriteria, $collection);
    }

    //<editor-fold desc="Helpers">

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param Collection $collection
     */
    private function addFiltersToCollection(SearchCriteriaInterface $searchCriteria, Collection $collection)
    {
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $conditions = [];
            $fields     = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $fields[] = $filter->getField();
                $conditions[] = [$filter->getConditionType() => $filter->getValue()];
            }
            $collection->addFieldToFilter($fields, $conditions);
        }
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param Collection $collection
     */
    private function addSortOrdersToCollection(SearchCriteriaInterface $searchCriteria, Collection $collection)
    {
        foreach ((array) $searchCriteria->getSortOrders() as $sortOrder) {
            $direction = $sortOrder->getDirection() === SortOrder::SORT_ASC ? 'asc' : 'desc';
            $collection->addOrder($sortOrder->getField(), $direction);
        }
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param Collection $collection
     */
    private function addPagingToCollection(SearchCriteriaInterface $searchCriteria, Collection $collection)
    {
        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->setCurPage($searchCriteria->getCurrentPage());
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param Collection $collection
     *
     * @return TransactionSearchResultInterface
     */
    private function buildSearchResult(SearchCriteriaInterface $searchCriteria, Collection $collection)
    {
        /** @var TransactionSearchResultInterface $searchResults */
        $searchResults = $this->searchResultFactory->create();

        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    //</editor-fold>
}
