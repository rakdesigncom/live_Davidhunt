<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Plumrocket\LayeredNavigationLite\Helper\Config;

/**
 * @since 1.0.0
 */
class AddProductFilterInitHandle implements ObserverInterface
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Changing attribute values
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isModuleEnabled()) {
            /** @var \Magento\Framework\View\Layout $layout */
            $layout = $observer->getLayout();
            if (array_intersect($layout->getUpdate()->getHandles(), $this->config->getAllowedHandles())) {
                $layout->getUpdate()->addHandle('product_filter_init');
            }
        }
    }
}
