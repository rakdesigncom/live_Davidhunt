<?php

namespace Fyb\Partner\Block;

use Magento\Framework\View\Element\Template;

class PartnerForm extends Template
{
    /**
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getFormAction()
    {
        return $this->getUrl('partner/index/post', ['_secure' => true]);
    }
}
