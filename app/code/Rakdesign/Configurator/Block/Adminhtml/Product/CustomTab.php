<?php

namespace Rakdesign\Configurator\Block\Adminhtml\Product;

class CustomTab extends \Magento\Backend\Block\Template {

    /**
     * Block template.
     *
     * @var string
     */
    protected $_template = 'custom_tab.phtml';
    protected $_registry;

    public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            \Magento\Framework\Registry $registry,
            array $data = []
    ) {
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    public function getCurrentProduct() {
        return $this->_registry->registry('current_product');
    }

}
