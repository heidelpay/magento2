<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

/**
 * Service Contract for the heidelpay gateway transactions.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/magento2
 */
namespace Heidelpay\Gateway\Api;

use Heidelpay\Gateway\Model\Transaction;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaInterface;

interface TransactionRepositoryInterface
{
    /**
     * Retrieves the transaction with the given id.
     *
     * @param int $id
     *
     * @return \Heidelpay\Gateway\Api\Data\TransactionInterface
     *
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * Saves the given transaction.
     *
     * @param Transaction $transaction
     *
     * @return \Heidelpay\Gateway\Api\Data\TransactionInterface
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(Transaction $transaction);

    /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
    /**
     * Lists transactions that match specified search criteria.
     *
     * This call returns an array of objects, but detailed information about each object’s attributes might not be
     * included.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria The search criteria.
     *
     * @return \Heidelpay\Gateway\Api\Data\TransactionSearchResultInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Deletes the given transaction.
     *
     * @param Transaction $transaction
     *
     * @return \Heidelpay\Gateway\Api\Data\TransactionSearchResultInterface
     *
     * @throws CouldNotDeleteException
     */
    public function delete(Transaction $transaction);



}