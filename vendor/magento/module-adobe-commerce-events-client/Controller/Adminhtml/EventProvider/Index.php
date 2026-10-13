<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Controller\Adminhtml\EventProvider;

use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Backend\Model\View\Result\Page;

/**
 * Event provider grid backend controller
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_AdobeCommerceEventsClient::event_provider';

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param bool $checkWorkspaceConfig
     */
    public function __construct(
        private readonly Context $context,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly bool $checkWorkspaceConfig = true
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        if ($this->checkWorkspaceConfig
            && empty($this->scopeConfig->getValue(Config::CONFIG_PATH_WORKSPACE_CONFIGURATION))
        ) {
            $this->getMessageManager()->addWarningMessage(
                __(
                    'The Adobe I/O Workspace Configuration is required for sending events. ' .
                    'Please navigate to Stores > Settings > Configuration > Adobe Services > ' .
                    'Adobe I/O Events > General configuration -> Adobe I/O Workspace Configuration and fill the ' .
                    'field with the configuration from App Builder project.'
                )
            );
        }

        /** @var Page $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $result->setActiveMenu(self::ADMIN_RESOURCE);
        $result->getConfig()->getTitle()->prepend(__('Event Providers'));

        return $result;
    }
}
