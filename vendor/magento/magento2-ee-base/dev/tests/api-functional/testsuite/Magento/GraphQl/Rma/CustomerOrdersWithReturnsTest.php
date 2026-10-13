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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Test\Fixture\Rma as RmaFixture;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\Sales\Test\Fixture\Shipment as ShipmentFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for customer orders query with returns
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerOrdersWithReturnsTest extends GraphQlAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

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
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->orderFactory = $this->objectManager->get(OrderInterfaceFactory::class);
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->rmaRepository = $this->objectManager->get(RmaRepositoryInterface::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
    }

    /**
     * Test customer order returns
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 1),
        Config("shipping/origin/name", "test"),
        Config("shipping/origin/phone", +380003434343),
        Config("shipping/origin/street_line1", "street"),
        Config("shipping/origin/street_line2", "1"),
        Config("shipping/origin/city", "Montgomery"),
        Config("shipping/origin/region_id", 1),
        Config("shipping/origin/postcode", 12345),
        Config("shipping/origin/country_id", "US"),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testCustomerOrdersWithReturnsQuery()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $pageSize = 10;
        $currentPage = 1;
        $orderIncrementId = DataFixtureStorageManager::getStorage()->get('order')->getIncrementId();

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderIncrementId}"}}) {
      items {
        returns(pageSize: {$pageSize}, currentPage: {$currentPage}) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
          }
          page_info {
            current_page
            page_size
            total_pages
          }
          total_count
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

        $customer = $this->customerRepository->get($customerEmail);
        $rma = $this->getCustomerReturnByOrder($customerEmail, $orderIncrementId);

        self::assertEquals(
            $rma->getDateRequested(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['created_at']
        );
        self::assertEquals(
            $rma->getIncrementId(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['number']
        );
        self::assertEqualsIgnoringCase(
            Status::STATE_APPROVED,
            $response['customer']['orders']['items'][0]['returns']['items'][0]['status']
        );
        self::assertEquals(
            $customer->getFirstname(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['firstname']
        );
        self::assertEquals(
            $customer->getLastname(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['lastname']
        );
        self::assertEquals(
            $customer->getEmail(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['email']
        );
        self::assertEquals(
            $this->idEncoder->encode((string)$rma->getEntityId()),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['uid']
        );
        self::assertEquals(
            $currentPage,
            $response['customer']['orders']['items'][0]['returns']['page_info']['current_page']
        );
        self::assertEquals(
            $pageSize,
            $response['customer']['orders']['items'][0]['returns']['page_info']['page_size']
        );
        self::assertEquals(
            1,
            $response['customer']['orders']['items'][0]['returns']['page_info']['total_pages']
        );
        self::assertEquals(
            1,
            $response['customer']['orders']['items'][0]['returns']['total_count']
        );
    }

    /**
     * Test customer order returns with negative page size value
     */
    #[
        Config("sales/magento_rma/enabled", 1),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testCustomerOrdersWithReturnsQueryWithNegativePageSize()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $order = DataFixtureStorageManager::getStorage()->get('order');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pageSize value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$order->getIncrementId()}"}}) {
      items {
        returns(pageSize: -1, currentPage: 1) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
          }
          page_info {
            current_page
            page_size
            total_pages
          }
          total_count
        }
      }
    }
  }
}
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail(), 'password')
        );
    }

    /**
     * Test customer order returns with zero page size value
     */
    #[
        Config("sales/magento_rma/enabled", 1),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testCustomerOrdersWithReturnsQueryWithZeroPageSize()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $order = DataFixtureStorageManager::getStorage()->get('order');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pageSize value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$order->getIncrementId()}"}}) {
      items {
        returns(pageSize: 0, currentPage: 1) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
          }
          page_info {
            current_page
            page_size
            total_pages
          }
          total_count
        }
      }
    }
  }
}
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail(), 'password')
        );
    }

    /**
     * Test customer order returns with zero current page value
     */
    #[
        Config("sales/magento_rma/enabled", 1),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testCustomerOrdersWithReturnsQueryWithZeroCurrentPage()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $order = DataFixtureStorageManager::getStorage()->get('order');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('currentPage value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$order->getIncrementId()}"}}) {
      items {
        returns(pageSize: 10, currentPage: 0) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
          }
          page_info {
            current_page
            page_size
            total_pages
          }
          total_count
        }
      }
    }
  }
}
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail(), 'password')
        );
    }

    /**
     * Test customer order returns with negative current page value
     */
    #[
        Config("sales/magento_rma/enabled", 1),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testCustomerOrdersWithReturnsQueryWithNegativeCurrentPage()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $order = DataFixtureStorageManager::getStorage()->get('order');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('currentPage value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$order->getIncrementId()}"}}) {
      items {
        returns(pageSize: 10, currentPage: -1) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
          }
          page_info {
            current_page
            page_size
            total_pages
          }
          total_count
        }
      }
    }
  }
}
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail(), 'password')
        );
    }

    /**
     * Test customer returns query with unauthorized customer
     */
    #[
        Config("sales/magento_rma/enabled", 1),
    ]
    public function testCustomerOrdersWithReturnsQueryWithUnauthorizedCustomer()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "100000555"}}) {
      items {
        returns(pageSize: 10, currentPage: 1) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
          }
          page_info {
            current_page
            page_size
            total_pages
          }
          total_count
        }
      }
    }
  }
}
QUERY;

        $this->graphQlQuery($query);
    }

    /**
     * Test customer order returns without params
     */
    #[
        Config("sales/magento_rma/enabled", 1),
        Config("sales/magento_rma/use_store_address", 1),
        Config("shipping/origin/name", "test"),
        Config("shipping/origin/phone", +380003434343),
        Config("shipping/origin/street_line1", "street"),
        Config("shipping/origin/street_line2", "1"),
        Config("shipping/origin/city", "Montgomery"),
        Config("shipping/origin/region_id", 1),
        Config("shipping/origin/postcode", 12345),
        Config("shipping/origin/country_id", "US"),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testCustomerOrdersWithReturnsQueryWithoutParams()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $order = DataFixtureStorageManager::getStorage()->get('order');
        $customerEmail = $customer->getEmail();
        $orderIncrementId = $order->getIncrementId();

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$orderIncrementId}"}}) {
      items {
        returns {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
          }
          page_info {
            current_page
            page_size
            total_pages
          }
          total_count
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

        $customer = $this->customerRepository->get($customerEmail);
        $rma = $this->getCustomerReturnByOrder($customerEmail, $orderIncrementId);

        self::assertEquals(
            $rma->getDateRequested(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['created_at']
        );
        self::assertEquals(
            $rma->getIncrementId(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['number']
        );
        self::assertEqualsIgnoringCase(
            Status::STATE_APPROVED,
            $response['customer']['orders']['items'][0]['returns']['items'][0]['status']
        );
        self::assertEquals(
            $customer->getFirstname(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['firstname']
        );
        self::assertEquals(
            $customer->getLastname(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['lastname']
        );
        self::assertEquals(
            $customer->getEmail(),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['customer']['email']
        );
        self::assertEquals(
            $this->idEncoder->encode((string)$rma->getEntityId()),
            $response['customer']['orders']['items'][0]['returns']['items'][0]['uid']
        );
        self::assertEquals(
            1,
            $response['customer']['orders']['items'][0]['returns']['page_info']['current_page']
        );
        self::assertEquals(
            20,
            $response['customer']['orders']['items'][0]['returns']['page_info']['page_size']
        );
        self::assertEquals(
            1,
            $response['customer']['orders']['items'][0]['returns']['page_info']['total_pages']
        );
        self::assertEquals(
            1,
            $response['customer']['orders']['items'][0]['returns']['total_count']
        );
    }

    /**
     * Test customer returns query with disabled RMA
     */
    #[
        Config("sales/magento_rma/enabled", 0),
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
                'items' => [['product_id' => '$product.id$']]
            ]
        ),
        DataFixture(RmaFixture::class, ['order_id' => '$order.id$'], 'rma'),
    ]
    public function testCustomerReturnsQueryWithDisabledRma()
    {
        $order = DataFixtureStorageManager::getStorage()->get('order');
        $customer = DataFixtureStorageManager::getStorage()->get('customer');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('RMA is disabled.');

        $query = <<<QUERY
{
  customer {
    orders(filter: {number: {eq: "{$order->getIncrementId()}"}}) {
      items {
        returns(pageSize: 10, currentPage: 1) {
          items {
            uid
            created_at
            customer{firstname lastname email}
            status
            number
          }
          page_info {
            current_page
            page_size
            total_pages
          }
          total_count
        }
      }
    }
  }
}
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail())
        );
    }

    /**
     * Get customer return by order
     *
     * @param string $customerEmail
     * @param string $incrementId
     * @return RmaInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerReturnByOrder(string $customerEmail, string $incrementId): RmaInterface
    {
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        $customer = $this->customerRepository->get($customerEmail);

        $this->searchCriteriaBuilder->addFilter(Rma::CUSTOMER_ID, $customer->getId());
        $this->searchCriteriaBuilder->addFilter(Rma::ORDER_ID, $order->getEntityId());
        $searchResults = $this->rmaRepository->getList($this->searchCriteriaBuilder->create());

        return $searchResults->getFirstItem();
    }
}
