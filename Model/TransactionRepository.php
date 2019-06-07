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
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
namespace Heidelpay\Gateway\Model;

use Heidelpay\Gateway\Model\ResourceModel\Transaction as TransactionAlias;
use Magento\Framework\Exception\NoSuchEntityException;

class TransactionRepository implements TransactionRepositoryInterface
{
    /** @var Transaction[] */
    protected $instances = [];

    /** @var Transaction[] */
    protected $instancesByQuoteId = [];

    /** @var TransactionAlias */
    protected $resourceModel;

    /** @var TransactionFactory */
    protected $transactionFactory;

    /**
     * @param TransactionFactory $transactionFactory
     * @param TransactionAlias $resourceModel
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        ResourceModel\Transaction $resourceModel
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->resourceModel = $resourceModel;
    }


    /**
     * @param $id
     * @param bool $forceReload
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getById($id, $forceReload = false)
    {
        $cacheKey = $this->getCacheKey([$id]);
        if ($forceReload || !isset($this->instances[$id][$cacheKey])) {
            $transaction = $this->transactionFactory->create();

            if (!$id) {
                throw new NoSuchEntityException(__('Requested transaction does not exist'));
            }

            $transaction->load($id);
            $this->instances[$id][$cacheKey] = $transaction;
            $this->instancesByQuoteId[$transaction->getTransactionId()][$cacheKey] = $transaction;
        }

        return $this->instances[$id][$cacheKey];
    }

//    /**
//     * Fetches a a list of transactions by qouteId.
//     *
//     * @param $quoteId
//     * @param bool $forceReload
//     */
//    public function getByQuoteId($quoteId, $forceReload = false)
//    {
//        $cacheKey = $this->getCacheKey([$quoteId]);
//        if ($forceReload || !isset($this->instancesByQuoteId[$quoteId][$cacheKey])) {
//            $transaction = $this->transactionFactory->create();
//            $transactionId = $this->resourceModel;
//        }
//    }

    /**
     * Get key for cache
     *
     * @param array $data
     * @return string
     */
    private function getCacheKey($data)
    {
        $serializeData = [];
        foreach ($data as $key => $value) {
            if (is_object($value)) {
                $serializeData[$key] = $value->getId();
            } else {
                $serializeData[$key] = $value;
            }
        }

        return md5(serialize($serializeData));
    }
}
