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

        /*$this->loggerMock = $this->createMock(Logger::class); //createMOck can use

        $this->getObjectManager()->addSharedInstance($this->loggerMock,get_class($this->loggerMock));

        $this->getObjectManager()->configure([
            'preferences' => [
                LoggerInterface::class => get_class($this->loggerMock)
            ]
        ]);*/
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
     * @dataProvider dataProviderPushCreatesNewTransactionDP
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture loadFixture
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store currency/options/allow EUR
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testPushCreatesNewTransaction($pushSpecification = null)
    {
        $pushSpecification = [];
        /** Step 0 - Create fictures (products, customer etc.)and save them to database */
        //$this->generateCustomerFixture();
        list($customer, $quote) = $this->generateQuote();

        $this->assertNotNull($quote);

        /** Step 2 - Load push */
        /** @var PushResponse $pushProvider */
        $pushProvider = $this->createObject(PushResponse::class);

        $pushSpecification['TransactionID'] = isset($pushSpecification['TransactionID'])?:$quote->getId();
        $pushSpecification['Amount'] = number_format($quote->getGrandTotal(), 2, '.', '');
        $pushSpecification['ShopperID'] = $customer->getId($customer->getId());

        /** @var SimpleXMLElement $xml */
        $xml = new SimpleXMLElement($pushProvider->providePushXml($pushSpecification));

        /** Step 3 - Modify push to match cart id And so on [Pushprovider] */

        /** Step 4 - Set request content (xml) */
        $this->getRequest()->setContent($xml->saveXML());

        /** Assertions vor dem push */
        /** @var Order $order */
        $order = $this->orderHelper->fetchOrder($quote->getId());
        $this->assertTrue($order->isEmpty());

        /** Step 5 - perform the actual test request on controller */
        $this->dispatch(self::CONTROLLER_PATH);

        echo 'Response-Content: ' . $this->getResponse()->getContent();
        /** Step 6 - Evaluate expected results (heidelpay)Transaction, Quotes, Orders */
        $fetchedQuote = $this->quoteRepository->get($quote->getId());
        $this->assertNotNull($fetchedQuote);

        /** @var Order $order */
        $order = $this->orderHelper->fetchOrder($quote->getId());
        $this->assertFalse($order->isEmpty(), 'Order creation failed: Order is empty');
        $collection = $this->transactionFactory->create();

        /** @var \Heidelpay\Gateway\Model\Transaction $heidelpayTransaction */
        $heidelpayTransaction = $collection->loadByTransactionId($xml->Transaction->Identification->UniqueID);
        $this->assertNotNull($heidelpayTransaction);
        $this->assertFalse($heidelpayTransaction->isEmpty());
        echo 'transaction: ' . $heidelpayTransaction->getUniqueId();

        $histories = $order->getAllStatusHistory();

        $historyComments = [];

        foreach ($histories as $entry) {
            $historyComments[] = $entry->getComment();
         }

        $debug = [
            'order' => [
                'state' => $order->getStatus(),
                'total' => $order->getGrandTotal(),
                'paid' => $order->getTotalPaid(),
                'history' => $historyComments
            ],
            '$quote' => [
                'state' => $quote->getStatus(),
                'total' => 30.0,//number_format($quote->getGrandTotal(), 2, '.', ''),
                'paid' => $quote->getTotalPaid(),
            ],
            'dataArray' => $this->getResponse()->getBody()
        ];

        echo "\n" . 'debug:' . "\n" . print_r($debug, 1);

        $this->assertEquals(
            (float)$xml->Transaction->Payment->Clearing->Amount,
            (float)$order->getGrandTotal(),
            'grand total amount doesn\'t match');

        $this->assertEquals(
            (float)$xml->Transaction->Payment->Clearing->Amount,
            (float)$order->getTotalPaid(),
            'Order state: ' .$order->getStatus() .'. total paid amount doesn\'t match');
    }

    public function dataProviderPushCreatesNewTransactionDP()
    {

        return [
            'default Push-XML' => [null],
            'Create from IV.PA' => [

            ],
        ];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    protected function generateQuote()
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
        $quote->getPayment()->setMethod('hgwsue');
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

}