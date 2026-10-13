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
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for eligible for return flag
 */
class EligibleForReturnFlagTest extends GraphQlAbstract
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
     * Setup
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->orderFactory = $this->objectManager->get(OrderInterfaceFactory::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
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
    ]
    public function testEligibleForReturn()
    {
        $order = DataFixtureStorageManager::getStorage()->get('order');
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $orderNumber = $order->getIncrementId();

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderNumber}"}}) {
      items {
        items {
          id
          eligible_for_return
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
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail(), 'password')
        );

        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderItems = $order->getItems();

        $expectedResult = [];

        foreach ($orderItems as $item) {
            $expectedResult[] = [
                'id' => $this->idEncoder->encode((string)$item->getItemId()),
                'eligible_for_return' => true
            ];
        }

        self::assertEqualsCanonicalizing(
            $expectedResult,
            $response['customer']['orders']['items'][0]['items']
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
    ]
    public function testWithDisabledRma()
    {
        $order = DataFixtureStorageManager::getStorage()->get('order');
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $orderNumber = $order->getIncrementId();

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderNumber}"}}) {
      items {
        items {
          id
          eligible_for_return
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
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail(), 'password')
        );

        $order = $this->orderFactory->create()->loadByIncrementId($orderNumber);
        $orderItems = $order->getItems();

        $expectedResult = [];

        foreach ($orderItems as $item) {
            $expectedResult[] = [
                'id' => $this->idEncoder->encode((string)$item->getItemId()),
                'eligible_for_return' => false
            ];
        }

        self::assertEqualsCanonicalizing(
            $expectedResult,
            $response['customer']['orders']['items'][0]['items']
        );
    }

    /**
     * Test eligible items for return with not existing order number
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
    ]
    public function testWithNotExistingOrderNumber()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $order = DataFixtureStorageManager::getStorage()->get('order');
        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$order->getIncrementId()}1"}}) {
      items {
        items {
          id
          eligible_for_return
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
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail(), 'password')
        );

        self::assertEmpty($response['customer']['orders']['items']);
    }

    /**
     * Test eligible items for return with unauthorized customer
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
    ]
    public function testUnauthorized()
    {
        $order = DataFixtureStorageManager::getStorage()->get('order');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$order->getIncrementId()}"}}) {
      items {
        items {
          id
          eligible_for_return
        }
      }
    }
  }
}
QUERY;
        $this->graphQlQuery($query);
    }
}
