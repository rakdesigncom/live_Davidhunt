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

namespace Magento\AdobeCommerceEventsClient\Api\Data;

/**
 * Interface for event provider
 *
 * @api
 */
interface EventProviderInterface
{
    public const ID = 'id';
    public const PROVIDER_ID = 'provider_id';
    public const INSTANCE_ID = 'instance_id';
    public const LABEL = 'label';
    public const DESCRIPTION = 'description';
    public const WORKSPACE_CONFIGURATION = 'workspace_configuration';

    public const OBSCURE_WORKSPACE_CONFIGURATION = '******';

    /**
     * The ID of the event provider record
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set the ID of the event provider record
     *
     * @param int|null $id
     * @return EventProviderInterface
     */
    public function setId($id);

    /**
     * Sets event provider id
     *
     * @param string $providerId
     * @return EventProviderInterface
     */
    public function setProviderId(string $providerId): EventProviderInterface;

    /**
     * Returns event provider id
     *
     * @return string
     */
    public function getProviderId(): string;

    /**
     * Sets event provider instance id
     *
     * @param string $instanceId
     * @return EventProviderInterface
     */
    public function setInstanceId(string $instanceId): EventProviderInterface;

    /**
     * Returns event provider instance id
     *
     * @return string
     */
    public function getInstanceId(): string;

    /**
     * Sets event provider label
     *
     * @param string $label
     * @return EventProviderInterface
     */
    public function setLabel(string $label): EventProviderInterface;

    /**
     * Returns event provider label
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Sets event provider description
     *
     * @param string $description
     * @return EventProviderInterface
     */
    public function setDescription(string $description): EventProviderInterface;

    /**
     * Returns event provider description
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Sets event provider workspace configuration
     *
     * @param string|null $workspaceConfiguration
     * @return EventProviderInterface
     */
    public function setWorkspaceConfiguration(?string $workspaceConfiguration): EventProviderInterface;

    /**
     * Returns event provider workspace configuration
     *
     * @return string
     */
    public function getWorkspaceConfiguration(): string;
}
