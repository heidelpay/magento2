<?php
/**
 * This class wraps quote objects to provide the values already adapted for the communication with the basket-api.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  magento2
 */
namespace Heidelpay\Gateway\Wrapper;

use Magento\Quote\Model\Quote;

class QuoteWrapper extends BaseWrapper
{
    const FIELD_DISCOUNT_AMOUNT = 'discount_amount';
    const FIELD_SHIPPING_AMOUNT = 'shipping_amount';
    const FIELD_SHIPPING_TAX_AMOUNT = 'shipping_tax_amount';
    const FIELD_SHIPPING_INCL_TAX = 'shipping_incl_tax';
    const FIELD_BASE_SHIPPING_DISCOUNT_AMOUNT = 'base_shipping_discount_amount';
    const FIELD_SUBTOTAL_WITH_DISCOUNT = 'subtotal_with_discount';
    const FIELD_TAX_AMOUNT = 'tax_amount';

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var array $totals
     */
    private $totals;

    /**
     * QuoteTotalsWrapper constructor.
     * @param Quote $quote
     */
    public function __construct(Quote $quote)
    {
        bcscale(10);
        $this->setQuote($quote);
        $this->totals = $quote->getShippingAddress()->toArray();
        ksort($this->totals);
    }

    /**
     * Calculate shipping tax in percent
     *
     * @return float shipping tax in percent
     */
    public function getShippingTaxPercent()
    {
        $shipping_amount = $this->getShippingAmountRaw();

        if ((int)$shipping_amount === 0) {
            return 0.0;
        }

        $taxPercentageRaw = bcdiv($this->getShippingTaxAmountRaw(), $shipping_amount);
        return round($taxPercentageRaw, 2);
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->quote->getQuoteCurrencyCode();
    }

    /**
     * @return int
     */
    public function getSubtotalWithDiscountAndShipping()
    {
        // SubtotalWithDiscount already contains the ShippingDiscount but not the shipping itself.
        return $this->getSubtotalWithDiscount() + $this->getShippingAmount();
    }

    /**
     * Take discount into account when calculating the tax amount.
     *
     * @return int
     */
    public function getActualTaxAmount()
    {

        return $this->getActualSubtotalTax() + $this->getActualShippingTax();
    }

    /**
     * @return int
     */
    public function getTotalDiscountAmount()
    {
        return (int)round(bcmul($this->getTotalDiscountAmountRaw(), 100));
    }

    /**
     * @return int
     */
    public function getTotalDiscountAmountRaw()
    {
        return abs($this->totals[self::FIELD_DISCOUNT_AMOUNT]);
    }

    /**
     * @return string
     */
    public function getBasketReferenceId()
    {
        return sprintf('M2-S%dQ%d-%s', $this->quote->getStoreId(), $this->quote->getId(), date('YmdHis'));
    }

    /**
     * @return mixed
     */
    public function getShippingAmount()
    {
        return $this->fetchNormalizeValue(self::FIELD_SHIPPING_AMOUNT);
    }

    /**
     * @return float
     */
    public function getShippingAmountRaw()
    {
        return $this->fetchNormalizeValue(self::FIELD_SHIPPING_AMOUNT, true);
    }

    /**
     * @return mixed
     */
    public function getShippingTaxAmount()
    {
        return $this->fetchNormalizeValue(self::FIELD_SHIPPING_TAX_AMOUNT);
    }

    /**
     * @return float
     */
    public function getShippingTaxAmountRaw()
    {
        return $this->fetchNormalizeValue(self::FIELD_SHIPPING_TAX_AMOUNT, true);
    }

    /**
     * @return mixed
     */
    public function getShippingInclTax()
    {
        return $this->fetchNormalizeValue(self::FIELD_SHIPPING_INCL_TAX);
    }

    /**
     * @return float
     */
    public function getShippingInclTaxRaw()
    {
        return $this->fetchNormalizeValue(self::FIELD_SHIPPING_INCL_TAX, true);
    }

    /**
     * @return mixed
     */
    public function getShippingDiscountAmount()
    {
        return $this->fetchNormalizeValue(self::FIELD_BASE_SHIPPING_DISCOUNT_AMOUNT);
    }

    /**
     * @return float
     */
    public function getShippingDiscountAmountRaw()
    {
        return $this->fetchNormalizeValue(self::FIELD_BASE_SHIPPING_DISCOUNT_AMOUNT, true);
    }

    /**
     * @return mixed
     */
    public function getSubtotalWithDiscount()
    {
        return $this->fetchNormalizeValue(self::FIELD_SUBTOTAL_WITH_DISCOUNT);
    }

    /**
     * @return float
     */
    public function getSubtotalWithDiscountRaw()
    {
        return $this->fetchNormalizeValue(self::FIELD_SUBTOTAL_WITH_DISCOUNT, true);
    }

    /**
     * @param Quote $quote
     */
    public function setQuote($quote)
    {
        $quote->collectTotals();
        $this->quote = $quote;
    }

    /**
     * @return float|int
     */
    public function getActualShippingTax()
    {
        return (int)round(bcmul($this->getShippingInclDiscount(), $this->getShippingTaxPercent()));
    }

    /**
     * @return int
     */
    public function getActualSubtotalTax()
    {
        return $this->getTaxAmount() - $this->getShippingTaxAmount();
    }

    /**
     * @return int
     */
    public function getActualSubtotalTaxRaw()
    {
        return $this->getTaxAmountRaw() - $this->getShippingTaxAmountRaw();
    }

    /**
     * @return int
     */
    public function getShippingInclDiscount()
    {
        return $this->getShippingAmount() - $this->getShippingDiscountAmount();
    }

    /**
     * @return int
     */
    public function getShippingInclDiscountRaw()
    {
        return $this->getShippingAmountRaw() - $this->getShippingDiscountAmountRaw();
    }

    /**
     * @return int
     */
    public function getTaxAmount()
    {
        return $this->fetchNormalizeValue(self::FIELD_TAX_AMOUNT);
    }

    /**
     * @return int
     */
    public function getTaxAmountRaw()
    {
        return $this->fetchNormalizeValue(self::FIELD_TAX_AMOUNT, true);
    }

    //<editor-fold desc="Helpers">

    /**
     * @param string $field
     * @param bool $raw
     * @return mixed
     */
    private function fetchNormalizeValue($field, $raw = false)
    {
        $value = $this->totals[$field];

        if (!$raw) {
            $value = (int)round(bcmul($value, 100));
        }

        return $value;
    }

    //</editor-fold>
}
