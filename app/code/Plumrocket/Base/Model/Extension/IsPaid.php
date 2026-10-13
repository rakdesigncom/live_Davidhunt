<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Model\Extension;

use Magento\Config\Model\Config\Structure\Data as ConfigStructureData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Plumrocket\Base\Api\Data\ExtensionInformationInterface;
use Plumrocket\Base\Api\GetExtensionInformationInterface;

/**
 * @since 2.5.0
 */
class IsPaid
{
    /**
     * @var \Plumrocket\Base\Api\GetExtensionInformationInterface
     */
    private $getExtensionInformation;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ConfigStructureData
     */
    private $systemXmlStructure;

    /**
     * @param \Plumrocket\Base\Api\GetExtensionInformationInterface $getExtensionInformation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface    $scopeConfig
     * @param ConfigStructureData                                   $configStructureData
     */
    public function __construct(
        GetExtensionInformationInterface $getExtensionInformation,
        ScopeConfigInterface $scopeConfig,
        ConfigStructureData $configStructureData
    ) {
        $this->getExtensionInformation = $getExtensionInformation;
        $this->scopeConfig = $scopeConfig;
        $this->systemXmlStructure = $configStructureData;
    }

    /**
     * Check if the extension is paid.
     *
     * @param string $moduleName
     * @return bool
     */
    public function execute(string $moduleName): bool
    {
        $extensionInformation = $this->getExtensionInformation->execute($moduleName);
        return $this->hasSerialKey($extensionInformation) || $this->hasDeclaredSerialKeyField($extensionInformation);
    }

    /**
     * Check if the extension has empty or not serial key value.
     *
     * @param \Plumrocket\Base\Api\Data\ExtensionInformationInterface $extensionInformation
     * @return bool
     */
    private function hasSerialKey(ExtensionInformationInterface $extensionInformation): bool
    {
        $generalConfigurations = $this->scopeConfig->getValue(
            "{$extensionInformation->getConfigSection()}/general",
            ScopeInterface::SCOPE_STORE,
            0
        );
        return is_array($generalConfigurations) && array_key_exists('serial', $generalConfigurations);
    }

    /**
     * Check if the serial key field is exists in system.xml
     *
     * @param \Plumrocket\Base\Api\Data\ExtensionInformationInterface $extensionInformation
     * @return bool
     */
    private function hasDeclaredSerialKeyField(ExtensionInformationInterface $extensionInformation): bool
    {
        return (bool) $this->systemXmlStructure->get(
            "sections/{$extensionInformation->getConfigSection()}/children/general/children/serial"
        );
    }
}
