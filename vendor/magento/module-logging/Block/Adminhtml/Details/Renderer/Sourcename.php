<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Event source name renderer
 *
 */
namespace Magento\Logging\Block\Adminhtml\Details\Renderer;

class Sourcename extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render the grid cell value
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $data = $row->getData($this->getColumn()->getIndex());
        if (!$data) {
            return '';
        }
        $html = '<div class="source-data">
                    <span class="source-name">' . $this->_escaper->escapeHtml($row->getSourceName()) . '</span>';
        if ($row->getSourceId()) {
            $html .= ' <span class="source-id">#' . $row->getSourceId() . '</span>';
        }
        return $html;
    }
}
