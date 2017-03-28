<?php

namespace Heidelpay\Gateway\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Transaction Repository
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.de/magento2
 *
 * @author Stephano Vogel
 *
 * @package heidelpay
 * @subpackage magento2
 * @category magento2
 */
class TransactionRepository
{
    /**
     * @var Transaction[]
     */
    protected $instances = [];

    /**
     * @var Transaction[]
     */
    protected $instancesByQuoteId = [];

    /**
     * @var \Heidelpay\Gateway\Model\ResourceModel\Transaction
     */
    protected $resourceModel;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;


    public function __construct(
        TransactionFactory $transactionFactory,
        ResourceModel\Transaction $resourceModel
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->resourceModel = $resourceModel;
    }


    public function get($id, $forceReload = false)
    {
        $cacheKey = $this->getCacheKey([$id]);
        if (!isset($this->instances[$id][$cacheKey]) || $forceReload) {
            $transaction = $this->transactionFactory->create();

            if (!$id) {
                throw new NoSuchEntityException(__('Requested product doesn\'t exist'));
            }

            $transaction->load($id);
            $this->instances[$id][$cacheKey] = $transaction;
            $this->instancesByQuoteId[$transaction->getTransactionId()][$cacheKey] = $transaction;
        }

        return $this->instances[$id][$cacheKey];
    }

    public function getByQuoteId($quoteId, $forceReload = false)
    {
        $cacheKey = $this->getCacheKey([$quoteId]);
        if (!isset($this->instancesByQuoteId[$quoteId][$cacheKey]) || $forceReload) {
            $transaction = $this->transactionFactory->create();

            $transactionId = $this->resourceModel;
        }
    }

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
