<?php
/**
 * OneStepCheckout
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to One Step Checkout AS software license.
 *
 * License is available through the world-wide-web at this URL:
 * https://www.onestepcheckout.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to mail@onestepcheckout.com so we can send you a copy immediately.
 *
 * @category   onestepcheckout
 * @package    onestepcheckout_iosc
 * @copyright  Copyright (c) 2017 OneStepCheckout  (https://www.onestepcheckout.com/)
 * @license    https://www.onestepcheckout.com/LICENSE.txt
 */
namespace Onestepcheckout\Iosc\Observer\Frontend\Registration;

use Exception;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Validator\Name;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Onestepcheckout\Iosc\Helper\Data;
use Psr\Log\LoggerInterface;

class CheckoutSubmitAfter implements ObserverInterface
{

    public const PATTERN_NAME = '/([^\p{L}\p{M}\s\d,\-_\'’`]+)/ui';
    public const PATTERN_TARGET_FIELDS = ['firstname', 'lastname', 'middlename'];
    public const MOCK_VALUE = '-';
    /**
     * @var Copy
     */
    protected $objectCopyService;
    /**
     * @var EncryptorInterface
     */
    protected $encryptorInterface;
    /**
     * @var Random
     */
    protected $random;
    /**
     * @var SimpleDataObjectConverter
     */
    protected $simpleDataObjectConverter;
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;
    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerFactory;
    /**
     * @var AddressInterfaceFactory
     */
    protected $addressFactory;
    /**
     * @var RegionInterfaceFactory
     */
    protected $regionFactory;
    /**
     * @var AccountManagementInterface
     */
    protected $accountManagementInterface;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Copy $objectCopyService
     * @param EncryptorInterface $encryptorInterface
     * @param Random $random
     * @param SimpleDataObjectConverter $simpleDataObjectConverter
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CustomerInterfaceFactory $customerFactory
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionInterfaceFactory $regionFactory
     * @param AccountManagementInterface $accountManagementInterface
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Copy $objectCopyService,
        EncryptorInterface $encryptorInterface,
        Random $random,
        SimpleDataObjectConverter $simpleDataObjectConverter,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepositoryInterface,
        CustomerInterfaceFactory $customerFactory,
        AddressInterfaceFactory $addressFactory,
        RegionInterfaceFactory $regionFactory,
        AccountManagementInterface $accountManagementInterface,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        Data $helper
    ) {

        $this->scopeConfig = $scopeConfig;

        $this->objectCopyService = $objectCopyService;
        $this->encryptorInterface = $encryptorInterface;
        $this->random = $random;
        $this->simpleDataObjectConverter = $simpleDataObjectConverter;

        $this->customerSession = $customerSession;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        $this->accountManagementInterface = $accountManagementInterface;

        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    /**
     * Handle post order registration and order tieing to guest customer
     *
     * @param Observer $observer
     * @event checkout_submit_after
     *
     * @return false|void
     */
    public function execute(
        Observer $observer
    ) {

        if (!$this->helper->isEnabled()) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();
        if (is_object($order) && $order->getStoreId() == "0") {
            return false;
        }

        $customer = false;
        $scopeStore = ScopeInterface::SCOPE_STORE;
        $autoPlace  = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/registration/autoplace', $scopeStore);
        $autoRegister = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/registration/autoregister', $scopeStore);
        $optionalRegister = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/registration/optionalpwd', $scopeStore);
        $requiredRegister = $this->scopeConfig
            ->getValue('onestepcheckout_iosc/registration/requiredpwd', $scopeStore);

        $email = $quote->getCustomerEmail();
        $isLoggedIn = $this->customerSession->getId();
        $isEmailAvailable  = (int)$this->accountManagementInterface
            ->isEmailAvailable($quote->getCustomerEmail());
        $tryAccountCreation = false;
        if (!$isLoggedIn &&
            (
                $autoRegister ||
                (
                    ($optionalRegister || $requiredRegister) &&
                    !empty($quote->getPasswordHash())
                )
            ) &&
            $isEmailAvailable) {
            $customer = $this->customerFactory
                        ->create(['data' => $this->prepcustomerData($quote)]);

            $customer
                ->setWebsiteId($this->storeManager->getWebsite()->getId())
                ->setStoreId($this->storeManager->getStore()->getId());

            $quote->getShippingAddress()->setEmail($email);
            if (($optionalRegister || $requiredRegister) && !empty($quote->getPasswordHash())) {
                $pwordH = $quote->getPasswordHash();
                $autoPlace = true;
                $quote->setIoscRegistered(1);
            } else {
                $pword  = $this->random->getRandomString(8);
                $pwordH = $this->encryptorInterface->getHash($pword, true);
                $quote->setIoscRegistered(2);
            }

            try {
                $customerA = $this->accountManagementInterface
                                ->createAccountWithPasswordHash($customer, $pwordH);
                $tryAccountCreation = true;
                if (($optionalRegister || $requiredRegister) && !empty($quote->getPasswordHash())) {
                    $quote->setPasswordHash("");
                }
            } catch (Exception $e) {
                $this->logger->critical($e);
            }

            if ($tryAccountCreation) {
                $customer = $customerA;
            }

            $isEmailAvailable = 0;
        }
        if (!$isLoggedIn && ($autoPlace || $requiredRegister) && !$isEmailAvailable) {
            if (!is_object($customer)) {
                $customer = $this->customerRepositoryInterface
                    ->get($email, $this->storeManager->getWebsite()->getId());
            }

            if (!empty($order) && is_object($order) && is_object($customer)) {
                $this->addUserToOrder($quote, $order, $customer);
                if ($order->getCustomerId() && $order->getCustomerIsGuest()) {
                    $order->setCustomerIsGuest(0);
                }

                $order->save();
                $quote->save();
            }
        }
    }

    /**
     * Add user to order
     *
     * @param $quote
     * @param $order
     * @param $customer
     */
    private function addUserToOrder(
        $quote,
        $order,
        $customer
    ) {
        $orderCustomerAttr = array_keys($order->getData());
        foreach ($orderCustomerAttr as $k) {
            if (strpos($k, 'customer_') !== false) {
                $k = str_replace('customer_', '', $k);

                $convertedKey = $this->simpleDataObjectConverter
                                    ->snakeCaseToUpperCamelCase($k);
                $setMethodName = 'setCustomer' . $convertedKey;
                $getMethodName = 'get' . $convertedKey;

                if (method_exists($customer, $getMethodName)) {
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    $v = call_user_func([
                        $customer,
                        $getMethodName
                    ]);

                    if ($v) {
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        call_user_func([
                            $order,
                            $setMethodName
                        ], $v);
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        call_user_func([
                            $quote,
                            $setMethodName
                        ], $v);
                    }
                }
            }
        }
    }

    /**
     * Prepare Customer Data
     *
     * @param object $salesObj
     * @return CustomerInterfaceFactory object
     */
    private function prepcustomerData($salesObj)
    {

        $customerData = $this->objectCopyService->copyFieldsetToTarget(
            'order_address',
            'to_customer',
            $salesObj->getBillingAddress(),
            []
        );

        if ($salesObj->getCustomerGender() && !isset($customerData['gender'])) {
            $customerData['gender'] = $salesObj->getCustomerGender();
        }

        if ($salesObj->getCustomerDob() && !isset($customerData['dob'])) {
            $customerData['dob'] = $salesObj->getCustomerDob();
        }

        if (!$salesObj->getIsVirtual()) {
            $addresses = [$salesObj->getBillingAddress(), $salesObj->getShippingAddress()];
        } else {
            $addresses = [$salesObj->getBillingAddress()];
        }
        if (class_exists(Name::class)) {
            foreach (self::PATTERN_TARGET_FIELDS as $name) {
                if (isset($customerData[$name]) && !empty($customerData[$name])) {
                    $customerData[$name] = preg_replace(self::PATTERN_NAME, self::MOCK_VALUE, $customerData[$name]);
                }
            }
        }

        foreach ($addresses as $address) {
            $addressData = $this->objectCopyService->copyFieldsetToTarget(
                'order_address',
                'to_customer_address',
                $address,
                []
            );
            /** @var AddressInterface $customerAddress */
            $customerAddress = $this->addressFactory->create(['data' => $addressData]);
            switch ($address->getAddressType()) {
                case Address::ADDRESS_TYPE_BILLING:
                    $customerAddress->setIsDefaultBilling(true);
                    break;
                case Address::ADDRESS_TYPE_SHIPPING:
                    $customerAddress->setIsDefaultShipping(true);
                    break;
            }

            if (is_string($address->getRegion())) {
                /** @var RegionInterface $region */
                $region = $this->regionFactory->create();
                $region->setRegion($address->getRegion());
                $region->setRegionCode($address->getRegionCode());
                $region->setRegionId($address->getRegionId());
                $customerAddress->setRegion($region);
            }
            $customerData['addresses'][] = $customerAddress;
        }

        return $customerData;
    }
}
