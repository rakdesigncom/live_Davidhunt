<?php
/**
 * @author FYB Romania
 * @copyright Copyright (c) FYB Romania (https://fyb.ro)
 * @package Notification Bar for Magento 2
 */

namespace Fyb\NotificationBar\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Editor extends Field
{
    /**
     * @var WysiwygConfig
     */
    protected $wysiwygConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        WysiwygConfig $wysiwygConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->wysiwygConfig = $wysiwygConfig;
    }

    /**
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setWysiwyg(true);
        $config = $this->wysiwygConfig->getConfig($element);

        $config->setData('add_variables', false)
            ->setData('add_widgets', false)
            ->setData('plugins', [])
            ->setData('add_images', false)
            ->setData('no_display', false)
            ->setData('use_container', false);

        if ($tinyMce = $config->getData('tinymce4')) {
            $tinyMce['toolbar'] = 'styleselect | fontsizeselect | bold italic underline | link charmap';
            $tinyMce['plugins'] = 'advlist autolink link charmap noneditable contextmenu paste code help autoresize';
            $tinyMce['menubar'] = '';
            $config->setData('tinymce4', $tinyMce);
        } else {
            $tinyMce = $config->getData('tinymce');

            $tinyMce['toolbar'] = 'styleselect | fontsizeselect | bold italic underline | link charmap';
            $tinyMce['plugins'] = 'advlist autolink link charmap noneditable paste code help autoresize';
            $tinyMce['menubar'] = '';
            $config->setData('tinymce', $tinyMce);
        }

        $element->setConfig($config);

        return parent::_getElementHtml($element);
    }
}
