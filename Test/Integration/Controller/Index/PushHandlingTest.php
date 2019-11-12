<?php
/**
 * This test class provides tests for the push controller.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @author  David Owusu <development@heidelpay.com>
 *
 * @package  heidelpay/magento2
 */

namespace Heidelpay\Gateway\Test\Integration\Controller\Index;


use Heidelpay\Gateway\Helper\Response as ResponseHelper;
use Heidelpay\Gateway\Model\ResourceModel\Transaction\Collection;
use Heidelpay\Gateway\Model\Transaction;
use Heidelpay\Gateway\Test\Integration\data\provider\PushResponse;
use Heidelpay\Gateway\Test\Integration\IntegrationTestAbstract;
use Heidelpay\Gateway\Test\Mocks\Helper\Response as ResponseHelperMock;
use Heidelpay\PhpPaymentApi\Constants\TransactionType;
use Magento\Customer\Api\CustomerManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order;
use SimpleXMLElement;
use Zend\Http\Headers;


/**
 * @inheritDoc
 *
 * @property Http $_request
 *
 * @method  Http getResponse()
 *
 */
class PushHandlingTest extends IntegrationTestAbstract
{
    const CONTROLLER_PATH = 'hgw/index/push';

    public $loggerMock;

    public function setUp()
    {
        parent::setUp();

        /** @var Http $request */
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

    /**
     * @test
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function EmptyRequest()
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
     * @test
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
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function PushCreatesNewTransaction($paymentCode, $paymentMethod)
    {
        list($quote, $xml) = $this->prepareRequest($paymentCode, $paymentMethod);
        $this->assertQuoteHasNoOrder($quote);
        $this->dispatch(self::CONTROLLER_PATH); // Call push controller.

        /** Evaluate end results. Quote, Order, Transaction */
        $this->assertQuoteStatus($quote->getId());

        /** @var Order $order */
        $fetchedOrder = $this->orderHelper->fetchOrder($quote->getId());
        $this->assertFalse($fetchedOrder->isEmpty(), 'Order creation failed: Order is empty');

        $uniqueId = $xml->Transaction->Identification->UniqueID;
        $this->AssertMagentoTransactionExists($uniqueId);

        // Check Amounts
        $this->assertEquals(
            $xml->Transaction->Payment->Clearing->Amount,
            $fetchedOrder->getGrandTotal(),
            'Grand total amount doesn\'t match');


        $shouldBeMarkedAsPaid = TransactionType::RESERVATION !== $this->paymentHelper->splitPaymentCode($paymentCode)[1];
        if ($shouldBeMarkedAsPaid) {
            $this->assertEquals(
                (float)$xml->Transaction->Payment->Clearing->Amount,
                (float)$fetchedOrder->getTotalPaid(),
                'Order state: ' .$fetchedOrder->getStatus() .'. Total paid amount doesn\'t match');

            $this->assertEquals('processing', $fetchedOrder->getState());
        }

    }

    /** Data provider for transaction types that should create new order.
     * @return array
     */
    public function dataProviderPushCreatesNewTransactionDP()
    {
        return [
            'Create order from invoice receipt ' => ['IV.RC', 'hgwivs'],
            'Create order from credit card debit' => ['CC.DB', 'hgwcc'],
            'Create order from sofort receipt' => ['OT.RC', 'hgwsue'],
            'Create order from sofort reservation' => ['OT.PA', 'hgwsue'],
            'Create order from prepayment receipt' => ['PP.RC', 'hgwpp'],
        ];
    }

    /** Data provider for transaction types that should NOT create new order.
     * @return array
     */
    public function CreateNoOrderFromInvalidTransactionTypesDP()
    {
        return [
            'Create no order from invoice reversal' => ['IV.RV', 'hgwivs'],
            'Create no order from credit card refund' => ['CC.RF', 'hgwcc'],
            'Create no order from invoice init' => ['IV.IN', 'hgwivs'],
            'Create no order from invoice finalize' => ['IV.FI', 'hgwivs'],
        ];
    }

    /**
     * No order should be created for transaction types other then defined.
     *
     * @test
     * @dataProvider CreateNoOrderFromInvalidTransactionTypesDP
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture loadFixture
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store currency/options/allow EUR
     * @param string $paymentCode
     * @param $paymentMethod
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function CreateNoOrderFromInvalidTransactionTypes($paymentCode, $paymentMethod)
    {
        list($quote, $xml) = $this->prepareRequest($paymentCode, $paymentMethod);
        $this->assertQuoteHasNoOrder($quote);
        $this->dispatch(self::CONTROLLER_PATH);

        /** Evaluate end results (heidelpay)Transaction, Quotes, Orders */
        $this->assertQuoteStatus($quote->getId(), '1');

        /** @var Order $order */
        $fetchedOrder = $this->orderHelper->fetchOrder($quote->getId());
        $this->assertTrue($fetchedOrder->isEmpty(), 'No Order should be created here');

        // Check Transaction
        /** @var Collection $collection */
        $collection = $this->transactionFactory->create();

        /** @var Transaction $heidelpayTransaction */
        $heidelpayTransaction = $collection->loadByTransactionId($xml->Transaction->Identification->UniqueID);
        $this->assertNotNull($heidelpayTransaction);
        $this->assertTrue($heidelpayTransaction->isEmpty());
    }

    /**
     * @param $paymentMethod
     * @return array
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function generateQuote($paymentMethod)
    {
        $quoteId = $this->cartManagement->createEmptyCart();
        /** @var CustomerManagementInterface $customerRepository */
        $customerRepository = $this->createObject(CustomerRepositoryInterface::class);

        /** @var Customer|CustomerInterface $customer */
        $customer = $customerRepository->get('l.h@mail.com');

        /** Create a cart | Sometimes also create an order for that cart.*/
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

        $quote->collectTotals()->save();
        return array($customer, $quote);
    }

    /**
     * @param $paymentCode
     * @param Quote $quote
     * @param Customer $customer
     * @return SimpleXMLElement
     */
    protected function preparePushNotification($paymentCode, Quote $quote, $customer)
    {
        /** @var PushResponse $pushProvider */
        $pushProvider = $this->createObject(PushResponse::class);

        $pushSpecification = [
            'TransactionID' => $quote->getId(),
            'Amount' => $quote->getGrandTotal(),
            'ShopperID' => $customer->getId()
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
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function prepareRequest($paymentCode, $paymentMethod)
    {
        /** Disable hash validation */
        $this->getObjectManager()->configure(
            ['preferences' => [ResponseHelper::class => ResponseHelperMock::class]]
        );

        /** Prepare data. Quote, Customer, XML */
        list($customer, $quote) = $this->generateQuote($paymentMethod);

        /** @var PushResponse $pushProvider */
        $xml = $this->preparePushNotification($paymentCode, $quote, $customer);

        /** Perform the actual test request on controller */
        $this->getRequest()->setContent($xml->saveXML());
        return array($quote, $xml);
    }

    /** Assert that magento transaction with given unique id exists.
     * @param $uniqueId
     */
    private function AssertMagentoTransactionExists($uniqueId)
    {
        $collection = $this->transactionFactory->create();
        /** @var Transaction $heidelpayTransaction */
        $heidelpayTransaction = $collection->loadByTransactionId($uniqueId);
        $this->assertNotNull($heidelpayTransaction);
        $this->assertFalse($heidelpayTransaction->isEmpty());
    }

    /** Assertions that Quote should exists but order doesn't.
     * @param $quote
     * @return void
     */
    private function assertQuoteHasNoOrder(Quote $quote)
    {
        /** @var Order $fetchedOrder */
        $fetchedOrder = $this->orderHelper->fetchOrder($quote->getId());
        $this->assertTrue($fetchedOrder->isEmpty());
        $this->assertNotNull($quote);
    }

    /** Assert that quote status is set as expected.
     * @param $quoteId
     * @param string $expectedStatus
     * @throws NoSuchEntityException
     */
    private function assertQuoteStatus($quoteId, $expectedStatus = '0')
    {
        $fetchedQuote = $this->quoteRepository->get($quoteId);
        $this->assertNotNull($fetchedQuote);

        $message = 'New order was created - Quote should NOT be active anymore!';
        if ($expectedStatus !== '0') {
            $message = 'No order was created - Quote should still be active!';
        }

        $this->assertEquals(
            $expectedStatus,
            $fetchedQuote->getIsActive(),
            $message);
    }
}
