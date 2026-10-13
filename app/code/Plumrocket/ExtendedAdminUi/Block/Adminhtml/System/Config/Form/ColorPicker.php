<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license/  End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\ArrayManager;

/**
 * Color picker for system settings
 *
 * For using you have to load Plumrocket_ExtendedAdminUi::css/lib/pickr/monolith.min.css on the page
 *
 * @since 1.0.3
 */
class ColorPicker extends Field
{
    /**
     * @var \Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @param \Magento\Backend\Block\Template\Context          $context
     * @param \Magento\Framework\Stdlib\ArrayManager           $arrayManager
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param array                                            $data
     */
    public function __construct(
        Context $context,
        ArrayManager $arrayManager,
        SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->arrayManager = $arrayManager;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        $element->addClass('pr-color-picker-value');

        $pickerPlaceholderId = $element->getHtmlId() . '_color_picker';
        $pickerContainerHtml = '<div id="' . $pickerPlaceholderId . '"></div>' ;

        /** @var Template $pickerConfigsBlock */
        $pickerConfigsBlock = $this->getLayout()->createBlock(Template::class);
        $pickerConfigsBlock
            ->setTemplate('Plumrocket_ExtendedAdminUi::system/config/color_picker.phtml')
            ->setData('jsonConfig', $this->serializer->serialize($this->getConfig($element, $pickerPlaceholderId)))
            ->setData('elementId', $element->getHtmlId());

        return "{$pickerContainerHtml}{$element->getElementHtml()}{$pickerConfigsBlock->toHtml()}";
    }

    /**
     * Decorate field row html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string $html
     * @return string
     */
    protected function _decorateRowHtml(AbstractElement $element, $html): string
    {
        return '<tr id="row_' . $element->getHtmlId() . '" class="pr-color-picker-field">' . $html . '</tr>';
    }

    /**
     * Get color picker config.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string                                               $pickerPlaceholderId
     * @return array
     */
    private function getConfig(AbstractElement $element, string $pickerPlaceholderId): array
    {
        $config = $this->getDefaultConfigs();
        $config['el'] = '#' . $pickerPlaceholderId;
        $config['default'] = $element->getEscapedValue() ?: null;
        $config['disabled'] = (bool) $element->getData('disabled');

        if ($element->getData('field_config/canRestore')) {
            $config = $this->arrayManager->set('components/interaction/clear', $config, false);
        }

        foreach ($element->getData('field_config') as $key => $fieldConfigValue) {
            if (0 !== strpos($key, 'pr_pickr/')) {
                continue;
            }

            $path = str_replace('pr_pickr/', '', $key);
            $value = is_numeric($fieldConfigValue) ? (int) $fieldConfigValue : $fieldConfigValue;
            $config = $this->arrayManager->set($path, $config, $value);
        }

        return $config;
    }

    /**
     * Get default configs.
     *
     * @return array
     */
    private function getDefaultConfigs(): array
    {
        return [
            'theme' => 'monolith',
            'useAsButton'=> false,
            'comparison' => false,
            'swatches' => [
                'rgba(244, 67, 54, 1)',
                'rgba(233, 30, 99, 0.95)',
                'rgba(156, 39, 176, 0.9)',
                'rgba(103, 58, 183, 0.85)',
                'rgba(63, 81, 181, 0.8)',
                'rgba(33, 150, 243, 0.75)',
                'rgba(3, 169, 244, 0.7)',
                'rgba(0, 188, 212, 0.7)',
                'rgba(0, 150, 136, 0.75)',
                'rgba(76, 175, 80, 0.8)',
                'rgba(139, 195, 74, 0.85)',
                'rgba(205, 220, 57, 0.9)',
                'rgba(255, 235, 59, 0.95)',
                'rgba(255, 193, 7, 1)'
            ],
            'components' => [
                // Main components
                'preview' => true,
                'opacity' => true,
                'hue' => true,
                // Input / output Options
                'interaction' => [
                    'hex' => true,
                    'rgba' => true,
                    'hsla' => false,
                    'hsva' => false,
                    'cmyk' => false,
                    'input' => true,
                    'clear' => true,
                    'save' => false
                ]
            ]
        ];
    }
}
