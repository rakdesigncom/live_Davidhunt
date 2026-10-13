<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Exception\LocalizedException;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Save Reward Point Observer on customer_save_after event
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveRewardPoints implements ObserverInterface
{
    /**
     * Customer converter
     *
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var RewardFactory
     */
    protected $_rewardFactory;

    /**
     * Core model store manager interface
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Reward helper
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData;

    /**
     * Authoriztion interface
     *
     * @var AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param StoreManagerInterface $storeManager
     * @param RewardFactory $rewardFactory
     * @param CustomerRegistry $customerRegistry
     * @param AuthorizationInterface|null $authorization
     */
    public function __construct(
        \Magento\Reward\Helper\Data $rewardData,
        StoreManagerInterface $storeManager,
        RewardFactory $rewardFactory,
        CustomerRegistry $customerRegistry,
        AuthorizationInterface $authorization = null
    ) {
        $this->_rewardData = $rewardData;
        $this->_storeManager = $storeManager;
        $this->_rewardFactory = $rewardFactory;
        $this->customerRegistry = $customerRegistry;
        $this->_authorization = $authorization ?? ObjectManager::getInstance()->get(AuthorizationInterface::class);
    }

    /**
     * Update reward points for customer, send notification
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_rewardData->isEnabled() ||
            !$this->_authorization->isAllowed(\Magento\Reward\Helper\Data::XML_PATH_PERMISSION_BALANCE)
        ) {
            return $this;
        }

        $request = $observer->getEvent()->getRequest();
        $data = $request->getPost('reward');

        if ($data && !empty($data['points_delta'])) {
            $this->validatePointsDelta((string)$data['points_delta']);
            /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
            $customer = $observer->getEvent()->getCustomer();

            if (!isset($data['store_id'])) {
                if ($customer->getStoreId() == 0) {
                    $defaultStore = $this->_storeManager->getDefaultStoreView();
                    if (!$defaultStore) {
                        $allStores = $this->_storeManager->getStores();
                        if (isset($allStores[0])) {
                            $defaultStore = $allStores[0];
                        }
                    }
                    $data['store_id'] = $defaultStore ? $defaultStore->getStoreId() : null;
                } else {
                    $data['store_id'] = $customer->getStoreId();
                }
            }
            $customerModel = $this->customerRegistry->retrieve($customer->getId());
            /** @var $reward \Magento\Reward\Model\Reward */
            $reward = $this->_rewardFactory->create();
            $reward->setCustomer($customerModel)
                ->setWebsiteId($this->_storeManager->getStore($data['store_id'])->getWebsiteId())
                ->loadByCustomer();

            $reward->addData($data);
            $reward->setAction(\Magento\Reward\Model\Reward::REWARD_ACTION_ADMIN)
                ->setActionEntity($customerModel)
                ->updateRewardPoints();
        }

        return $this;
    }

    /**
     * Validates reward points delta value.
     *
     * @param string $pointsDelta
     * @return void
     * @throws LocalizedException
     */
    private function validatePointsDelta(string $pointsDelta): void
    {
        if (filter_var($pointsDelta, FILTER_VALIDATE_INT) === false) {
            throw new LocalizedException(__('Reward points should be a valid integer number.'));
        }
    }
}
