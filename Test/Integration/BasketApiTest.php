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

use Heidelpay\Gateway\Gateway\Config\HgwInvoiceSecuredPaymentConfig;
use Heidelpay\Gateway\Gateway\Config\HgwMainConfig;
use Heidelpay\Gateway\PaymentMethods\HeidelpayInvoiceSecuredPaymentMethod;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Catalog\ProductFixture;
use TddWizard\Fixtures\Catalog\ProductFixtureRollback;
use TddWizard\Fixtures\Checkout\CartBuilder;
use TddWizard\Fixtures\Customer\CustomerBuilder;
use TddWizard\Fixtures\Customer\CustomerFixture;
use TddWizard\Fixtures\Customer\CustomerFixtureRollback;

class BasketApiTest extends AbstractController
{
    const NUMBER_OF_PRODUCTS = 5;

    /**
     * @var CustomerFixture $customerFixture
     */
    private $customerFixture;

    /**
     * @var array $productFixture
     */
    private $productFixtures = [];

    /** @var \Magento\TestFramework\ObjectManager $objectManager*/
    private $objectManager;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->customerFixture = new CustomerFixture(
            CustomerBuilder::aCustomer()->build()
        );

        for ($idx = 0; $idx < self::NUMBER_OF_PRODUCTS; $idx++) {
            $price = mt_rand(1, 30000) / 100;
            echo "\nPreis:" . $price;
            $this->productFixtures[] = new ProductFixture(
                ProductBuilder::aSimpleProduct()
                    ->withPrice($price)
                    ->build()
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        CustomerFixtureRollback::create()->execute($this->customerFixture);

        foreach ($this->productFixtures as $productFixture) {
            ProductFixtureRollback::create()->execute($productFixture);
        }
    }

    /**
     * @test
     */
    public function verifySomething()
    {
        $mainConfigMock = $this->mockMainConfig();

        $configMock = $this->getMock(
            HgwInvoiceSecuredPaymentConfig::class,
            ['getChannel'],
            [],
            '',
            false
        );
        $configMock->method('getChannel')->willReturn('31HA07BC81856CAD6D8E05CDDE7E2AC8');

        // override config preference to inject custom config parameters
        $this->objectManager->addSharedInstance(
            $configMock,
            'HgwInvoiceSecuredConfig' // wenn das nicht geht, dann die MockConfig::class hier verwenden
        );

        $this->customerFixture->login();

        $cart = CartBuilder::forCurrentSession();
        foreach ($this->productFixtures as $productFixture) {
            $cart = $cart->withSimpleProduct($productFixture->getSku(), mt_rand(1, 3));
        }
        $cart = $cart->build();
        $quote = $cart->getQuote();

        /** @var HeidelpayInvoiceSecuredPaymentMethod $method */
        $method = $this->objectManager->create(
            HeidelpayInvoiceSecuredPaymentMethod::class,
            ['config' => $configMock, 'mainConfig' => $mainConfigMock]
        );

        /** @var \Heidelpay\PhpPaymentApi\Response $response */
        $response = $method->getHeidelpayUrl($quote);



        echo "\nResponse: " . $response->toJson();
    }

    private function mockMainConfig()
    {
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);

        $configMock = $this->getMock(
            HgwMainConfig::class,
            ['getSecuritySender', 'getUserPasswd', 'getUserLogin', 'isSandboxModeActive'],
            ['scopeConfig' => $scopeConfig]
        );
        $configMock->method('getUserLogin')->willReturn('31ha07bc8142c5a171744e5aef11ffd3');
        $configMock->method('getUserPasswd')->willReturn('93167DE7');
        $configMock->method('getSecuritySender')->willReturn('31HA07BC8142C5A171745D00AD63D182');
        $configMock->method('isSandboxModeActive')->willReturn(true);

        // override mainconfig preference to inject custom config parameters
        $this->objectManager->addSharedInstance(
            $configMock,
            HgwMainConfig::class // wenn das nicht geht, dann die MockConfig::class hier verwenden
        );

        return $configMock;
    }
}
