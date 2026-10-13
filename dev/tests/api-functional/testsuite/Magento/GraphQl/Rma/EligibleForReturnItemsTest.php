<?php
/************************************************************************
 * Copyright 2020 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\GraphQl\Rma;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Rma\Test\Fixture\Rma as RmaFixture;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\Sales\Test\Fixture\Shipment as ShipmentFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for eligible for return items
 */
class EligibleForReturnItemsTest extends GraphQlAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * Setup
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->orderFactory = $this->objectManager->get(OrderInterfaceFactory::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
        $this->fixtures = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
    }

    /**
     * Test eligible items for return
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/enabled_on_product", 1),
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ProductFixture::class, as: 'product2'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product1.id$', 'qty' => 1]),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product2.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product1.id$'],
                    ['product_id' => '$product2.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testEligibleForReturn()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderNumber}"}}) {
      items {
        items_eligible_for_return {
          id
          product_name
        }
      }
    }
  }
}
QUERY;

        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderItems = $order->getItems();

        $expectedResult = [];

        foreach ($orderItems as $item) {
            $expectedResult[] = [
                'id' => $this->idEncoder->encode((string)$item->getItemId()),
                'product_name' => $item->getName()
            ];
        }

        self::assertEqualsCanonicalizing(
            $expectedResult,
            $response['customer']['orders']['items'][0]['items_eligible_for_return']
        );
    }

    /**
     * Test eligible items for return
     */
    #[
        Config("sales/magento_rma/enabled", 0),
        Config("sales/magento_rma/enabled_on_product", 0),
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ProductFixture::class, as: 'product2'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product1.id$', 'qty' => 1]),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product2.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product1.id$'],
                    ['product_id' => '$product2.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithDisabledRma()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderNumber}"}}) {
      items {
        items_eligible_for_return {
          id
          product_name
        }
      }
    }
  }
}
QUERY;

        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        self::assertEmpty($response['customer']['orders']['items'][0]['items_eligible_for_return']);
    }

    /**
     * Test eligible items for return with not existing order number
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        DataFixture(ProductFixture::class, as: 'product1'),
        DataFixture(ProductFixture::class, as: 'product2'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product1.id$', 'qty' => 1]),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product2.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product1.id$'],
                    ['product_id' => '$product2.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testWithNotExistingOrderNumber()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "111000555"}}) {
      items {
        items_eligible_for_return {
          id
          product_name
        }
      }
    }
  }
}
QUERY;

        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        self::assertEmpty($response['customer']['orders']['items']);
    }

    /**
     * Test eligible items for return with unauthorized customer
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/enabled_on_product", 1),
        DataFixture(ProductFixture::class, as: 'product'),
        DataFixture(Customer::class, as: 'customer'),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], as: 'quote'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$quote.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$quote.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$quote.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'], 'invoice'),
        DataFixture(
            ShipmentFixture::class,
            [
                'order_id' => '$order.id$',
                'items' => [
                    ['product_id' => '$product.id$'],
                ]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testUnauthorized()
    {
        $orderNumber = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderNumber}"}}) {
      items {
        items_eligible_for_return {
          id
          product_name
        }
      }
    }
  }
}
QUERY;
        $this->graphQlQuery($query);
    }
}
