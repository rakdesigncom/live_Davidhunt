<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form\Element;

use Magento\Framework\Data\Form\Element\Radios;

/**
 * Frontend model for select field
 *
 * See README.md for instruction
 *
 * @method bool|null getDisabled()
 *
 * @since 1.0.4
 */
class ImageRadioButtons extends Radios
{

    /**
     * Get custom CSS class.
     *
     * @return string
     */
    public function getPrValueCustomCssClass(): string
    {
        return 'plum_admin__field-wrapper';
    }

    /**
     * @inheritDoc
     */
    protected function _optionToHtml($option, $selected): string
    {
        $html = '<div class="admin__field admin__field-option plum_admin__field-image-radio-buttons">';

        $inputId = $this->getHtmlId() . $option['value'];

        $html .= $this->createOptionInput($option, $inputId, $selected);
        $html .= $this->createOptionLabel($option, $inputId);

        return $html . '</div>';
    }

    /**
     * Create input html.
     *
     * @param array            $option
     * @param string           $inputId
     * @param int|string|array $selected
     * @return string
     */
    private function createOptionInput(array $option, string $inputId, $selected): string
    {
        $html = '<input type="radio" ' . $this->getRadioButtonAttributes($option);

        $html .= 'value="' . $this->_escape($option['value']). '" ' .
            'class="admin__control-radio" ' .
            'id="' . $inputId . '"';

        if ($option['value'] == $selected) {
            $html .= ' checked="checked"';
        }

        if ($this->getDisabled()) {
            $html .= ' disabled';
        }

        return $html . ' />';
    }

    /**
     * Create label with image in it.
     *
     * @param array  $option
     * @param string $inputId
     * @return string
     */
    protected function createOptionLabel(array $option, string $inputId): string
    {
        if (empty($option['image'])) {
            $option['image'] = '';
        }

        $optionHtml = '<label class="admin__field-label" for="' . $inputId . '">' .
            '<span class="pr-radio-img-label">' . $option['label'] . '</span>' .
            '<div class="pr-radio-img-wrp">' .
            '<img alt="' . $option['label'] . ' preview" ' .
            'src="' . $option['image'] . '" '.
            $this->createSourceSet($option, 'image') .
            ($this->getDisabled() ? 'style="display: none;"' : '') .
            '>';

        if (isset($option['anim'])) {
            $optionHtml .= "<img class=\"image__anim\" alt=\"{$option['label']} animation\"" .
                $this->createSourceSet($option, 'anim') .
                'src="' . $option['anim'] . '" ' .
                ($this->getDisabled() ? 'style="display: none;"' : '') .
                '>';
        }

        $optionHtml .= '</div></label>';

        return $optionHtml;
    }

    /**
     * Create source set for retina.
     *
     * @param array  $option
     * @param string $type
     * @return string
     */
    private function createSourceSet(array $option, string $type): string
    {
        $retinaUrl = $type . '2x';
        if (! isset($option[$retinaUrl]) || ! $option[$retinaUrl]) {
            return '';
        }
        return "srcset=\"{$option['image']}, {$option['image2x']} 2x\" ";
    }
}
