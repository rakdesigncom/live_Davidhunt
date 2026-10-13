<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

class Delete extends \Magento\GiftRegistry\Controller\Index implements HttpPostActionInterface
{
    /**
     * Delete selected gift registry entity
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            $entity = $this->_initEntity();
            if ($entity->getId()) {
                $entity->delete();
                $this->messageManager->addSuccess(__('You deleted this gift registry.'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $message = __('Something went wrong while deleting the gift registry.');
            $this->messageManager->addException($e, $message);
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
