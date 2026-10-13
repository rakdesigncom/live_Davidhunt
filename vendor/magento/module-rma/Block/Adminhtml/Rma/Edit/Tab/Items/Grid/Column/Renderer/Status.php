<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2014 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

/**
 * Grid column widget for rendering status grid cells
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer;

use Magento\Framework\DataObject;
use Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\AbstractRenderer;

class Status extends AbstractRenderer
{
    /**
     * Renders status column when it is editable
     *
     * @param   DataObject $row
     * @return  string
     */
    protected function _getEditableView(DataObject $row)
    {
        $options = $this->getStatusManager()->getAllowedStatuses();

        $selectName = 'items[' . $row->getId() . '][' . $this->getColumn()->getId() . ']';
        $html = '<select name="' . $selectName . '" class="admin__control-select required-entry">';
        $value = $row->getData($this->getColumn()->getIndex());
        $html .= '<option value=""></option>';
        foreach ($options as $val => $label) {
            $selected = $val == $value && $value !== null ? ' selected="selected"' : '';
            $html .= '<option value="' . $val . '"' . $selected . '>' .
                $this->_escaper->escapeHtml($label) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Renders status column when it is not editable
     *
     * @param   DataObject $row
     * @return  string
     */
    protected function _getNonEditableView(DataObject $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $options = $this->getStatusManager()->getAllowedStatuses();
        return $this->_escaper->escapeHtml($options[$value] ?? $value);
    }
}
