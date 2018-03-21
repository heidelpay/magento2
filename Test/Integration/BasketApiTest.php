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
use Heidelpay\PhpBasketApi\Object\Basket;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Quote\Model\Quote\Address;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

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
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->_objectManager = Bootstrap::getObjectManager();
        $this->generateCustomerFixture();
        $this->generateProductFixtures(self::NUMBER_OF_PRODUCTS);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture  default  currency/options/allow EUR
     * @magentoConfigFixture  default  currency/options/base EUR
     * @magentoConfigFixture  default  currency/options/default EUR
     *
     * @test
     * @throws \Heidelpay\PhpBasketApi\Exception\InvalidBasketitemPositionException
     */
    public function verifyBasketHasSameValueAsApiCall()
    {
        /** @var Session $magentoCustomerSession */
        $magentoCustomerSession = $this->getObject(Session::class);
        $loggedIn = $magentoCustomerSession->loginById($this->customerFixture->getId());

        $this->assertTrue($loggedIn, 'Could not log in');

        /** @var Cart $cart */
        $cart = $this->createObject(Cart::class);
        foreach ($this->productFixtures as $productFixture) {
            $cart = $cart->addProduct($productFixture, ['qty' => mt_rand(1, 3)]);
        }
        $cart = $cart->save();
        $quote = $cart->getQuote();

        /** @var BasketHelper $basketHelper */
        $basketHelper = $this->getObject(BasketHelper::class);

        /** @var Payment $paymentHelper */
        $paymentHelper = $this->getObject(Payment::class);

        /** @var Basket $basket */
        $basket = $basketHelper->convertQuoteToBasket($quote)->getBasket();

        $this->assertEquals(
            (int)bcmul($paymentHelper->format($quote->getGrandTotal()), 100),
            $basket->getAmountTotalNet() + $basket->getAmountTotalVat()
        );
    }

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
     * @param string $class
     * @return mixed
     */
    private function getObject($class)
    {
        return $this->_objectManager->get($class);
    }

    /**
     * @param string $class
     * @return mixed
     */
    private function createObject($class)
    {
        return $this->_objectManager->create($class);
    }
}
