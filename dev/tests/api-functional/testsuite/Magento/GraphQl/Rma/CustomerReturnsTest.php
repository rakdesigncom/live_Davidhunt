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
use Magento\GraphQl\GetCustomerAuthenticationHeader;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Test\Fixture\Rma as RmaFixture;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\Sales\Test\Fixture\Shipment as ShipmentFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for customer returns query
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerReturnsTest extends GraphQlAbstract
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var GetCustomerAuthenticationHeader
     */
    private $getCustomerAuthenticationHeader;

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
        $this->getCustomerAuthenticationHeader = $this->objectManager->get(GetCustomerAuthenticationHeader::class);
        $this->customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->rmaRepository = $this->objectManager->get(RmaRepositoryInterface::class);
        $this->idEncoder = $this->objectManager->get(Uid::class);
    }

    /**
     * Test customer returns
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
    public function testCustomerReturnsQuery()
    {
        $customerEmail = DataFixtureStorageManager::getStorage()->get('customer')->getEmail();
        $pageSize = 10;
        $currentPage = 1;

        $query = <<<QUERY
{
  customer {
    returns(pageSize: {$pageSize}, currentPage: {$currentPage}) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
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
QUERY;

        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        $customer = $this->customerRepository->get($customerEmail);
        $rma = $this->getCustomerReturn($customerEmail);

        self::assertEquals($rma->getDateRequested(), $response['customer']['returns']['items'][0]['created_at']);
        self::assertEquals($rma->getIncrementId(), $response['customer']['returns']['items'][0]['number']);
        self::assertEqualsIgnoringCase(
            Status::STATE_APPROVED,
            $response['customer']['returns']['items'][0]['status']
        );
        self::assertEquals(
            $customer->getFirstname(),
            $response['customer']['returns']['items'][0]['customer']['firstname']
        );
        self::assertEquals(
            $customer->getLastname(),
            $response['customer']['returns']['items'][0]['customer']['lastname']
        );
        self::assertEquals(
            $customerEmail,
            $response['customer']['returns']['items'][0]['customer']['email']
        );
        self::assertEquals(
            $this->idEncoder->encode((string)$rma->getEntityId()),
            $response['customer']['returns']['items'][0]['uid']
        );
        self::assertEquals($currentPage, $response['customer']['returns']['page_info']['current_page']);
        self::assertEquals($pageSize, $response['customer']['returns']['page_info']['page_size']);
        self::assertEquals(1, $response['customer']['returns']['page_info']['total_pages']);
        self::assertEquals(1, $response['customer']['returns']['total_count']);
    }

    /**
     * Test customer returns with negative page size value
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
    public function testCustomerReturnsQueryWithNegativePageSize()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pageSize value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: -5, currentPage: 2) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
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
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail(), 'password')
        );
    }

    /**
     * Test customer returns with zero page size value
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
    public function testCustomerReturnsQueryWithZeroPageSize()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('pageSize value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: 0, currentPage: 1) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
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
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail(), 'password')
        );
    }

    /**
     * Test customer returns with zero current page value
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
    public function testCustomerReturnsQueryWithZeroCurrentPage()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('currentPage value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: 10, currentPage: 0) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
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
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail(), 'password')
        );
    }

    /**
     * Test customer returns with negative current page value
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
    public function testCustomerReturnsQueryWithNegativeCurrentPage()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('currentPage value must be greater than 0.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: 10, currentPage: -1) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
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
    public function testCustomerReturnsQueryWithUnauthorizedCustomer()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: 10, currentPage: 1) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
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
QUERY;

        $this->graphQlQuery($query);
    }

    /**
     * Test customer returns query without params
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
    public function testCustomerReturnsQueryWithoutParams()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $customerEmail = $customer->getEmail();
        $query = <<<QUERY
{
  customer {
    returns {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
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
QUERY;

        $response = $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        $customer = $this->customerRepository->get($customerEmail);
        $rma = $this->getCustomerReturn($customerEmail);

        self::assertEquals($rma->getDateRequested(), $response['customer']['returns']['items'][0]['created_at']);
        self::assertEquals($rma->getIncrementId(), $response['customer']['returns']['items'][0]['number']);
        self::assertEqualsIgnoringCase(
            Status::STATE_APPROVED,
            $response['customer']['returns']['items'][0]['status']
        );
        self::assertEquals(
            $customer->getFirstname(),
            $response['customer']['returns']['items'][0]['customer']['firstname']
        );
        self::assertEquals(
            $customer->getLastname(),
            $response['customer']['returns']['items'][0]['customer']['lastname']
        );
        self::assertEquals(
            $customerEmail,
            $response['customer']['returns']['items'][0]['customer']['email']
        );
        self::assertEquals(
            $this->idEncoder->encode((string)$rma->getEntityId()),
            $response['customer']['returns']['items'][0]['uid']
        );
        self::assertEquals(1, $response['customer']['returns']['page_info']['current_page']);
        self::assertEquals(20, $response['customer']['returns']['page_info']['page_size']);
        self::assertEquals(1, $response['customer']['returns']['page_info']['total_pages']);
        self::assertEquals(1, $response['customer']['returns']['total_count']);
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
        $customer = DataFixtureStorageManager::getStorage()->get('customer');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('RMA is disabled.');

        $query = <<<QUERY
{
  customer {
    returns(pageSize: 10, currentPage: 0) {
      items {
        uid
        number
        created_at
        customer{firstname lastname email}
        status
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
QUERY;

        $this->graphQlQuery(
            $query,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customer->getEmail())
        );
    }

    /**
     * Get customer return
     *
     * @param string $customerEmail
     * @return RmaInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerReturn(string $customerEmail): RmaInterface
    {
        $customer = $this->customerRepository->get($customerEmail);
        $this->searchCriteriaBuilder->addFilter(Rma::CUSTOMER_ID, $customer->getId());
        $searchResults = $this->rmaRepository->getList($this->searchCriteriaBuilder->create());

        return $searchResults->getFirstItem();
    }
}
