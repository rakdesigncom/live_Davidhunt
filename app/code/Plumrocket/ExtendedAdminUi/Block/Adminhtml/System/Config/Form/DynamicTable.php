<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form\Element\DynamicTable\InputTable;

/**
 * @since 1.1.0
 */
abstract class DynamicTable extends Field
{
    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = '<input type="hidden" name="' . $element->getname() . '" value="" />';

        /** @var \Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form\Element\DynamicTable\InputTable $table */
        $table = $this->getLayout()->createBlock(InputTable::class);
        $table->setContainerFieldId((string) $element->getName())
            ->setRowKey('name');
        foreach ($this->getColumns() as $columnId => $column) {
            $table->addColumn($columnId, $column);
        }
        $table->setArray($this->_getValue($element->getValue()));

        $html .= $table->toHtml();
        $html .= $this->getNewRowButtonHtml();
        $html .= $this->getInitScriptHtml($element);

        return $html;
    }

    /**
     * Get field table columns settings.
     *
     * @return array[]
     */
    abstract protected function getColumns(): array;

    /**
     * Get prepared field value.
     *
     * @param array $data
     * @return array[]
     */
    protected function _getValue(array $data = []): array
    {
        $rows = [
            '_TMPNAME_' => [],
        ];

        if ($data && is_array($data)) {
            $rows = array_merge($rows, $data);
        }

        foreach ($rows as $name => &$row) {
            $row += ['name' => $name];
        }

        return $rows;
    }

    /**
     *  Get new row button HTML.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getNewRowButtonHtml(): string
    {
        return $this->getLayout()
            ->createBlock(Button::class)
            ->addData($this->getButtonData())
            ->toHtml();
    }

    /**
     * Get settings for new row button.
     *
     * @return array
     */
    protected function getButtonData(): array
    {
        return [
            'label' => __('Add Row'),
            'type' => 'button',
            'class' => 'add dynamic-table-field add-row-value',
        ];
    }

    /**
     * Get field init script HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function getInitScriptHtml(AbstractElement $element): string
    {
        return <<<HTML
<script>
    require([
        'Plumrocket_ExtendedAdminUi/js/form/element/dynamic-table',
        'domReady!'
    ], function (dynamicTable) {
        dynamicTable.initialize('row_{$element->getHtmlId()}')
    });
</script>
HTML;
    }
}
