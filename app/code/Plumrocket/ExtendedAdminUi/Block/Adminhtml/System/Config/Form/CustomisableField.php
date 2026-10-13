<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Asset\Repository;

/**
 * Frontend model for render customizations.
 *
 * See README.md for instruction
 *
 * @since 1.0.4
 */
class CustomisableField extends Field
{

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $viewAssetRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context  $context
     * @param \Magento\Framework\View\Asset\Repository $viewAssetRepository
     * @param array                                    $data
     */
    public function __construct(
        Context $context,
        Repository $viewAssetRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->viewAssetRepository = $viewAssetRepository;
    }

    /**
     * Render element value
     *
     * @param \Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form\Element\ImageRadioButtons $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element): string
    {
        $valueCssClass = $element->getPrValueCustomCssClass();
        $this->addAttributes($element);

        if ($element->getTooltip()) {
            $html = '<td class="value with-tooltip ' . $valueCssClass . '">';
            $html .= $this->_getElementHtml($element);
            $html .= '<div class="tooltip"><span class="help"><span></span></span>';
            $html .= '<div class="tooltip-content">' . $element->getTooltip() . '</div></div>';
        } else {
            $html = '<td class="value ' . $valueCssClass . '">';
            $html .= $this->_getElementHtml($element);
        }
        $comment = (string) $element->getComment();
        if ($comment) {
            $html .= '<p class="note"><span>' . $this->filterTooltipMediaVariables($element, $comment) . '</span></p>';
        }
        $html .= '</td>';
        return $html;
    }

    /**
     * Disable checkbox if needed.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return bool
     */
    protected function _isInheritCheckboxRequired(AbstractElement $element): bool
    {
        if ($element->getData('field_config/pr_disable_inherit_checkbox')
            || $element->getPrDisableInheritCheckbox()
        ) {
            return false;
        }
        return parent::_isInheritCheckboxRequired($element);
    }

    /**
     * Disable scope label if needed.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _renderScopeLabel(AbstractElement $element): string
    {
        if ($element->getData('field_config/pr_disable_scope_label')) {
            return '';
        }
        return parent::_renderScopeLabel($element);
    }

    /**
     * Replace media directives by urls.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @param string                                               $comment
     * @return string
     */
    private function filterTooltipMediaVariables(AbstractElement $element, string $comment): string
    {
        if (! $element->getData('field_config/pr_allow_variables')) {
            return $comment;
        }

        if (preg_match_all('/{{media url="(.*?)".*?}}/', $comment, $media)) {
            $urls = [];
            foreach ($media[1] as $fieldId) {
                $urls[] = $this->viewAssetRepository->getUrl($fieldId);
            }
            $comment = str_replace($media[0], $urls, $comment);
        }

        return $comment;
    }

    /**
     * Add defined in system.xml attributes as element custom attributes.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return void
     */
    private function addAttributes(AbstractElement $element): void
    {
        if (! is_array($element->getData('field_config'))) {
            return;
        }

        foreach ($element->getData('field_config') as $key => $value) {
            if (0 !== strpos($key, 'pr_element_attribute/')) {
                continue;
            }

            $attrName = str_replace('pr_element_attribute/', '', $key);
            $element->addCustomAttribute($attrName, $value);
        }
    }
}
