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

namespace Magento\AdobeCommerceEventsClient\Model;

use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\AdobeCommerceEventsClient\Model\ResourceModel\EventProvider as ResourceModel;

/**
 * Event provider model
 */
class EventProvider extends AbstractModel implements EventProviderInterface
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function setProviderId(string $providerId): EventProviderInterface
    {
        return $this->setData(self::PROVIDER_ID, $providerId);
    }

    /**
     * @inheritDoc
     */
    public function getProviderId(): string
    {
        return (string)$this->getData(self::PROVIDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setInstanceId(string $instanceId): EventProviderInterface
    {
        return $this->setData(self::INSTANCE_ID, $instanceId);
    }

    /**
     * @inheritDoc
     */
    public function getInstanceId(): string
    {
        return (string)$this->getData(self::INSTANCE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setLabel(string $label): EventProviderInterface
    {
        return $this->setData(self::LABEL, $label);
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return (string)$this->getData(self::LABEL);
    }

    /**
     * @inheritDoc
     */
    public function setDescription(string $description): EventProviderInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return (string)$this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setWorkspaceConfiguration(?string $workspaceConfiguration): EventProviderInterface
    {
        return $this->setData(self::WORKSPACE_CONFIGURATION, $workspaceConfiguration);
    }

    /**
     * @inheritDoc
     */
    public function getWorkspaceConfiguration(): string
    {
        return (string)$this->getData(self::WORKSPACE_CONFIGURATION);
    }
}
