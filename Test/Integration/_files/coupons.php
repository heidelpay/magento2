<?php
/**
 * Create coupon fixtures
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpay/magento2
 */

use Magento\Customer\Model\GroupManagement;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\Rule;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use \Magento\SalesRule\Model\ResourceModel\Rule as ResourceRule;

$objectManager = Bootstrap::getObjectManager();

// Create 20€ FIXED CARD Coupon
/** @var Rule $salesRule */
$salesRule = $objectManager->create(Rule::class);
$salesRule->setData(
    [
        'name' => '20€',
        'is_active' => 1,
        'customer_group_ids' => [GroupManagement::NOT_LOGGED_IN_ID],
        'conditions' => [],
        'coupon_type' => Rule::COUPON_TYPE_SPECIFIC,
        'simple_action' => Rule::CART_FIXED_ACTION,
        'discount_amount' => 20,
        'discount_step' => 0,
        'stop_rules_processing' => 1,
        'website_ids' => [
            $objectManager->get(StoreManagerInterface::class)->getWebsite()->getId()
        ]
    ]
);
$objectManager->get(ResourceRule::class)->save($salesRule);

// Create coupon and assign "20% fixed discount" rule to this coupon.
/** @var CouponInterface $coupon */
$coupon = $objectManager->create(Coupon::class);
$code = 'COUPON_FIXED_CART_20_EUR';
$coupon->setRuleId($salesRule->getId())
    ->setCode($code)
    ->setType(CouponInterface::TYPE_MANUAL);
$objectManager->get(CouponRepositoryInterface::class)->save($coupon);