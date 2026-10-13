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
namespace Onestepcheckout\Iosc\Block\Frontend\LayoutProcessors;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\ScopeInterface;
use Onestepcheckout\Iosc\Helper\Data;

class SubscribeProcessor implements LayoutProcessorInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var Subscriber
     */
    protected $subscriber;
    /**
     * @var Data
     */
    protected $helper;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $customerSession
     * @param Subscriber $subscriber
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $customerSession,
        Subscriber $subscriber,
        Data $helper
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->subscriber = $subscriber;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function process($jsLayout)
    {
        $configKey = 'subscribe';

        $layoutPath = $jsLayout['components']['checkout']
                        ['children']['iosc']
                        ['children'][$configKey] ?? false;

        if ($this->helper->isEnabled() && $layoutPath) {
            unset(
                $jsLayout['components']['checkout']
                ['children']['iosc']
                ['children'][$configKey]
            );

            $include = $this->getIsEnabled($configKey);

            $sidebarPath = $jsLayout['components']['checkout']
                            ['children']['sidebar']
                            ['children'][$configKey] ?? false;

            if ($include) {
                if ($sidebarPath) {
                    $componentConfig = array_merge($sidebarPath, $layoutPath);
                    $jsLayout['components']['checkout']
                        ['children']['sidebar']
                        ['children'][$configKey] = $componentConfig;
                }
            } else {
                if ($sidebarPath) {
                    unset(
                        $jsLayout['components']['checkout']
                        ['children']['sidebar']
                        ['children'][$configKey]
                    );
                }
            }
        }

        return $jsLayout;
    }

    /**
     * Get isEnabled
     *
     * @param $configKey
     * @return bool
     */
    private function getIsEnabled($configKey)
    {

        $include = false;
        $scopeStore = ScopeInterface::SCOPE_STORE;

        $enabled = $this->scopeConfig
            ->getValue(
                'onestepcheckout_iosc/' . $configKey . '/enable',
                $scopeStore
            );

        if ($enabled) {
            $include = true;
        }

        $isLoggedIn = $this->customerSession->getId();
        if ($isLoggedIn) {

            $enabledForReg = $this->scopeConfig
                ->getValue(
                    'onestepcheckout_iosc/' . $configKey . '/enableforreg',
                    $scopeStore
                );

            $hideFromRegandSubscribed = $this->scopeConfig
                ->getValue(
                    'onestepcheckout_iosc/' . $configKey . '/hidefromregandsubscribed',
                    $scopeStore
                );

            if (!$enabledForReg) {
                $include = false;
            }

            if ($enabledForReg && $hideFromRegandSubscribed) {
                $status = $this->subscriber->loadByCustomerId($isLoggedIn);
                if ($status->getStatus()) {
                    $include = false;
                }
            }
        }

        return $include;
    }
}
