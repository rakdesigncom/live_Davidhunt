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
use Onestepcheckout\Iosc\Helper\Data;

class SidebarProcessor implements LayoutProcessorInterface
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
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function process($jsLayout)
    {

        $layoutPath = $jsLayout['components']['checkout']
                        ['children']['sidebar']
                        ['children'] ?? false;
        if ($this->helper->isEnabled() && $layoutPath) {

            $configKey = 'iosc-summary';
            $lastComponent = end($layoutPath)['component'];
            $componentCount = count($layoutPath);
            $component = [
                'component' => 'Onestepcheckout_Iosc/js/totals',
                'displayArea' => 'sidebar',
                'config' => [
                    'template' => 'Onestepcheckout_Iosc/summary',
                    'iosc_cnf' => [
                        'lastComponent' => $lastComponent,
                        'componentCount' => $componentCount
                    ]
                ]
            ];
            $sidebar = $jsLayout['components']['checkout']
                        ['children']['sidebar'];
            unset(
                $jsLayout['components']['checkout']
                    ['children']['sidebar']
            );
            $jsLayout['components']['checkout']
                ['children'][$configKey] = $component;
            $jsLayout['components']['checkout']
                ['children']['sidebar'] = $sidebar;
        }

        return $jsLayout;
    }
}
