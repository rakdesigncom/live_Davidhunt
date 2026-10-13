<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\Model\Handler\Controllers;

use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Logging\Model\Event;
use Magento\Logging\Model\Event\ChangesFactory;
use Magento\Logging\Model\Processor;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\Filter\Input\MaliciousCode;

class ConfigSaveHandler
{
    /**
     * @var string
     */
    public const KEY_STORE_CONFIG_ORIGINAL_VALUES = 'store_config_original_values';

    /**
     * @var Structure
     */
    private $structureConfig;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ChangesFactory
     */
    private $eventChangesFactory;

    /**
     * @var MaliciousCode
     */
    private $maliciousCode;

    /**
     * @param Structure $structureConfig
     * @param DataPersistorInterface $dataPersistor
     * @param RequestInterface $request
     * @param ChangesFactory $eventChangesFactory
     * @param MaliciousCode|null $maliciousCode
     */
    public function __construct(
        Structure $structureConfig,
        DataPersistorInterface $dataPersistor,
        RequestInterface $request,
        ChangesFactory $eventChangesFactory,
        MaliciousCode $maliciousCode = null
    ) {
        $this->structureConfig = $structureConfig;
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
        $this->eventChangesFactory = $eventChangesFactory;
        $this->maliciousCode = $maliciousCode ??
            ObjectManager::getInstance()->get(MaliciousCode::class);
    }

    /**
     * Execute custom handler for config save
     *
     * @param Event $eventModel
     * @param Processor $processor
     */
    public function execute(
        Event $eventModel,
        Processor $processor
    ): void {
        $postData = $this->request->getPostValue();

        $sectionId = $this->request->getParam('section') ?? 'general';
        $eventModel->setInfo($sectionId);

        if (!isset($postData['groups']) || !is_array($postData['groups'])) {
            return;
        }

        $skippedEncryptedFields = $this->getSkippedEncryptedFields();

        $originalData = $this->getOriginalData();

        foreach ($postData['groups'] as $groupName => $groupData) {
            $this->createEventChangeEntryForGroup(
                $processor,
                $sectionId,
                $skippedEncryptedFields,
                $originalData,
                $groupName,
                $groupData
            );
        }
    }

    /**
     * Get node paths for encrypted fields
     *
     * @return array
     */
    private function getSkippedEncryptedFields(): array
    {
        $encryptedNodePaths = $this->structureConfig->getFieldPathsByAttribute('backend_model', Encrypted::class);

        $encryptedFields = [];
        foreach ($encryptedNodePaths as $path) {
            $encryptedFields[] = substr($path, strrpos($path, '/') + 1);
        }

        return $encryptedFields;
    }

    /**
     * Get original data for config values before they were saved with new values
     *
     * @return array
     */
    private function getOriginalData(): array
    {
        $originalData = [];

        if ($this->dataPersistor->get(self::KEY_STORE_CONFIG_ORIGINAL_VALUES)) {
            $originalData = $this->dataPersistor->get(
                self::KEY_STORE_CONFIG_ORIGINAL_VALUES
            );
            $this->dataPersistor->clear(self::KEY_STORE_CONFIG_ORIGINAL_VALUES);
        }

        return $originalData;
    }

    /**
     * Create event chaneg entry for a config group
     *
     * @param Processor $processor
     * @param string $sectionId
     * @param array $skippedEncryptedFields
     * @param array $originalData
     * @param string $groupName
     * @param array $groupData
     */
    private function createEventChangeEntryForGroup(
        Processor $processor,
        string $sectionId,
        array $skippedEncryptedFields,
        array $originalData,
        string $groupName,
        array $groupData
    ): void {
        $groupOriginalData = [];
        $groupFieldsData = [];
        if (!isset($groupData['fields']) || !is_array($groupData['fields'])) {
            return;
        }
        foreach ($groupData['fields'] as $fieldName => $fieldValueData) {
            //Clearing config data accordingly to collected skip fields
            if (in_array($fieldName, $skippedEncryptedFields)) {
                continue;
            }

            $fieldPath = sprintf('%s/%s/%s', $sectionId, $groupName, $fieldName);
            $originalValue = $originalData[$fieldPath] ?? null;
            $newValue = $fieldValueData['value'] ?? $originalValue;

            if (isset($fieldValueData['inherit'])) {
                $newValue = $originalValue;
            }

            if ($originalValue != $newValue) {
                $groupOriginalData[$fieldName] = $originalValue;
                $groupFieldsData[$fieldName] = $newValue;
            }
        }

        /** @var \Magento\Logging\Model\Event\Changes $change */
        $change = $this->eventChangesFactory->create();

        $groupName = $this->maliciousCode->filter($groupName);

        $processor->addEventChanges(
            $change->setSourceName($groupName)
                ->setOriginalData($groupOriginalData)
                ->setResultData($groupFieldsData)
        );
    }
}
