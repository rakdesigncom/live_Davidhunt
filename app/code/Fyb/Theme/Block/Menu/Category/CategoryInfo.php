<?php

namespace Fyb\Theme\Block\Menu\Category;

use Magento\Framework\View\Element\Template;

class CategoryInfo extends \Magento\Catalog\Block\Category\View
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Category $categoryHelper,
        array $data = []
    ) {
        parent::__construct($context, $layerResolver, $registry, $categoryHelper, $data);
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getLevelMainCategory($category, $level)
    {
        if ($category->getLevel() <= $level) {
            return $category;
        }

        $parent = $category->getParentCategory();
        return $this->getLevelMainCategory($parent, $level);
    }
}
