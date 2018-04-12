<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Tax\Model\ClassModel;

/** @var $objectManager \Magento\TestFramework\ObjectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var ClassModel $productTaxClass */
$productTaxClass = $objectManager->create(ClassModel::class);
$productTaxClass->load('Taxable Goods', 'class_name');

/** @var ClassModel $customerTaxClass */
$customerTaxClass = $objectManager->create(ClassModel::class);
$customerTaxClass->load('Retail Customer', 'class_name');

$taxRate = [
    'tax_country_id' => 'DE',
    'tax_region_id' => '*',
    'tax_postcode' => '*',
    'code' => 'de_mwst',
    'rate' => '19',
];
$rate = $objectManager->create('Magento\Tax\Model\Calculation\Rate')->setData($taxRate)->save();

/** @var Magento\Framework\Registry $registry */
$registry = $objectManager->get('Magento\Framework\Registry');
$registry->unregister('_fixture/Magento_Tax_Model_Calculation_Rate');
$registry->register('_fixture/Magento_Tax_Model_Calculation_Rate', $rate);

$ruleData = [
    'code' => 'de mwst Rule',
    'priority' => '0',
    'position' => '0',
    'customer_tax_class_ids' => [$customerTaxClass->getId()],
    'product_tax_class_ids' => [$productTaxClass->getId()],
    'tax_rate_ids' => [$rate->getId()],
];

$taxRule = $objectManager->create('Magento\Tax\Model\Calculation\Rule')->setData($ruleData)->save();

$registry->unregister('_fixture/Magento_Tax_Model_Calculation_Rule');
$registry->register('_fixture/Magento_Tax_Model_Calculation_Rule', $taxRule);

$ruleData['code'] = 'Test Rule Duplicate';

$objectManager->create('Magento\Tax\Model\Calculation\Rule')->setData($ruleData)->save();
