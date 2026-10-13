<?php

namespace Fyb\Faq\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;

class Faq extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Fyb\Faq\Model\ResourceModel\Qa\CollectionFactory
     */
    protected $qaCollectionFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(
        Context $context,
        \Fyb\Faq\Model\ResourceModel\Qa\CollectionFactory $qaCollectionFactory,
        RequestInterface $request,
        array $data = []
    ) {
        $this->qaCollectionFactory = $qaCollectionFactory;
        $this->request = $request;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('FAQs'));
        $this->pageConfig->setKeywords(__('FAQs'));
        $this->pageConfig->setDescription(__('FAQs'));

        return parent::_prepareLayout();
    }

    public function getQuestions()
    {
        $collection = $this->qaCollectionFactory->create();
        $collection->addFieldToFilter('is_active', true);
        $collection->addOrder('sort_order', 'ASC');

        return $collection;
    }
}
