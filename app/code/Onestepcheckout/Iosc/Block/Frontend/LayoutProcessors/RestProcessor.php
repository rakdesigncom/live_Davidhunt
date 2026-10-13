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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Onestepcheckout\Iosc\Helper\Data;
use Onestepcheckout\Iosc\Model\Recaptcha\AdapterInterface;

class RestProcessor implements LayoutProcessorInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var AdapterInterface
     */
    protected $reCaptcha;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param AdapterInterface $reCaptcha
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        AdapterInterface $reCaptcha
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->reCaptcha = $reCaptcha;
    }

    /**
     * @inheritdoc
     */
    public function process($jsLayout)
    {
        $configKey = 'rest';
        $layoutPath =   $jsLayout['components']['checkout']
                        ['children']['iosc']
                        ['children'][$configKey] ?? false;

        if ($this->helper->isEnabled() && $layoutPath) {
            unset(
                $jsLayout['components']['checkout']
                ['children']['iosc']
                ['children'][$configKey]
            );

            $scopeStore = ScopeInterface::SCOPE_STORE;
            $configPath = 'onestepcheckout_iosc/' . $configKey . '/recaptcha';

            $sidebarPath =  $jsLayout['components']['checkout']
                            ['children']['sidebar']
                            ['children'][$configKey] ?? false;
            $layoutPath['cnf'] = $this->helper->replaceConfigValues($layoutPath['cnf']);
            $recaptchaEnabled = false;

            if ($this->reCaptcha->isConfigured() &&
                !empty($layoutPath['cnf']['is_recaptcha_rules'])
            ) {
                $recaptchaEnabled =  true;
            }

            if ($recaptchaEnabled) {
                if ($sidebarPath) {
                    $layoutPath['cnf'] = $layoutPath['cnf']['is_recaptcha_rules'];
                    $sidebarPath['config']['reCaptchaId'] = 'recaptcha-iosc-onestepcheckout';
                    $reCaptchaSettings = $this->reCaptcha->getCaptchaSettings();
                    $sidebarPath['settings'] = $reCaptchaSettings;

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
}
