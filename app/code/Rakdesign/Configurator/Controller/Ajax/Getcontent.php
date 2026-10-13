<?php

namespace Rakdesign\Configurator\Controller\Ajax;

class Getcontent extends \Magento\Framework\App\Action\Action {

    protected $registry;

    public function __construct(
            \Magento\Framework\App\Action\Context $context,
            \Magento\Framework\Registry $registry) {

        $this->registry = $registry;
        parent::__construct($context);
    }

    public function execute() {

        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }

}
