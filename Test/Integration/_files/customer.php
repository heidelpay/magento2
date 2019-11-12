<?php
/**
 * Creates a customer for testing
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @author  David Owusu <development@heidelpay.com>
 *
 * @package  heidelpay/magento2
 */

$yesterday = new DateTime();
$yesterday->sub(new \DateInterval('P1D'));
$tomorrow= new DateTime();
$tomorrow->add(new \DateInterval('P1D'));

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var $product \Magento\Catalog\Model\Product */
$customerFactory = $objectManager->create(Magento\Customer\Api\Data\CustomerInterface::class);

/** @var \Magento\Tax\Model\ClassModel $customerTaxClass */
$customerTaxClass = $objectManager->create(Magento\Tax\Model\ClassModel::class);
$customerTaxClass->load('Retail Customer', 'class_name');

/** @var \Magento\Customer\Model\Group $customerGroup */
$customerGroup = $objectManager->create(Magento\Customer\Model\Group::class)
    ->load('custom_group', 'customer_group_code');
$customerGroup->setTaxClassId($customerTaxClass->getId())->save();

$customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory');
/** @var CustomerInterface $customer */
$customer = $customerFactory->create()
    ->setEmail('l.h@mail.com')
    ->setFirstname('Linda')
    ->setLastname('Heideich')
    ->setPassword('123456789')
    ->setGroupId($customerGroup->getId())
    ->save();

$addressFactory = $objectManager->get('\Magento\Customer\Model\AddressFactory');
$addressFactory->create()
    ->setCustomerId($customer->getId())
    ->setFirstname('Linda')
    ->setLastname('Heideich')
    ->setCountryId('DE')
    ->setPostcode('69115')
    ->setCity('Heidelberg')
    ->setTelephone(1234567890)
    ->setFax(123456789)
    ->setStreet('Vangerowstr. 18')
    ->setIsDefaultBilling('1')
    ->setIsDefaultShipping('1')
    ->setSaveInAddressBook('1')
    ->setIncrementId(2)
    ->save();