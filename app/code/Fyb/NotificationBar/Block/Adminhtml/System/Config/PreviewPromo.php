<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Notification Bar for Magento 2
 */

namespace Fyb\NotificationBar\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class PreviewPromo extends Template implements RendererInterface
{
    /**
     * @inheritdoc
     */
    protected $_template = 'Fyb_NotificationBar::system/config/preview.phtml';

    /**
     * @inheritdoc
     */
    public function render(AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCssAdditional()
    {
        $css = $this->_assetRepo->createAsset(
            'Fyb_NotificationBar::css/styles.css',
            ['area' => 'frontend']
        )->getContent();

        return str_replace(["\r", "\r"], ' ', $css);
    }
}
