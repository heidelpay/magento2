<?php
$yesterday = new DateTime();
$yesterday->sub(new \DateInterval('P1D'));
$tomorrow= new DateTime();
$tomorrow->add(new \DateInterval('P1D'));

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create('Magento\Catalog\Model\Product');
$product
    ->setId(1)
    ->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product 1')
    ->setSku('simple1')
    ->setPrice(10)
    ->setData('news_from_date', $yesterday->format('Y-m-d H:i:s'))
    ->setData('news_to_date', $tomorrow->format('Y-m-d H:i:s'))
    ->setDescription('Description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setCategoryIds([2])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setUrlKey('my-url-key')
    ->save();

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create('Magento\Catalog\Model\Product');
$product
    ->setId(2)
    ->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product 2')
    ->setSku('simple2')
    ->setPrice(10)
    ->setData('news_from_date', null)
    ->setData('news_to_date', null)
    ->setDescription('Description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setCategoryIds([2])
    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
    ->setUrlKey('my-url-key2')
    ->save();