<?php
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/**
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/magento2
 */
namespace Heidelpay\Gateway\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface TransactionSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return \Heidelpay\Gateway\Api\Data\TransactionInterface[]
     */
    public function getItems();

    /**
     * @param \Heidelpay\Gateway\Api\Data\TransactionInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
