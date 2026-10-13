<?php
/**
 * Mega Menu CustomMenuCategory Block.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Block\Adminhtml\System\Config;

/**
 * CustomMenuCategory
 */
class CustomMenuCategory extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_categoryHelper;

    /**
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_categoryHelper = $categoryHelper;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }

    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $cat = $this->getStoreCategories(true, false, true);
            $categories = $this->_categoryCollectionFactory->create();
            $categories->addAttributeToSelect('*');
            $categories->addIsActiveFilter();
            $categories->addLevelFilter(2);
            $this->addOption('', '-- Select --');
            $catArray = [];
            foreach ($cat as $catData) {
                $catArray[] = ['label' => 'Before - '. $catData->getName(), 'value' => $catData->getId()];
            }
            $output = [];
            $align = [
                 [
                    'label' => 'At the End (Default)',
                    'value' => 'default',
                 ],
                 [
                    'label' => 'Align Right',
                    'value' => 'right',
                 ],
                 [
                    'label' => 'Align Left',
                    'value' => 'left',
                 ]
            ];
            $output[] = ['label' => 'Left/Right Side', 'value' => $align];
            $output[] = ['label' => 'Category', 'value' => $catArray];

            $this->setOptions($output);
        }

        return parent::_toHtml();
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Retrieve current store categories
     *
     * @param $sorted
     * @param $asCollection
     * @param $toLoad
     * @return array|\Magento\Framework\Data\Tree\Node\Collection
     */
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted, $asCollection, $toLoad);
    }
}
