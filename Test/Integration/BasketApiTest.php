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
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CouponManagementInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\SalesRule\Model\Rule;
use \Magento\Quote\Model\Quote;

class BasketApiTest extends AbstractController
{
    const NUMBER_OF_PRODUCTS = 5;

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
            'fixed cart 20 EUR coupon' => ['COUPON_FIXED_CART_20_EUR']
//            '20 percent coupon /wo shipping' => ['COUPON_20_PERC_WO_SHIPPING'],
//            '20 percent coupon /w shipping' => ['COUPON_20_PERC_W_SHIPPING']
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

        $cartId = $this->cartManagement->createEmptyCart($this->customerFixture->getId());

        foreach ($this->productFixtures as $productFixture) {
            /** @var CartItemInterface $quoteItem */
            $quoteItem = $this->createObject(CartItemInterface::class);
            $quoteItem->setQuoteId($cartId);
            $quoteItem->setProduct($productFixture);
            $quoteItem->setQty(mt_rand(1, 3));
            $this->cartItemRepository->save($quoteItem);
        }

        if ($couponCode !== null) {
            $this->couponManagement->set($cartId, $couponCode);
        }

        /**
         * @var Quote $quote
          */
        $quote = $this->quoteRepository->get($cartId);

        /** @var Basket $basket */
        $basket = $this->basketHelper->convertQuoteToBasket($quote)->getBasket();

        echo "\n getGrandTotal: " . $quote->getGrandTotal();
        echo "\n getAmountTotalNet: " . $basket->getAmountTotalNet();
        echo "\n getAmountTotalVat: " . $basket->getAmountTotalVat();
        echo "\n getAmountTotalDiscount: " . $basket->getAmountTotalDiscount();

        /** @var QuoteWrapper $basketTotals */
        $basketTotals = $this->createObject(QuoteWrapper::class, ['quote' => $quote]);
        echo "\n\n basket: " . print_r($basketTotals->dump(), 1);

        $this->assertEquals(
            (int)bcmul($this->paymentHelper->format($quote->getGrandTotal()), 100),
            $basket->getAmountTotalNet() + $basket->getAmountTotalVat()
        );
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
        /** @var CustomerFactory $customerFactory */
        $customerFactory = $this->getObject('\Magento\Customer\Model\CustomerFactory');

        /** @var Customer $customer */
        $customer = $customerFactory->create();
        $customer
            ->setEmail('l.h@mail.com')
            ->setFirstname('Linda')
            ->setLastname('Heideich')
            ->setPassword('123456789');
        $customer->save();

        $addressFactory = $this->getObject('\Magento\Customer\Model\AddressFactory');

        /** @var Address $address */
        $address = $addressFactory->create();
        $address
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
            ->setSaveInAddressBook('1');
        $address->save();

        $this->customerFixture = $customer;
    }

    private function generateProductFixtures($number)
    {
        // create product fixtures
        for ($idx = 1; $idx <= $number; $idx++) {
            /** @var $product Product */
            $product = $this->createObject(Product::class);
            $product
                ->setId($idx)
                ->setTypeId(Type::TYPE_SIMPLE)
                ->setWebsiteIds([1])
                ->setName('Simple Product ' . $idx)
                ->setSku('simple' . $idx)
                ->setPrice(mt_rand(1, 30000) / 100)
                ->setDescription('Description')
                ->setVisibility(Visibility::VISIBILITY_BOTH)
                ->setStatus(Status::STATUS_ENABLED)
                ->setStockData(
                    ['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]
                )
                ->setUrlKey('url-key' . $idx)
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

    //</editor-fold>
}
