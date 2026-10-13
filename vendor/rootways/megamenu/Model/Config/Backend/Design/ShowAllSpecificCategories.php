<?php
/**
 * Mega Menu Show All Categories Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Config\Backend\Design;

class ShowAllSpecificCategories implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Rootways\Megamenu\Helper\Data
     */
    protected $customHelper;

    /**
     * @param \Rootways\Megamenu\Helper\Data $customHelper
     */
    public function __construct(
        \Rootways\Megamenu\Helper\Data $customHelper
    ) {
        $this->customHelper = $customHelper;
    }

    public function toOptionArray()
    {
        $category = $this->customHelper->getCategory($this->customHelper->getRootCategoryId());
        $childrenCategories = $category->getChildrenCategories();
        $productAttributeCollection = [];
        foreach ($childrenCategories as $cat) {
            $productAttributeCollection[] = ['value' =>  $cat->getId(), 'label' => $cat->getName()];
        }
        return $productAttributeCollection;
    }
}
