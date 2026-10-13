<?php
/************************************************************************
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
 * ***********************************************************************
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Fixture;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\AttributeFactory;
use Magento\Framework\DataObject;
use Magento\Rma\Api\RmaAttributesManagementInterface;
use Magento\Rma\Model\Item\Attribute;
use Magento\Rma\Model\ResourceModel\Item\Attribute as ResourceModelAttribute;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class RmaItemAttribute implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'entity_type_id' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
        'attribute_id' => null,
        'attribute_code' => 'attribute%uniqid%',
        'default_frontend_label' => 'Attribute%uniqid%',
        'frontend_labels' => [],
        'frontend_input' => 'text',
        'backend_type' => 'varchar',
        'is_required' => false,
        'is_user_defined' => true,
        'note' => null,
        'backend_model' => null,
        'source_model' => null,
        'default_value' => null,
        'is_unique' => '0',
        'frontend_class' => null,
        'sort_order' => 0,
        'attribute_set_id' => RmaAttributesManagementInterface::ATTRIBUTE_SET_ID,
        'attribute_group_id' => 1,
        'input_filter' => null,
        'multiline_count' => 0,
        'validate_rules' => null,
    ];

    /**
     * @var DataMerger
     */
    private DataMerger $dataMerger;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $processor;

    /**
     * @var AttributeFactory
     */
    private AttributeFactory $attributeFactory;

    /**
     * @var ResourceModelAttribute
     */
    private ResourceModelAttribute $resourceModelAttribute;

    /**
     * @var AttributeRepositoryInterface
     */
    private AttributeRepositoryInterface $attributeRepository;

    /**
     * @param DataMerger $dataMerger
     * @param ProcessorInterface $processor
     * @param AttributeRepositoryInterface $attributeRepository
     * @param AttributeFactory $attributeFactory
     * @param ResourceModelAttribute $resourceModelAttribute
     */
    public function __construct(
        DataMerger $dataMerger,
        ProcessorInterface $processor,
        AttributeRepositoryInterface $attributeRepository,
        AttributeFactory $attributeFactory,
        ResourceModelAttribute $resourceModelAttribute
    ) {
        $this->dataMerger = $dataMerger;
        $this->processor = $processor;
        $this->attributeFactory = $attributeFactory;
        $this->resourceModelAttribute = $resourceModelAttribute;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        /** @var Attribute $attr */
        $attr = $this->attributeFactory->createAttribute(Attribute::class, self::DEFAULT_DATA);
        $mergedData = $this->processor->process($this, $this->dataMerger->merge(self::DEFAULT_DATA, $data));
        $attr->setData($mergedData);
        $this->resourceModelAttribute->save($attr);
        return $attr;
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $this->attributeRepository->deleteById($data['attribute_id']);
    }
}
