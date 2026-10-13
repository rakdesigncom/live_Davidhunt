<?php
namespace Fyb\StockistLocator\Controller\Adminhtml\Stockist;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->_objectManager->create('Fyb\StockistLocator\Model\Localstockists');
                $model->load($id);

                if ($model->getId()) {
                    $model->delete();
                }

                $this->messageManager->addSuccess(__('The item has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $id]);
            }
        }

        $this->messageManager->addError(__('We can\'t find a item to delete.'));

        return $resultRedirect->setPath('*/*/');
    }
}
