<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license/  End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form;

use Magento\Framework\Data\Form\Element\Radios;
use Magento\Framework\DataObject;

/**
 * For using this block you have to
 *
 * @deprecated since 1.0.4
 * @see \Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form\Element\ImageRadioButtons
 * @since 1.0.0
 */
class RadiosWithImage extends Radios
{
    /**
     * @inheritDoc
     */
    protected function _optionToHtml($option, $selected)
    {
        $html = '<div class="admin__field admin__field-option plum_admin__field-radios-with-image">' .
            '<input type="radio" ' . $this->getRadioButtonAttributes($option);
        if (is_array($option)) {
            $html .= 'value="' . $this->_escape(
                $option['value']
            ) . '" class="admin__control-radio" id="' . $this->getHtmlId() . $option['value'] . '"';
            if ($option['value'] == $selected) {
                $html .= ' checked="checked"';
            }
            $html .= ' />';
            $html .= '<label class="admin__field-label" for="' .
                $this->getHtmlId() .
                $option['value'] .
                '"><span>' .
                $option['label'] .
                '</span>' .
                '<div class="pr-radio-img-wrp">' .
                '<img src="' . $option['image'] . '" alt="' . $option['label'] . ' Icon">' .
                '</div>
                </label>';

        } elseif ($option instanceof DataObject) {
            $html .= 'id="' . $this->getHtmlId() . $option->getValue() . '"' . $option->serialize(
                ['label', 'title', 'value', 'class', 'style']
            );
            if (in_array($option->getValue(), $selected)) {
                $html .= ' checked="checked"';
            }
            $html .= ' />';
            $html .= '<label class="inline" for="' .
                $this->getHtmlId() .
                $option->getValue() .
                '">' .
                $option->getLabel() .
                '<div class="pr-radio-img-wrp">' .
                '<img src="' . $option->getImage() . '" alt="' . $option->getLabel() . ' Icon">' .
                '</div>
                </label>';
        }
        $html .= '</div>';
        return $html;
    }
}
