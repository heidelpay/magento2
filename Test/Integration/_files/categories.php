<?php
/**
 * creates a category for testing
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @author  David Owusu <development@heidelpay.com>
 *
 * @package  heidelpay/magento2
 */

/** @var $category \Magento\Catalog\Model\Category */
$category = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(Magento\Catalog\Model\Category::class);
$category->isObjectNew(true);
$category->setId(3)
    ->setName('Category 1')
    ->setParentId(2)
    ->setPath('1/2/3')
    ->setLevel(2)
    ->setAvailableSortBy('name')
    ->setDefaultSortBy('name')
    ->setIsActive(true)
    ->setPosition(1)
    ->setData('display_mode', 'new_products')
    ->save();