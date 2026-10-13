<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Model\Backup;

/**
 * Config for backups
 */
class Config
{
    public const XML_BACKUP_ITEMS = 'support/backup_items';

    public const OS_WIN_CODE = 'WIN';
    public const OS_OSX_CODE = 'DAR';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->objectManager = $objectManager;
    }

    /**
     * Get Backup Items from definition on config
     *
     * @return \Magento\Support\Model\Backup\AbstractItem[]
     */
    public function getBackupItems()
    {
        $objectItems = [];
        $items = (array) $this->scopeConfig->getValue(self::XML_BACKUP_ITEMS);
        foreach ($items as $key => $item) {
            $object = $this->objectManager->create($item['class']);
            $object->setData($item['params']);
            $objectItems[$key] = $object;
        }

        return $objectItems;
    }

    /**
     * Get file extension for backup of certain type
     *
     * @param string $type
     * @return string
     */
    public function getBackupFileExtension($type)
    {
        $path = '/' . $type . '/params/output_file_extension';
        $fileExtension = (string) $this->scopeConfig->getValue(self::XML_BACKUP_ITEMS . $path);

        return preg_replace('/[^a-zA-Z0-9.]/', '', $fileExtension);
    }

    /**
     * Return Unsupported OS
     *
     * @return string
     */
    public function getUnsupportedOs()
    {
        $result = '';
        $os = [
            self::OS_WIN_CODE => __('Windows'),
            self::OS_OSX_CODE => __('OS X'),
        ];

        foreach ($os as $osCode => $osName) {
            if (stristr(PHP_OS, $osCode)) {
                $result = $osName;
            }
        }

        return $result;
    }
}
