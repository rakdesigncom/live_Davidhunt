<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form\Element\DynamicTable;

use Magento\Backend\Block\Widget;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Plumrocket\ExtendedAdminUi\Block\Adminhtml\System\Config\Form\Element\DynamicTable\InputTable\Column;

/**
 * @since 1.1.0
 */
class InputTable extends Extended implements RendererInterface
{
    private const HIDDEN_ELEMENT_CLASS = 'hidden-input-table';

    /** @var \Magento\Framework\Data\Form\Element\AbstractElement */
    protected $_element;

    /** @var string  */
    protected $_containerFieldId;

    /** @var string */
    protected $_rowKey;

    /**
     * @var \Magento\Framework\Data\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context   $context
     * @param \Magento\Backend\Helper\Data              $backendHelper
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param \Magento\Framework\DataObjectFactory      $dataObjectFactory
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        parent::__construct(
            $context,
            $backendHelper,
            $data
        );
    }

    /**
     * Configure table.
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function _construct()
    {
        parent::_construct();
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        $this->setMessageBlockVisibility(false);
    }

    /**
     * @inheritDoc
     */
    public function addColumn($columnId, $column): self
    {
        if (is_array($column)) {
            $column['sortable'] = false;
            $this->getColumnSet()->setChild(
                $columnId,
                $this->getLayout()
                    ->createBlock(Column::class)
                    ->setData($column)
                    ->setId($columnId)
                    ->setGrid($this)
            );
            $this->getColumnSet()->getChildBlock($columnId)->setGrid($this);
        } else {
            throw new LocalizedException(__('Please correct the column format and try again.'));
        }

        $this->_lastColumnId = $columnId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function canDisplayContainer(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        return Widget::_prepareLayout();
    }

    /**
     * Prepare field values.
     *
     * @param array $array
     * @return $this
     */
    public function setArray(array $array): InputTable
    {
        $collection = $this->collectionFactory->create();
        $i = 1;
        foreach ($array as $item) {
            if (! $item instanceof DataObject) {
                $item = $this->dataObjectFactory->create(['data' => $item]);
            }

            if (! $item->getId()) {
                $item->setId($i);
            }

            $collection->addItem($item);
            $i++;
        }
        $this->setCollection($collection);
        return $this;
    }

    /**
     * Get row key.
     *
     * @return string
     */
    public function getRowKey(): string
    {
        return $this->_rowKey;
    }

    /**
     * Set row key.
     *
     * @param string $key
     * @return $this
     */
    public function setRowKey($key): InputTable
    {
        $this->_rowKey = $key;
        return $this;
    }

    /**
     * Get container field id.
     *
     * @return string
     */
    public function getContainerFieldId(): string
    {
        return $this->_containerFieldId;
    }

    /**
     * Set container field id.
     *
     * @param string $name
     * @return $this
     */
    public function setContainerFieldId(string $name): InputTable
    {
        $this->_containerFieldId = $name;
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml(): string
    {
        $html = parent::_toHtml();
        $html = preg_replace(
            '/(\s+class\s*=\s*["\'](?:\s*|[^"\']*\s+)messages)((?:\s*|\s+[^"\']*)["\'])/isU',
            '$1 ' . self::HIDDEN_ELEMENT_CLASS . ' $2',
            $html
        );

        return str_replace(
            '<div class="admin__data-grid-wrap',
            '<div id="' . $this->getHtmlId() . '_wrap" class="admin__data-grid-wrap dynamic-table-field',
            $html
        );
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element): string
    {
        $this->setElement($element);
        return
            '<tr>
                <td class="label">' . $element->getLabelHtml() . '</td>
                <td class="value">' . $this->toHtml() . '</td>
            </tr>';
    }

    /**
     * Set element.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return $this
     */
    public function setElement(AbstractElement $element): self
    {
        $this->_element = $element;
        return $this;
    }

    /**
     * Get element.
     *
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function getElement(): AbstractElement
    {
        return $this->_element;
    }
}
