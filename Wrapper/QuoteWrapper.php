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
        $this->setQuote($quote);
        $this->totals = $quote->getShippingAddress()->toArray();
    }

    /**
     * Calculate shipping tax in percent
     *
     * @return float shipping tax in percent
     */
    public function getShippingTaxPercent()
    {
        $shipping_amount = $this->totals['shipping_amount'];

        if ((int)$shipping_amount === 0) {
            return 0.0;
        }

        $tax = bcdiv(bcmul($this->totals['shipping_tax_amount'], 100, 10), $shipping_amount);
        return round($tax, 2);
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
        return (int)round(bcmul(abs($this->totals['discount_amount']), 100, 10));
    }

    /**
     * @return string
     */
    public function getBasketReferenceId()
    {
        return sprintf('M2-S%dQ%d-%s', $this->quote->getStoreId(), $this->quote->getId(), date('YmdHis'));
    }

    /**
     * @return int
     */
    public function getShippingAmount()
    {
        return (int)round(bcmul($this->totals['shipping_amount'], 100, 10));
    }

    /**
     * @return int
     */
    public function getShippingTaxAmount()
    {
        return (int)round(bcmul($this->totals['shipping_tax_amount'], 100, 10));
    }

    /**
     * @return int
     */
    public function getShippingInclTax()
    {
        return (int)round(bcmul($this->totals['shipping_incl_tax'], 100, 10));
    }

    /**
     * @return int
     */
    private function getShippingDiscountAmount()
    {
        return (int)round(bcmul($this->totals['base_shipping_discount_amount'], 100, 10));
    }

    /**
     * @return int
     */
    private function getSubtotalWithDiscount()
    {
        return (int)round(bcmul($this->totals['subtotal_with_discount'], 100, 10));
    }

    /**
     * @param Quote $quote
     */
    private function setQuote($quote)
    {
        $quote->collectTotals();
        $this->quote = $quote;
    }

    /**
     * @return float|int
     */
    private function getActualShippingTax()
    {
        return (int)round(bcdiv(bcmul($this->getShippingInclDiscount(), $this->getShippingTaxPercent(), 10), 100, 10));
    }

    /**
     * @return int
     */
    private function getActualSubtotalTax()
    {
        return $this->getTaxAmount() - $this->getShippingTaxAmount();
    }

    /**
     * @return int
     */
    private function getShippingInclDiscount()
    {
        return $this->getShippingAmount() - $this->getShippingDiscountAmount();
    }

    /**
     * @return int
     */
    private function getTaxAmount()
    {
        return (int)round(bcmul($this->totals['tax_amount'], 100, 10));
    }
}
