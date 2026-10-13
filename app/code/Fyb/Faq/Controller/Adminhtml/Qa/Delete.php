<?php

namespace Fyb\Faq\Controller\Adminhtml\Qa;

use Fyb\Faq\Controller\Adminhtml\Qa;

class Delete extends Qa
{
    /**
     * @return void
     */
    public function execute()
    {
        $qaId = (int)$this->getRequest()->getParam('entity_id');
        if ($qaId) {
            /** @var $qaModel \Fyb\Faq\Model\Qa */
            $qaModel = $this->_qaFactory->create();
            $qaModel->load($qaId);
            // Check this qa exists or not
            if (!$qaModel->getId()) {
                $this->messageManager->addError(__('This qa no longer exists.'));
            } else {
                try {
                    // Delete QA
                    $qaModel->delete();
                    $this->messageManager->addSuccess(__('The qa has been deleted.'));
                    // Redirect to grid page
                    $this->_redirect('*/*/');

                    return;
                } catch (\Exception $e) {
                    $this->messageManager->addError($e->getMessage());
                    $this->_redirect('*/*/edit', ['entity_id' => $qaModel->getId()]);
                }
            }
        }
    }
}
