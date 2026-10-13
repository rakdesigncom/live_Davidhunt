<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Notification Bar for Magento 2
 */

namespace Fyb\NotificationBar\Block;

use Magento\Backend\Block\AbstractBlock;

class Init extends AbstractBlock
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $page = $objectManager->get(\Magento\Framework\View\Page\Config::class);
        $page->addPageAsset('Fyb_NotificationBar::css/styles.css');
    }
}
