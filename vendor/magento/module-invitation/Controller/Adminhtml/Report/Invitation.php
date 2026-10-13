<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Invitation\Model\Config;

/**
 * Invitation reports controller
 *
 */
abstract class Invitation extends Action
{
    /**
     * Invitation Config
     *
     * @var Config
     */
    protected $_config;

    /**
     * @var FileFactory
     */
    protected $_fileFactory;

    /**
     * @param Context $context
     * @param Config $config
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        Config $config,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->_fileFactory = $fileFactory;
        $this->_config = $config;
    }

    /**
     * Init action breadcrumbs
     *
     * @return $this
     */
    public function _initAction()
    {
        $this->_view->loadLayout();
        $this->_addBreadcrumb(__('Reports'), __('Reports'));
        $this->_addBreadcrumb(__('Invitations'), __('Invitations'));
        return $this;
    }

    /**
     * Acl admin user check
     *
     * @return boolean
     */
    protected function _isAllowed(): bool
    {
        if ($this->_config->isEnabled()) {
            return match ($this->getRequest()->getActionName()) {
                'exportCsv', 'exportExcel' =>
                    $this->_authorization->isAllowed('Magento_Invitation::general'),
                'exportCustomerCsv', 'exportCustomerExcel' =>
                    $this->_authorization->isAllowed('Magento_Invitation::magento_invitation_customer'),
                'exportOrderCsv', 'exportOrderExcel' =>
                    $this->_authorization->isAllowed('Magento_Invitation::order'),
                default =>
                    $this->_authorization->isAllowed('Magento_Reports::report'),
            };
        }

        return false;
    }
}
