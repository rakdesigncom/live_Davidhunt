<?php
/**
 * Class ReadHandler
 *
 * PHP version 8.2
 *
 * @category Sparsh
 * @package  Sparsh_Banner
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\Banner\Model\ResourceModel\Banner\Relation\Store;

use Sparsh\Banner\Model\ResourceModel\Banner;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;

/**
 * Class ReadHandler
 */
class ReadHandler implements ExtensionInterface
{
    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var Banner
     */
    protected $resourceBanner;

    /**
     * @param MetadataPool $metadataPool
     * @param Banner $resourceBanner
     */
    public function __construct(
        MetadataPool $metadataPool,
        Banner $resourceBanner
    ) {
        $this->metadataPool = $metadataPool;
        $this->resourceBanner = $resourceBanner;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        if ($entity->getId()) {
            $stores = $this->resourceBanner->lookupStoreIds((int)$entity->getId());
            $entity->setData('store_id', $stores);
        }
        return $entity;
    }
}
