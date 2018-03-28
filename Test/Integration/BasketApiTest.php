<?php
/**
 * This test class provides tests of the modules integration with the basket-api.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @author  Simon Gabriel <development@heidelpay.com>
 *
 * @package  heidelpay/magento2
 */
namespace Heidelpay\Gateway\Test\Integration;

use Heidelpay\Gateway\Helper\BasketHelper;
use Heidelpay\Gateway\Helper\Payment;
use Heidelpay\Gateway\Wrapper\QuoteWrapper;
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
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\Tax\Model\ClassModel;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\SalesRule\Model\Rule;
use \Magento\Quote\Model\Quote;
use Magento\OfflineShippingSampleData\Model\Tablerate;

class BasketApiTest extends AbstractController
{
    const ENABLE_DEBUG_OUTPUT = false;

    const NUMBER_OF_PRODUCTS = 3;

    /**
     * @var Customer $customerFixture
     */
    private $customerFixture;

    /**
     * @var array $productFixture
     */
    private $productFixtures = [];

    /**
     * @var CouponManagementInterface $couponManagement
     */
    private $couponManagement;

    /**
     * @var CartManagementInterface $cartManagement
     */
    private $cartManagement;

    /**
     * @var CartItemRepositoryInterface $cartItemRepository
     */
    private $cartItemRepository;

    /**
     * @var CartRepositoryInterface $quoteRepo
     */
    private $quoteRepository;

    /**
     * @var Payment $paymentHelper
     */
    private $paymentHelper;

    /**
     * @var BasketHelper $basketHelper
     */
    private $basketHelper;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        echo self::ENABLE_DEBUG_OUTPUT ? "\n\n\n" : '';

        /**
         * @var Tablerate $tablerate
         */
        $tablerate = $this->getObject(Tablerate::class);
        $tablerate->install(['Heidelpay_Gateway::Test/Integration/fixtures/tablerate.csv']);

        $this->generateCustomerFixture();
        $this->generateProductFixtures(self::NUMBER_OF_PRODUCTS);
        $this->couponManagement = $this->createObject(CouponManagementInterface::class);
        $this->cartManagement = $this->createObject(CartManagementInterface::class);
        $this->cartItemRepository = $this->createObject(CartItemRepositoryInterface::class);
        $this->quoteRepository = $this->getObject(CartRepositoryInterface::class);
        $this->basketHelper = $this->getObject(BasketHelper::class);
        $this->paymentHelper = $this->getObject(Payment::class);
    }

    /**
     * @return array
     */
    public function verifyBasketHasSameValueAsApiCallDP()
    {
        return [
            'No coupon' => [null],
            'fixed cart 20 EUR coupon' => ['COUPON_FIXED_CART_20_EUR'],
            '20 percent coupon /wo shipping' => ['COUPON_20_PERC_WO_SHIPPING']
            // Test deaktiviert, weil Magento bei Rabatt auf Shipping die MwSt nicht richtig berechnet.
            // '20 percent coupon /w shipping' => ['COUPON_20_PERC_W_SHIPPING']
        ];
    }

    /**
     * @dataProvider verifyBasketHasSameValueAsApiCallDP
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture couponFixtureProvider
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store currency/options/allow EUR
     *
     * @test
     * @param $couponCode
     * @throws \Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException
     */
    public function verifyBasketHasSameValueAsApiCall($couponCode)
    {
        $this->assertResult($couponCode);
    }

    /**
     * @dataProvider verifyBasketHasSameValueAsApiCallDP
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture couponFixtureProvider
     * @magentoDataFixture taxFixtureProvider
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store currency/options/allow EUR
     *
     * @test
     * @param $couponCode
     * @throws \Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException
     */
    public function verifyBasketHasSameValueAsApiCallPlusTaxes($couponCode)
    {
        $this->assertResult($couponCode);
    }

    /**
     * @dataProvider verifyBasketHasSameValueAsApiCallDP
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoDataFixture couponFixtureProvider
     * @magentoDataFixture taxFixtureProvider
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store tax/classes/shipping_tax_class 2
     * @magentoConfigFixture default_store currency/options/allow EUR
     *
     * @test
     * @param $couponCode
     * @throws \Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException
     */
    public function verifyBasketHasSameValueAsApiCallPlusTaxesPlusShippingTax($couponCode)
    {
        $this->assertResult($couponCode);
    }

    /**
     * @param $couponCode
     * @return array
     * @throws \Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException
     */
    private function performCheckout($couponCode)
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

        return array($quote, $basket);
    }

    //<editor-fold desc="Helper">

    /**
     * @param string $class
     * @return mixed
     */
    private function getObject($class)
    {
        return $this->getObjectManager()->get($class);
    }

    /**
     * @param string $class
     * @param array $params
     * @return mixed
     */
    private function createObject($class, array $params = [])
    {
        return $this->getObjectManager()->create($class, $params);
    }

    /**
     * @return ObjectManagerInterface
     */
    private function getObjectManager()
    {
        if (!($this->_objectManager instanceof ObjectManagerInterface)) {
            $this->_objectManager = Bootstrap::getObjectManager();
        }
        return $this->_objectManager;
    }
    //</editor-fold>

    //<editor-fold desc="Fixture Helpers">

    private function generateCustomerFixture()
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

    private function generateProductFixtures($number)
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
     * Create coupon fixtures.
     */
    public static function couponFixtureProvider()
    {
        self::removeDefaultRules();

        require __DIR__ . '/_files/coupons.php';
    }

    /**
     * Create tax fixtures.
     */
    public static function taxFixtureProvider()
    {
        require __DIR__ . '/_files/tax_classes.php';
    }

    /**
     * Remove default rules from shop.
     */
    private static function removeDefaultRules()
    {
        /** @var ObjectManagerInterface $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        /** @var RuleRepositoryInterface $ruleRepository */
        $ruleRepository = $objectManager->create(RuleRepositoryInterface::class);

        /** @var Array $rules */
        $rules = $objectManager->create(Rule::class)->getCollection();

        /** @var Rule $rule */
        foreach ($rules as $rule) {
            $ruleRepository->deleteById($rule->getRuleId());
        }
    }

    /**
     * @param string $title
     * @param array $data
     */
    private function echoToConsole($title, array $data = [])
    {
        echo "\n" . $title . ': ' . print_r($data, true);
    }

    /**
     * @param $basket
     */
    private function printItemsAndSums($basket)
    {
        $sum = 0;
        echo "\nProducts in Basket:";
        /** @var BasketItem $basketItem */
        foreach ($basket->getBasketItems() as $key => $basketItem) {
            if ('Discount' !== $basketItem->getTitle()) {
                echo "\nProduct #" . $key . ': ' .
                    $basketItem->getTitle() . "\t" .
                    $basketItem->getQuantity() . "x \t" .
                    $basketItem->getAmountNet() . ' (' .
                    $basketItem->getAmountPerUnit() . ')';

                $sum += $basketItem->getAmountNet();
            }
        }

        echo "\nSum: " . $sum . "\n";
    }

    /**
     * @param $quote
     */
    private function printBasketValues($quote)
    {
        /** @var QuoteWrapper $basketTotals */
        $basketTotals = $this->createObject(QuoteWrapper::class, ['quote' => $quote]);
        $this->echoToConsole('basket', $basketTotals->dump());
    }

    /**
     * @param $couponCode
     * @throws \Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException
     */
    private function assertResult($couponCode)
    {
        /** @var CartInterface $quote */
        /** @var Basket $basket */
        list($quote, $basket) = $this->performCheckout($couponCode);

        $grandTotal = (int)bcmul($this->paymentHelper->format($quote->getGrandTotal()), 100);
        $grandTotalCalculated = $basket->getAmountTotalNet() + $basket->getAmountTotalVat();
        $difference = $grandTotal - $grandTotalCalculated;

        echo "\nGrand Total:\t\t" . $grandTotal;
        echo "\nGrand Total (calc):\t" . $grandTotalCalculated;
        echo "\nDifference:\t\t" . $difference;
        echo "\nAmount Total Net:\t" . $basket->getAmountTotalNet();
        echo "\nAmount Total Vat:\t" . $basket->getAmountTotalVat() . "\n";

        $this->assertLessThanOrEqual(1, $difference, 'Basket and Payment value difference is greater than 1(ct).');
    }

    //</editor-fold>
}
