<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2026 Adobe
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
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Config\Backend;

use Magento\AdobeIoEventsClient\Model\WorkspaceConfigurationJsonCleanerInterface;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Backend model for the workspace_configuration admin field.
 *
 * Strips non-essential keys from project.workspace.details before the value is
 * encrypted and persisted, keeping only `credentials` and `runtime`.
 */
class WorkspaceConfiguration extends Encrypted
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param EncryptorInterface $encryptor
     * @param WorkspaceConfigurationJsonCleanerInterface $jsonCleaner
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        EncryptorInterface $encryptor,
        private readonly WorkspaceConfigurationJsonCleanerInterface $jsonCleaner,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $encryptor,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Clean the JSON payload before delegating encryption to the parent.
     *
     * Obscured values (unchanged field indicator) and empty values are passed
     * through to the parent unchanged, matching the same guards in Encrypted::beforeSave().
     *
     * @return void
     */
    public function beforeSave(): void
    {
        $value = (string) $this->getValue();

        if (!preg_match('/^\*+$/', $value) && !empty($value)) {
            $this->setValue($this->jsonCleaner->clean($value));
        }

        parent::beforeSave();
    }
}
