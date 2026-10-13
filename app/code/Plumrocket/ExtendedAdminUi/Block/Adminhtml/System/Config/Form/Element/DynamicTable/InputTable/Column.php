<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form\Element\DynamicTable\InputTable;

use Magento\Backend\Block\Widget\Grid\Column\Extended;
use Magento\Framework\DataObject;

/**
 * @since 1.1.0
 */
class Column extends Extended
{
    /**
     * Table row key value.
     *
     * @var null
     */
    protected $_rowKeyValue;

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return preg_replace('#-$#', '', $this->getName());
    }

    /**
     * @inheritDoc
     */
    public function getRowField(DataObject $row)
    {
        if (null !== $this->getGrid()->getRowKey()) {
            $this->_rowKeyValue = $row->getData($this->getGrid()->getRowKey());
        }

        if (! $this->_rowKeyValue) {
            return '';
        }

        return parent::getRowField($row);
    }

    /**
     * Get column field name.
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->getName();
    }

    /**
     * Get column html name.
     *
     * @return string
     */
    public function getHtmlName(): string
    {
        return $this->getName();
    }

    /**
     * Get column name.
     *
     * @return string
     */
    public function getName(): string
    {
        return sprintf(
            '%s[%s][%s]',
            $this->getGrid()->getContainerFieldId(),
            $this->_rowKeyValue,
            parent::getId()
        );
    }
}
