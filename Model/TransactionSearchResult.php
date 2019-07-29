<?php
/**
 * Transaction search result
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/magento2
 *
 * @author  Simon Gabriel
 *
 * @package heidelpay\magento2
 */
namespace Heidelpay\Gateway\Model;

use Heidelpay\Gateway\Api\Data\TransactionSearchResultInterface;
use Magento\Framework\Api\SearchResults;

/** @noinspection EmptyClassInspection */
class TransactionSearchResult extends SearchResults implements TransactionSearchResultInterface
{
}
