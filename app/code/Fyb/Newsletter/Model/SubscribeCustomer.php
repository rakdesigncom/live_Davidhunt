<?php

namespace Fyb\Newsletter\Model;

use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Newsletter\Model\SubscriberFactory;

class SubscribeCustomer
{
    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Newsletter\Model\SubscriptionManagerInterface
     */
    protected $subscriptionManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\Validator\EmailAddress
     */
    protected $emailValidator;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $_customerUrl;

    /**
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement
     * @param \Magento\Newsletter\Model\SubscriptionManagerInterface $subscriptionManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Validator\EmailAddress $emailValidator
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Url $customerUrl
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        StoreManagerInterface $storeManager,
        CustomerAccountManagement $customerAccountManagement,
        SubscriptionManagerInterface $subscriptionManager,
        CustomerRepositoryInterface $customerRepository,
        EmailValidator $emailValidator,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        CustomerUrl $customerUrl,
    ) {
        $this->_subscriberFactory = $subscriberFactory;
        $this->_storeManager = $storeManager;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->subscriptionManager = $subscriptionManager;
        $this->customerRepository = $customerRepository;
        $this->emailValidator = $emailValidator;
        $this->_customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->_customerUrl = $customerUrl;
    }

    /**
     * @param string $email
     *
     * @return \Magento\Newsletter\Model\Subscriber
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function subscribe($email)
    {
        $this->validateEmailFormat($email);
        $this->validateGuestSubscription();
        $this->validateEmailAvailable($email);

        $websiteId = (int)$this->_storeManager->getStore()->getWebsiteId();
        /** @var Subscriber $subscriber */
        $subscriber = $this->_subscriberFactory->create()->loadBySubscriberEmail($email, $websiteId);
        if ($subscriber->getId()
            && (int)$subscriber->getSubscriberStatus() === Subscriber::STATUS_SUBSCRIBED) {
            throw new LocalizedException(
                __('This email address is already subscribed.')
            );
        }

        $storeId = (int)$this->_storeManager->getStore()->getId();
        $currentCustomerId = $this->getCustomerId($email, $websiteId);

        $subscriber = $currentCustomerId
            ? $this->subscriptionManager->subscribeCustomer($currentCustomerId, $storeId)
            : $this->subscriptionManager->subscribe($email, $storeId);

        return $subscriber;
    }

    /**
     * Validates that the email address isn't being used by a different account.
     *
     * @param string $email
     *
     * @return void
     * @throws LocalizedException
     */
    protected function validateEmailAvailable($email)
    {
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        if ($this->_customerSession->isLoggedIn()
            && ($this->_customerSession->getCustomerDataObject()->getEmail() !== $email
                && !$this->customerAccountManagement->isEmailAvailable($email, $websiteId))
        ) {
            throw new LocalizedException(
                __('This email address is already assigned to another user.')
            );
        }
    }

    /**
     * Validates the format of the email address
     *
     * @param string $email
     *
     * @return void
     * @throws LocalizedException
     */
    protected function validateEmailFormat($email)
    {
        if (!$this->emailValidator->isValid($email)) {
            throw new LocalizedException(__('Please enter a valid email address.'));
        }
    }

    /**
     * Validates that if the current user is a guest, that they can subscribe to a newsletter.
     *
     * @return void
     * @throws LocalizedException
     */
    protected function validateGuestSubscription()
    {
        if ($this->scopeConfig->getValue(
                Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG,
                ScopeInterface::SCOPE_STORE
            ) != 1
            && !$this->_customerSession->isLoggedIn()
        ) {
            throw new LocalizedException(
                __(
                    'Sorry, but the administrator denied subscription for guests. Please <a href="%1">register</a>.',
                    $this->_customerUrl->getRegisterUrl()
                )
            );
        }
    }

    /**
     * Check if customer with provided email exists and return its id
     *
     * @param string $email
     * @param int $websiteId
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCustomerId(string $email, int $websiteId): ?int
    {
        try {
            $customer = $this->customerRepository->get($email, $websiteId);

            return (int)$customer->getId();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get success message
     *
     * @param int $status
     * @return Phrase
     */
    public function getSuccessMessage(int $status): Phrase
    {
        if ($status === Subscriber::STATUS_NOT_ACTIVE) {
            return __('The confirmation request has been sent.');
        }

        return __('Thank you for your subscription.');
    }
}
