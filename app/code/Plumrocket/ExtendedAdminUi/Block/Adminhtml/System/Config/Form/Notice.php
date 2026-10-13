<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license/  End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Color picker for system settings
 *
 * @since 1.0.3
 */
class Notice extends Field
{

    /**
     * Render notice.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $short = (bool) $element->getData('field_config/pr_notice__short');

        if ($short || $element->getLabel()) {
            $html = '<td class="label"><label for="' .
                $element->getHtmlId() . '"><span>' .
                $element->getLabel() .
                '</span></label></td>';
            $html .= '<td class="value pr-notice__content-wrapper">';
        } else {
            $html = '<td class="value pr-notice__content-wrapper" colspan="3">';
        }

        $html .= '<div class="pr-notice__content">';
        $html .= $this->_getElementHtml($element);
        $html .= '</div>';
        $html .= '</td>';

        return $this->_decorateRowHtml($element, $html);
    }

    /**
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $element->getData('field_config/pr_notice__text') ?: '';
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
        $level = $element->getData('field_config/pr_notice__level') ?: 'info';
        $class = 'pr_notice pr_notice--' . $level . ($element->getLabel() ? '' : ' pr-notice--full-width');
        return '<tr id="row_' . $element->getHtmlId() . '" class="' . $class . '">'
            . $html
            . '</tr>';
    }
}
