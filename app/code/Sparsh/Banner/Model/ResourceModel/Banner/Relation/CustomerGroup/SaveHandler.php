<?php
/**
 * Class SaveHandler
 *
 * PHP version 8.2
 *
 * @category Sparsh
 * @package  Sparsh_Banner
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\Banner\Model\ResourceModel\Banner\Relation\CustomerGroup;

use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Sparsh\Banner\Api\Data\BannerInterface;
use Sparsh\Banner\Model\ResourceModel\Banner;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class SaveHandler
 */
class SaveHandler implements ExtensionInterface
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
     * @throws \Exception
     */
    public function execute($entity, $arguments = [])
    {
        $entityMetadata = $this->metadataPool->getMetadata(BannerInterface::class);
        $linkField = $entityMetadata->getLinkField();

        $connection = $entityMetadata->getEntityConnection();

        $oldCustomer = $this->resourceBanner->lookupCustomerGroupIds((int)$entity->getId());

        $newCustomer = (array)$entity->getCustomerGroup();

        if (empty($newCustomer)) {
            $newCustomer = (array)$entity->getCustomerGroupId();
        }

        $table = $this->resourceBanner->getTable('sparsh_banner_customer_group');

        $delete = array_diff($oldCustomer, $newCustomer);

        if ($delete) {

            $where = [
                $linkField . ' = ?' => (int)$entity->getData($linkField),
                'customer_group_id IN (?)' => $delete,
            ];
            $connection->delete($table, $where);
        }

        $insert = array_diff($newCustomer, $oldCustomer);

        if ($insert) {

            $data = [];
            foreach ($insert as $customerGroupId) {
                $data[] = [
                    $linkField => (int)$entity->getData($linkField),
                    'customer_group_id' => (int)$customerGroupId
                ];
            }

            $connection->insertMultiple($table, $data);
        }

        return $entity;
    }
}
