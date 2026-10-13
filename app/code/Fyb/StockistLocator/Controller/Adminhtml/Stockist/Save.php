<?php
namespace Fyb\StockistLocator\Controller\Adminhtml\Stockist;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;

class Save extends \Magento\Backend\App\Action
{
    protected $modelFactory;
    protected $formKeyValidator;

    /**
     * @param Action\Context $context
     */
    public function __construct(
        Context $context,
        \Fyb\StockistLocator\Model\LocalstockistsFactory $modelFactory,
        Validator $formKeyValidator
    ) {
        parent::__construct($context);

        $this->modelFactory = $modelFactory;
        $this->formKeyValidator = $formKeyValidator;
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__("Form key is invalid"));
            return $resultRedirect->setPath('*/*/index');
        }

        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $model = $this->modelFactory->create();

            $id = $data['entity_id'] ?? '';
            unset($data['entity_id']);
            if ($id) {
                $model->load($id);
            }

            $model->addData($data)->save();
            $this->messageManager->addSuccess(__('The Stockist has been saved.'));

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e, __("We can't submit your request, Please try again."));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
