<?php
/**
 * This class wraps item objects to provide the values already adapted for the communication with the basket-api.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  magento2
 */
namespace Heidelpay\Gateway\Wrapper;

use Magento\Quote\Model\Quote\Item;

class ItemWrapper extends BaseWrapper
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

    public function getDiscountTaxCompensationAmount ()
    {
        return (int)floor(bcmul($this->item->getDiscountTaxCompensationAmount(), 100, 10));
    }

    /**
     * @param string $prefix
     * @return string
     */
    public function getReferenceId($prefix = '')
    {
        return $prefix . sprintf('%x%d', $this->item->getSku(), $this->item->getQty());
    }

    public function calculateDiscountTaxAmount()
    {
        return bcmul($this->item->getDiscountAmount(), $this->item->getTaxPercent());
    }
}
