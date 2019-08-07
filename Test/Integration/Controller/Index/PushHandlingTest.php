<?php
/**
 * Created by PhpStorm.
 * User: David.Owusu
 * Date: 12.06.2019
 * Time: 11:53
 */

namespace Heidelpay\Gateway\Test\Integration\Controller\Index;


use Heidelpay\Gateway\Test\Integration\IntegrationTestAbstract;
use Heidelpay\Gateway\Test\Integration\data\provider\PushResponse;
use Magento\Customer\Api\CustomerManagementInterface;
use Magento\Customer\Model\Customer;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order;
use SimpleXMLElement;
use Zend\Http\Headers;


/**
 * @inheritDoc
 *
 * @property \Magento\Framework\App\Request\Http $_request
 *
 * @method  \Magento\Framework\App\Request\Http getResponse()
 *
 */
class PushHandlingTest extends IntegrationTestAbstract
{
    const CONTROLLER_PATH = 'hgw/index/push';

    public $loggerMock;

    public function setUp()
    {
        parent::setUp();

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();

        /** Set Request type */
        $request->setMethod($request::METHOD_POST);
        $request->setHeaders(Headers::fromString('content-type: application/xml'));
    }

    public static function loadFixture()
    {
        include __DIR__ . '/../../_files/products.php';
        include __DIR__ . '/../../_files/categories.php';
        include __DIR__ . '/../../_files/coupons.php';
        include __DIR__ . '/../../_files/customer.php';
    }

    public function testTrue()
    {
        $this->assertTrue(true);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testEmptyRequest()
    {
        $request = $this->getRequest();
        $request->setMethod($request::METHOD_GET);

        $this->dispatch(self::CONTROLLER_PATH);
        $this->getResponse();
        $response = $this->getResponse()->getBody();
        $this->assertEmpty($response, $response);
    }

    /**
     * Test creation of new order via push if no order exists already.
     *
     * @dataProvider dataProviderPushCreatesNewTransactionDP
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture loadFixture
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store currency/options/allow EUR
     * @param string $paymentCode
     * @param $paymentMethod
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testPushCreatesNewTransaction($paymentCode, $paymentMethod)
    {
        list($quote, $xml) = $this->prepareRequest($paymentCode, $paymentMethod);
        $this->dispatch(self::CONTROLLER_PATH);

        /** Step 4 - Evaluate end results (heidelpay)Transaction, Quotes, Orders */
        $fetchedQuote = $this->quoteRepository->get($quote->getId());

        /** @var Order $order */
        $fetchedOrder = $this->orderHelper->fetchOrder($quote->getId());

        $this->assertNotNull($fetchedQuote);
        $this->assertEquals('0', $fetchedQuote->getIsActive());

        $this->assertFalse($fetchedOrder->isEmpty(), 'Order creation failed: Order is empty');

        // Check Transaction
        $collection = $this->transactionFactory->create();
        /** @var \Heidelpay\Gateway\Model\Transaction $heidelpayTransaction */
        $heidelpayTransaction = $collection->loadByTransactionId($xml->Transaction->Identification->UniqueID);
        $this->assertNotNull($heidelpayTransaction);
        $this->assertFalse($heidelpayTransaction->isEmpty());

        $isPreAuthorization = 'PA' ===$this->paymentHelper->splitPaymentCode($paymentCode)[1];
         // Check Amounts
            $this->assertEquals(
                $fetchedOrder->getGrandTotal(),
                $xml->Transaction->Payment->Clearing->Amount,
                'grand total amount doesn\'t match');

        if (!$isPreAuthorization)
        {
            $this->assertEquals(
                (float)$xml->Transaction->Payment->Clearing->Amount,
                (float)$fetchedOrder->getTotalPaid(),
                'Order state: ' .$fetchedOrder->getStatus() .'. Total paid amount doesn\'t match');

            $this->assertEquals('processing', $fetchedOrder->getState());
        }

    }

    public function dataProviderPushCreatesNewTransactionDP()
    {
        return [
            'Create from IV.RC' => ['IV.RC', 'hgwivs'],
            'Create from CC.DB' => ['CC.DB', 'hgwcc'],
            'Create from DD.DB' => ['DD.DB', 'hgwdd'],
            'Create from OT.RC' => ['OT.RC', 'hgwsue'],
            'Create from OT.PA' => ['OT.PA', 'hgwsue'],
            'Create from PP.RC' => ['PP.RC', 'hgwpp'],
        ];
    }

    public function CreateNoOrderFromInvalidTransactionTypesDP()
    {
        return [
            'create no order from IV.RV' => ['IV.RV', 'hgwivs'],
            'create no order from CC.RF' => ['CC.RF', 'hgwcc'],
            'create no order from IV.IN' => ['IV.IN', 'hgwivs'],
            'create no order from IV.FI' => ['IV.FI', 'hgwivs'],
        ];
    }

    /**
     * No order should be created for transaction types other then defined.
     *
     * @dataProvider CreateNoOrderFromInvalidTransactionTypesDP
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture loadFixture
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store currency/options/allow EUR
     * @param string $paymentCode
     * @param $paymentMethod
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testCreateNoOrderFromInvalidTransactionTypes($paymentCode, $paymentMethod)
    {
        list($quote, $xml) = $this->prepareRequest($paymentCode, $paymentMethod);
        $this->dispatch(self::CONTROLLER_PATH);

        /** Step 4 - Evaluate end results (heidelpay)Transaction, Quotes, Orders */
        $fetchedQuote = $this->quoteRepository->get($quote->getId());

        /** @var Order $order */
        $fetchedOrder = $this->orderHelper->fetchOrder($quote->getId());

        $this->assertNotNull($fetchedQuote);
        $this->assertEquals('1', $fetchedQuote->getIsActive());

        $this->assertTrue($fetchedOrder->isEmpty(), 'no Order should be created here');

        // Check Transaction
        $collection = $this->transactionFactory->create();

        // Check Transaction
        /** @var \Heidelpay\Gateway\Model\Transaction $heidelpayTransaction */
        $heidelpayTransaction = $collection->loadByTransactionId($xml->Transaction->Identification->UniqueID);
        $this->assertNotNull($heidelpayTransaction);
        $this->assertTrue($heidelpayTransaction->isEmpty());

        // Check amount of history entries.
        $histories = $fetchedOrder->getAllStatusHistory();
        $this->assertCount(0, $histories);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    protected function generateQuote($paymentMethod)
    {
        $quoteId = $this->cartManagement->createEmptyCart();
        /** @var CustomerManagementInterface $customerRepository */
        $customerRepository = $this->createObject(\Magento\Customer\Api\CustomerRepositoryInterface::class);

        /** @var Customer $customer */
        $customer = $customerRepository->get('l.h@mail.com');

        /** Step 1 - Create a cart | Sometimes also create an order for that cart.*/
        /** @var Quote $quote */
        $quote = $this->quoteRepository->get($quoteId);

        $product = $this->productRepository->getById('1');
        $quote->addProduct($product);

        $product = $this->productRepository->getById('2');
        $quote->assignCustomer($customer);
        $quote->addProduct($product);

        /** @var Payment $payment */
        $quote->getPayment()->setMethod($paymentMethod);
        //$quote->getBillingAddress()->addData($addressData);
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCustomerId($customer->getId())
            ->collectShippingRates()
            ->setFreeShipping(true)
            ->setShippingMethod('flatrate_flatrate')
            ->setPaymentMethod('hgwcc');

        $quote->collectTotals();
        $quote->save();
        return array($customer, $quote);
    }

    /**
     * @param array $pushSpecification
     * @param $paymentCode
     * @param $quote
     * @param $customer
     * @return SimpleXMLElement
     */
    protected function preparePushNotification($paymentCode, $quote, $customer)
    {
        $pushProvider = $this->createObject(PushResponse::class);

        $pushSpecification = [
            'TransactionID' => $quote->getId(),
            'Amount' => $quote->getGrandTotal(),
            'ShopperID' => $customer->getId($customer->getId())
        ];

        /** @var SimpleXMLElement $xml */
        $xml = new SimpleXMLElement($pushProvider->providePushXml($pushSpecification));
        $xml->Transaction->Payment->addAttribute('code', $paymentCode);
        return $xml;
    }

    /**
     * @param $paymentCode
     * @param $paymentMethod
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function prepareRequest($paymentCode, $paymentMethod)
    {
        /** Step 1 - Prepare data. Quote, Customer, XML */
        list($customer, $quote) = $this->generateQuote($paymentMethod);

        /** @var PushResponse $pushProvider */
        $xml = $this->preparePushNotification($paymentCode, $quote, $customer);

        /** Step 2 Assertions before push controller is called */
        /** @var Order $fetchedOrder */
        $fetchedOrder = $this->orderHelper->fetchOrder($quote->getId());
        $this->assertTrue($fetchedOrder->isEmpty());
        $this->assertNotNull($quote);

        /** Step 3 - Perform the actual test request on controller */
        $this->getRequest()->setContent($xml->saveXML());
        return array($quote, $xml);
    }

}