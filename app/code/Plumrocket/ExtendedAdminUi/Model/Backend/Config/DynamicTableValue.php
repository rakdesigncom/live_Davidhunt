<?php
/**
 * @package     Plumrocket_ExtendedAdminUi
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\ExtendedAdminUi\Model\Backend\Config;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * @since 1.1.0
 */
class DynamicTableValue extends Value
{

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $config
     * @param \Magento\Framework\App\Cache\TypeListInterface               $cacheTypeList
     * @param \Magento\Framework\Serialize\SerializerInterface             $serializer
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SerializerInterface $serializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection);
        $this->serializer = $serializer;
    }

    /**
     * Prepare field value after load.
     *
     * @return \Plumrocket\ExtendedAdminUi\Model\Backend\Config\DynamicTableValue
     */
    public function afterLoad()
    {
        $value = $this->getValue() ?? '{}';
        $value = $this->serializer->unserialize((string) $value);
        $value = $this->prepareTableFieldValue($value);
        $this->setValue($value);
        return parent::afterLoad();
    }

    /**
     * Prepare field value before save.
     *
     * @return \Plumrocket\ExtendedAdminUi\Model\Backend\Config\DynamicTableValue
     */
    public function beforeSave()
    {
        $value = $this->prepareTableFieldValue($this->getValue());
        $value = $this->serializer->serialize($value);
        $this->setValue($value);
        return parent::beforeSave();
    }

    /**
     * Prepare the table field value, it must be an array type
     *
     * @param array|string $value
     * @return array
     */
    protected function prepareTableFieldValue($value): array
    {
        if (empty($value) && ! is_array($value)) {
            $value = [];
        }
        return $value;
    }
}
