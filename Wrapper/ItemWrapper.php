<?php
/**
 * Short Summary
 *
 * Description
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/heidelpay-php-api/
 *
 * @author  Simon Gabriel <simon.gabriel@heidelpay.de>
 *
 * @package  Heidelpay
 * @subpackage PhpStorm
 * @category ${CATEGORY}
 */
namespace Heidelpay\Gateway\Wrapper;

use Magento\Quote\Model\Quote\Item;

class ItemWrapper
{
    /**
     * @var Item
     */
    private $item;

    /**
     * itemWrapper constructor.
     * @param Item $item
     */
    public function __construct(Item $item)
    {
        $this->item = $item;
    }

    /**
     * @return int
     */
    public function getTaxPercent()
    {
        return (int)$this->item->getTaxPercent();
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        return (int)floor(bcmul($this->item->getPrice(), 100, 10));
    }

    /**
     * @return int
     */
    public function getRowTotalWithDiscount()
    {
        return (int)floor(bcmul($this->item->getRowTotal() - $this->item->getDiscountAmount(), 100, 10));
    }

    /**
     * @return int
     */
    public function getDiscountAmount()
    {
        return (int)floor(bcmul($this->item->getDiscountAmount(), 100, 10));
    }

    /**
     * @return int
     */
    public function getTaxAmount()
    {
        return (int)floor(bcmul($this->item->getTaxAmount(), 100, 10));
    }

    /**
     * @return int
     */
    public function getRowTotalInclTax()
    {
        return (int)floor(bcmul($this->item->getRowTotalInclTax(), 100, 10));
    }

    /**
     * @return int
     */
    public function getRowTotal()
    {
        return (int)floor(bcmul($this->item->getRowTotal(), 100, 10));
    }

    /**
     * @param string $prefix
     * @return string
     */
    public function getReferenceId($prefix = '')
    {
        return $prefix . sprintf('%x%d', $this->item->getSku(), $this->item->getQty());
    }
}
