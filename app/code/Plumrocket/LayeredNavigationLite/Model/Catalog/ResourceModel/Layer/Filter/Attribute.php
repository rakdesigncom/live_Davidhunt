<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Model\Catalog\ResourceModel\Layer\Filter;

class Attribute extends \Magento\Catalog\Model\ResourceModel\Layer\Filter\Attribute
{
    //Which attributes will use reseted category collection
    protected $skipFilterAttributes = ["color"];

    public function getCount(\Magento\Catalog\Model\Layer\Filter\FilterInterface $filter)
    {
        $attribute = $filter->getAttributeModel();
        $attributeCode = $attribute->getAttributeCode();
        $storeId = $filter->getStoreId();
        $connection = $this->getConnection();
        $tableAlias = sprintf('%s_idx', $attributeCode);

        $select = clone $filter->getLayer()->getProductCollection()->getSelect();

        if (in_array($attributeCode, $this->skipFilterAttributes)) {

            $from = $select->getPart(\Magento\Framework\DB\Select::FROM);

            unset($from["search_result"]);

            $select->reset(\Magento\Framework\DB\Select::FROM);
            $select->setPart(\Magento\Framework\DB\Select::FROM, $from);
        }

        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->reset(\Magento\Framework\DB\Select::ORDER);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);

        $conditions = [
            "$tableAlias.entity_id = e.entity_id",
            $connection->quoteInto("$tableAlias.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("$tableAlias.store_id = ?", $storeId),
        ];

        $select->join(
            [$tableAlias => $this->getMainTable()],
            implode(' AND ', $conditions),
            ['value', 'count' => new \Zend_Db_Expr("COUNT($tableAlias.entity_id)")]
        )->group(
            "$tableAlias.value"
        );

        return $connection->fetchPairs($select);
    }
}
