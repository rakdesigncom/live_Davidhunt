<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdobeCommerceEventsClient\Block\Adminhtml\System\Config;

use Magento\AdobeCommerceEventsClient\Config\AdobeIoConfigurationChecker;
use Magento\AdobeCommerceEventsClient\Event\EventInitializationException;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\AdobeIoEventMetadataSynchronizer;
use Magento\AdobeCommerceEventsClient\Event\Synchronizer\EventSyncList;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Field for synchronizing events to Adobe I/O
 */
class SynchronizeEventsButton extends Field
{
    /**
     * Path to template file
     *
     * @var string
     */
    protected $_template = 'Magento_AdobeCommerceEventsClient::system/config/synchronize_events.phtml';

    /**
     * @param Context $context
     * @param EventSyncList $eventSyncList
     * @param AdobeIoConfigurationChecker $configChecker
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private EventSyncList $eventSyncList,
        private AdobeIoConfigurationChecker $configChecker,
        private LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Returns element html
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Generates button html
     *
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(
            Button::class
        )->setData(
            [
                'id' => 'synchronize_events',
                'label' => __('Execute Synchronization'),
                'disabled' => $this->isButtonDisabled()
            ]
        );

        return $button->toHtml();
    }

    /**
     * Gets ajax url for synchronizing events to Adobe I/O
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('adminhtml/synchronization/synchronizeEvents');
    }

    /**
     * Checks whether there are registered events to sync to Adobe I/O
     *
     * @return bool
     */
    public function hasEventsToSync(): bool
    {
        try {
            return !empty($this->eventSyncList->getList());
        } catch (EventInitializationException $e) {
            $this->logger->error(
                'Unable to check for events to sync to Adobe I/O: ' . $e->getMessage(),
                ['destination' => ['internal', 'external']]
            );

            return false;
        }
    }

    /**
     * Checks whether the Adobe I/O configuration is complete
     *
     * @return bool
     */
    public function isConfigurationComplete(): bool
    {
        return $this->configChecker->isComplete();
    }

    /**
     * Checks Adobe I/O config and the list of events to be synced to determine whether the button should be disabled
     *
     * @return bool
     */
    public function isButtonDisabled(): bool
    {
        return !$this->isConfigurationComplete() || !$this->hasEventsToSync();
    }
}
