<?php

namespace Rakdesign\Configurator\Block\Adminhtml\Customconfigurator;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended {

    protected $moduleManager;
    protected $configuratorFactory;
    protected $_status;

    public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            \Magento\Backend\Helper\Data $backendHelper,
            \Rakdesign\Configurator\Model\ConfiguratorFactory $configuratorFactory,
            \Magento\Framework\Module\Manager $moduleManager,
            array $data = []
    ) {
        $this->configuratorFactory = $configuratorFactory;
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct() {
        parent::_construct();
        $this->setId('postGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
        $this->setVarNameFilter('post_filter');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection() {
        $collection = $this->configuratorFactory->create()->getCollection();
        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    /**
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns() {
        $this->addColumn(
                'entity_id',
                [
                    'header' => __('ID'),
                    'type' => 'number',
                    'index' => 'entity_id',
                    'header_css_class' => 'col-id',
                    'column_css_class' => 'col-id'
                ]
        );



        $this->addColumn(
                'product_id',
                [
                    'header' => __('Product Id'),
                    'index' => 'product_id',
                ]
        );
     




       // $this->addExportType($this->getUrl('customconfigurator/*/exportCsv', ['_current' => true]), __('CSV'));
       // $this->addExportType($this->getUrl('customconfigurators/*/exportExcel', ['_current' => true]), __('Excel XML'));

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */

    /**
     * @return string
     */
    public function getGridUrl() {
        return $this->getUrl('customconfigurator/*/index', ['_current' => true]);
    }

    /**
     * @param \Magento\Framework\Object $row
     * @return string
     */
    public function getRowUrl($row) {

        return $this->getUrl(
                        'catalog/product/edit',
                        ['id' => $row->getProductId()]
        );
    }

}
