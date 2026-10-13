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
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\Sales\Test\Fixture\Shipment as ShipmentFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Rma\Test\Fixture\Rma as RmaFixture;
use Magento\Rma\Test\Fixture\RmaItem as RmaItemFixture;
use Magento\Rma\Test\Fixture\RmaShipping as RmaShippingFixture;

/**
 * Test for removing return tracking
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RemoveReturnTrackingTest extends GraphQlAbstract
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
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

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
     * Test tracking removing by unauthorized customer
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testUnauthorized()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The current customer isn\'t authorized.');

        $rma = $this->getCustomerReturn('customer_uk_address@test.com');
        $tracks = $rma->getTracks();
        $trackUid = $this->idEncoder->encode((string)current($tracks)->getEntityId());

        $mutation = <<<MUTATION
mutation {
  removeReturnTracking(
    input: {
      return_shipping_tracking_uid: "{$trackUid}"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation($mutation);
    }

    /**
     * Test tracking removing when RMA is disabled
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
        DataFixture(
            RmaItemFixture::class,
            [
                'sku' => '$product.sku$',
                'name' => '$product.name$',
                'rma_entity_id' => '$rma.id$',
                'id' => '$rma.id$'
            ],
            'rma_item'
        ),
        DataFixture(
            RmaShippingFixture::class,
            [
                'rma_entity_id' => '$rma.id$',
            ],
            'rma_shipping'
        ),
    ]
    public function testWithDisabledRma()
    {
        $customer = DataFixtureStorageManager::getStorage()->get('customer');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('RMA is disabled.');

        $customerEmail = $customer->getEmail();
        $rma = $this->getCustomerReturn($customerEmail);
        $tracks = $rma->getTracks();
        $trackUid = $this->idEncoder->encode((string)current($tracks)->getEntityId());

        $mutation = <<<MUTATION
mutation {
  removeReturnTracking(
    input: {
      return_shipping_tracking_uid: "{$trackUid}"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test tracking removing with wrong uid
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testWithWrongId()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You selected the wrong RMA.');

        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $tracks = $rma->getTracks();
        $trackId = current($tracks)->getEntityId() + 10;
        $trackUid = $this->idEncoder->encode((string)$trackId);

        $mutation = <<<MUTATION
mutation {
  removeReturnTracking(
    input: {
      return_shipping_tracking_uid: "{$trackUid}"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test tracking removing with not encoded uid
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testWithNotEncodedId()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $tracks = $rma->getTracks();
        $trackId = current($tracks)->getEntityId();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Value of uid \"{$trackId}\" is incorrect");

        $mutation = <<<MUTATION
mutation {
  removeReturnTracking(
    input: {
      return_shipping_tracking_uid: "{$trackId}"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );
    }

    /**
     * Test tracking removing
     *
     * @magentoConfigFixture sales/magento_rma/enabled 1
     * @magentoConfigFixture sales/magento_rma/use_store_address 1
     * @magentoConfigFixture shipping/origin/name test
     * @magentoConfigFixture shipping/origin/phone +380003434343
     * @magentoConfigFixture shipping/origin/street_line1 street
     * @magentoConfigFixture shipping/origin/street_line2 1
     * @magentoConfigFixture shipping/origin/city Montgomery
     * @magentoConfigFixture shipping/origin/region_id 1
     * @magentoConfigFixture shipping/origin/postcode 12345
     * @magentoConfigFixture shipping/origin/country_id US
     * @magentoApiDataFixture Magento/Rma/_files/rma_with_customer.php
     */
    public function testRemoveTracking()
    {
        $customerEmail = 'customer_uk_address@test.com';
        $rma = $this->getCustomerReturn($customerEmail);
        $tracks = $rma->getTracks();
        $trackUid = $this->idEncoder->encode((string)current($tracks)->getEntityId());

        $mutation = <<<MUTATION
mutation {
  removeReturnTracking(
    input: {
      return_shipping_tracking_uid: "{$trackUid}"
    }
  ) {
    return {
      shipping {
        tracking {
          uid
          carrier {
            label
          }
          tracking_number
        }
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlMutation(
            $mutation,
            [],
            '',
            $this->getCustomerAuthenticationHeader->execute($customerEmail, 'password')
        );

        self::assertEmpty($response['removeReturnTracking']['return']['shipping']['tracking']);
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
