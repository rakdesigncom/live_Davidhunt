<?php

namespace Fyb\Faq\Controller\Adminhtml\Qa;

use Fyb\Faq\Controller\Adminhtml\Qa;

class MassDelete extends Qa
{
    /**
     * @return void
     */
    public function execute()
    {
        $collection = $this->_filter->getCollection($this->_collectionFactory->create());
        $collectionSize = $collection->getSize();
        foreach ($collection as $item) {
            try {
                $item->delete();
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->messageManager->addSuccess(
            __('A total of %1 qa(s) were deleted.', count($collectionSize))
        );
        $this->_redirect('*/*/index');
    }
}
