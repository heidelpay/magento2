<?php

namespace Heidelpay\Gateway\Test\Integration;

use Heidelpay\Gateway\Helper\BasketHelper;
use Heidelpay\Gateway\Helper\Order as OrderHelper;
use Heidelpay\Gateway\Helper\Payment;
use Heidelpay\PhpBasketApi\Object\Basket;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Group;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CouponManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Tax\Model\ClassModel;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use \Magento\Quote\Model\Quote;
use Magento\OfflineShippingSampleData\Model\Tablerate;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\CollectionFactory as HeidelpayTransactionCollectionFactory;


class IntegrationTestAbstract extends AbstractController
{
    const ENABLE_DEBUG_OUTPUT = false;

    const NUMBER_OF_PRODUCTS = 3;

    /**
     * @var Customer $customerFixture
     */
    protected $customerFixture;

    /**
     * @var array $productFixture
     */
    protected $productFixtures = [];

    /**
     * @var CouponManagementInterface $couponManagement
     */
    protected $couponManagement;

    /**
     * @var CartManagementInterface $cartManagement
     */
    protected $cartManagement;

    /**
     * @var CartItemRepositoryInterface $cartItemRepository
     */
    protected $cartItemRepository;

    /**
     * @var CartRepositoryInterface $quoteRepo
     */
    protected $quoteRepository;

    /**
     * @var Payment $paymentHelper
     */
    protected $paymentHelper;

    /**
     * @var BasketHelper $basketHelper
     */
    protected $basketHelper;
    protected $productRepository;
    /** @var OrderHelper $orderHelper */
    protected $orderHelper;
    /** @var HeidelpayTransactionCollectionFactory\ */
    protected $transactionFactory;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        echo self::ENABLE_DEBUG_OUTPUT ? "\n\n\n" : '';

        $this->transactionFactory = $this->createObject(HeidelpayTransactionCollectionFactory::class);
        $this->orderHelper = $this->createObject(OrderHelper::class);
        $this->productRepository = $this->createObject(ProductRepositoryInterface::class);
        $this->couponManagement = $this->createObject(CouponManagementInterface::class);
        $this->cartManagement = $this->createObject(CartManagementInterface::class);
        $this->cartItemRepository = $this->createObject(CartItemRepositoryInterface::class);
        $this->quoteRepository = $this->getObject(CartRepositoryInterface::class);
        $this->basketHelper = $this->getObject(BasketHelper::class);
        $this->paymentHelper = $this->getObject(Payment::class);
    }

    /**
     * @return ObjectManagerInterface
     */
    protected function getObjectManager()
    {
        if (!($this->_objectManager instanceof ObjectManagerInterface)) {
            $this->_objectManager = Bootstrap::getObjectManager();
        }
        return $this->_objectManager;
    }

    /**
     * @param string $class
     * @param array $params
     * @return mixed
     */
    protected function createObject($class, array $params = [])
    {
        return $this->getObjectManager()->create($class, $params);
    }

    /**
     * @param string $class
     * @return mixed
     */
    protected function getObject($class)
    {
        return $this->getObjectManager()->get($class);
    }

    protected function performCheckout($couponCode)
    {
        $cartId = $this->cartManagement->createEmptyCart();

        /** @var Product $productFixture */
        foreach ($this->productFixtures as $productFixture) {
            $quantity = mt_rand(1, 3);

            /** @var CartItemInterface $quoteItem */
            $quoteItem = $this->createObject(CartItemInterface::class);
            $quoteItem->setQuoteId($cartId);
            $quoteItem->setProduct($productFixture);
            $quoteItem->setQty($quantity);
            $this->cartItemRepository->save($quoteItem);
        }

        if ($couponCode !== null) {
            $this->couponManagement->set($cartId, $couponCode);
        }

        /**
         * @var Quote $quote
         */
        $quote = $this->quoteRepository->get($cartId);
        $quote->getShippingAddress()
            ->setCustomerId($this->customerFixture->getId())
            ->setFirstname('Linda')
            ->setLastname('Heideich')
            ->setCountryId('DE')
            ->setPostcode('69115')
            ->setCity('Heidelberg')
            ->setTelephone(1234567890)
            ->setFax(123456789)
            ->setStreet('Vangerowstr. 18')
            ->setShippingMethod('tablerate_bestway')
            ->save();

        /** @var Basket $basket */
        $basket = $this->basketHelper->convertQuoteToBasket($quote)->getBasket();

        if (self::ENABLE_DEBUG_OUTPUT) {
            $this->printBasketValues($quote);
            $this->printItemsAndSums($basket);
        }

        return $quote;
    }

    protected function generateCustomerFixture()
    {
        /** @var \Magento\Tax\Model\ClassModel $customerTaxClass */
        $customerTaxClass = $this->createObject(ClassModel::class);
        $customerTaxClass->load('Retail Customer', 'class_name');

        /** @var \Magento\Customer\Model\Group $customerGroup */
        $customerGroup = $this->createObject(Group::class)
            ->load('custom_group', 'customer_group_code');
        $customerGroup->setTaxClassId($customerTaxClass->getId())->save();

        $customerFactory = $this->getObject('\Magento\Customer\Model\CustomerFactory');
        /** @var CustomerInterface $customer */
        $customer = $customerFactory->create()
            ->setEmail('l.h@mail.com')
            ->setFirstname('Linda')
            ->setLastname('Heideich')
            ->setPassword('123456789')
            ->setGroupId($customerGroup->getId())
            ->save();

        $addressFactory = $this->getObject('\Magento\Customer\Model\AddressFactory');
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
            ->save();

        $this->customerFixture = $customer;
    }

    protected function generateProductFixtures($number)
    {
        /** @var ClassModel $productTaxClass */
        $productTaxClass = $this->createObject(ClassModel::class);
        $productTaxClass->load('Taxable Goods', 'class_name');

        // create product fixtures
        for ($idx = 1; $idx <= $number; $idx++) {
            $price = (mt_rand(1, 30000) / 100);

            /** @var Product $product */
            $product = $this->createObject(Product::class);
            $product
                ->setId($idx+2)
                ->setTypeId(Type::TYPE_SIMPLE)
                ->setAttributeSetId(4)
                ->setWebsiteIds([1])
                ->setName('Simple Product ' . $idx)
                ->setSku('simple' . $idx)
                ->setPrice($price)
                ->setData('news_from_date', null)
                ->setData('news_to_date', null)
                ->setVisibility(Visibility::VISIBILITY_BOTH)
                ->setStatus(Status::STATUS_ENABLED)
                ->setStockData(
                    ['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]
                )
                ->setUrlKey('url-key' . $idx)
                ->setTaxClassId($productTaxClass->getId())
                ->save();

            $this->productFixtures[] = $product;
        }
    }

    /**
     * @param string $title
     * @param array $data
     */
    protected function echoToConsole($title, array $data = [])
    {
        echo "\n" . $title . ': ' . print_r($data, true);
    }

    /**
     * @param string $title
     * @param array $data
     */
    protected function debugOutput($title, array $data = [])
    {
        if (self::ENABLE_DEBUG_OUTPUT) {
            $this->echoToConsole($title);
        }
    }

}