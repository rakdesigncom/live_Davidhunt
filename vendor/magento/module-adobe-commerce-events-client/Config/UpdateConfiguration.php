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

namespace Magento\AdobeCommerceEventsClient\Config;

use Magento\AdobeCommerceEventsClient\Api\UpdateConfigurationInterface;
use Magento\AdobeCommerceEventsClient\Api\Data\ConfigurationInterface;
use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\AdobeIoEventsClient\Model\WorkspaceConfigurationJsonCleanerInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Updates eventing configuration
 */
class UpdateConfiguration implements UpdateConfigurationInterface
{
    /**
     * @var array
     */
    private array $encryptedValues = [
        Config::CONFIG_PATH_WORKSPACE_CONFIGURATION
    ];

    /**
     * @param WriterInterface $configWriter
     * @param TypeListInterface $appCache
     * @param EncryptorInterface $encryptor
     * @param WorkspaceConfigurationJsonCleanerInterface $jsonCleaner
     * @param array $validators
     */
    public function __construct(
        private WriterInterface $configWriter,
        private TypeListInterface $appCache,
        private EncryptorInterface $encryptor,
        private WorkspaceConfigurationJsonCleanerInterface $jsonCleaner,
        private array $validators
    ) {
    }

    /**
     * Updates eventing configuration and clean the config type cache
     *
     * @param ConfigurationInterface $config
     * @return bool
     * @throws ValidatorException
     */
    public function update(ConfigurationInterface $config): bool
    {
        $data = $config->getData();
        foreach ($data as $configPath => $value) {
            if (isset($this->validators[$configPath])) {
                array_map(
                    fn (ValidatorInterface $validator) => $validator->validate($value),
                    $this->validators[$configPath]
                );
            }
        }

        foreach ($data as $configPath => $value) {
            if ($configPath === Config::CONFIG_PATH_WORKSPACE_CONFIGURATION && !empty($value)) {
                $value = $this->jsonCleaner->clean($value);
            }
            if (in_array($configPath, $this->encryptedValues)) {
                $value = $this->encryptor->encrypt($value);
            }
            if ($configPath === Config::CONFIG_PATH_ENABLED) {
                $value = $value ? 1 : 0;
            }
            $this->configWriter->save($configPath, $value);
        }
        $this->appCache->cleanType('config');

        return true;
    }
}
