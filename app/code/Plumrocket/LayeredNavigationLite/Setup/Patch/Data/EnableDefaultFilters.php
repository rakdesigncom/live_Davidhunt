<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Setup\Patch\Data;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @since 1.0.0
 */
class EnableDefaultFilters implements DataPatchInterface
{
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $filterableAttributes;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    private $resourceConfig;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface                        $moduleDataSetup
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $filterableAttributes
     * @param \Magento\Config\Model\ResourceModel\Config                               $resourceConfig
     * @param \Magento\Framework\Serialize\SerializerInterface                         $serializer
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CollectionFactory $filterableAttributes,
        Config $resourceConfig,
        SerializerInterface $serializer
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->filterableAttributes = $filterableAttributes;
        $this->resourceConfig = $resourceConfig;
        $this->serializer = $serializer;
    }

    /**
     * Apply patch
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $collection = $this->filterableAttributes->create();
        $collection->setItemObjectClass(Attribute::class)
                   ->setOrder('position', 'ASC');

        $collection->addIsFilterableFilter();

        $attributeCodes =[];
        foreach ($collection as $attribute) {
            if ($attribute->getFrontendLabel()) {
                $attributeCodes[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
            }
        }

        $value = $this->serializer->serialize($attributeCodes);
        $this->resourceConfig->saveConfig(
            \Plumrocket\LayeredNavigationLite\Helper\Config\Attribute::XML_PATH_ATTRS,
            $value,
            'default'
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
