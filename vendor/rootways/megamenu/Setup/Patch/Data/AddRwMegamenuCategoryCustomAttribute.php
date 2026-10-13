<?php
declare (strict_types = 1);

namespace Rootways\Megamenu\Setup\Patch\Data;


use Magento\Catalog\Helper\DefaultCategoryFactory;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CustomerAttribute
 */
class AddRwMegamenuCategoryCustomAttribute implements DataPatchInterface, PatchRevertableInterface
{
   /**
    * @var ModuleDataSetupInterface
    */
   private $moduleDataSetup;

   /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * @var DefaultCategoryFactory
     */
    private $defaultCategoryFactory;


    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory,
        \Magento\Catalog\Helper\DefaultCategoryFactory $defaultCategoryFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->defaultCategoryFactory = $defaultCategoryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->addCategoryCustomAttributes();
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function addCategoryCustomAttributes()
    {
        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Category::ENTITY);
        $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

        $menu_attributes = [
            'megamenu_type' => [
                'type' => 'varchar',
                'label' => 'Mega Menu Type',
                'input' => 'select',
                'source' => 'Rootways\Megamenu\Model\Attribute\Menutype',
                'required' => false,
                'sort_order' => 10,
                'input_renderer' => 'Rootways\Megamenu\Block\Adminhtml\Category\Helper\Dependency',
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_type_numofcolumns' => [
                'type' => 'varchar',
                'label' => 'Sub-category Columns',
                'input' => 'select',
                'source' => 'Rootways\Megamenu\Model\Attribute\Numberofcol',
                'required' => false,
                'sort_order' => 20,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_show_catimage' => [
                'type' => 'int',
                'label' => 'Show Category Image',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required' => false,
                'sort_order' => 30,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_show_catimage_img' => [
                'type' => 'varchar',
                'label' => 'Category Image',
                'input' => 'image',
                'backend' => 'Magento\Catalog\Model\Category\Attribute\Backend\Image',
                'required' => false,
                'sort_order' => 40,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_show_catimage_width' => [
                'type' => 'varchar',
                'label' => 'Category Image Width',
                'input' => 'text',
                'required' => false,
                'sort_order' => 50,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_show_catimage_height' => [
                'type' => 'varchar',
                'label' => 'Category Image Height',
                'input' => 'text',
                'required' => false,
                'sort_order' => 60,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_type_leftblock' => [
                'type' => 'text',
                'label' => 'Left Side Content Block',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 70,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_type_leftblock_w' => [
                'type' => 'varchar',
                'label' => 'Left Side Block Width',
                'input' => 'select',
                'source' => 'Rootways\Megamenu\Model\Attribute\ContentBlockWidth',
                'required' => false,
                'sort_order' => 80,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_type_rightblock' => [
                'type' => 'text',
                'label' => 'Right Side Content Block',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 90,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_type_rightblock_w' => [
                'type' => 'varchar',
                'label' => 'Right Side Block Width',
                'input' => 'select',
                'source' => 'Rootways\Megamenu\Model\Attribute\ContentBlockWidth',
                'required' => false,
                'sort_order' => 100,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_type_header' => [
                'type' => 'text',
                'label' => 'Header Content Block',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 110,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
             'megamenu_type_footer' => [
                'type' => 'text',
                'label' => 'Footer Content Block',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 120,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_type_labeltx' => [
                'type' => 'varchar',
                'label' => 'Label Text',
                'input' => 'text',
                'required' => false,
                'sort_order' => 130,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_type_labelclr' => [
                'type' => 'varchar',
                'label' => 'Label Color',
                'input' => 'text',
                'required' => false,
                'sort_order' => 140,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
            'megamenu_type_class' => [
                'type' => 'varchar',
                'label' => 'Custom Class',
                'input' => 'text',
                'required' => false,
                'sort_order' => 150,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
            ],
             'megamenu_type_half_pos' => [
                'type' => 'varchar',
                'label' => 'Dropdown Position',
                'input' => 'select',
                'source' => 'Rootways\Megamenu\Model\Attribute\Dropdownpos',
                'required' => false,
                'sort_order' => 160,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
             ],
            'megamenu_type_viewmore' => [
                'type' => 'varchar',
                'label' => 'Add View More Link After',
                'input' => 'text',
                'required' => false,
                'sort_order' => 170,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
             ],
             'megamenu_type_subcatlevel' => [
                'type' => 'varchar',
                'label' => 'Level of Sub Categories to be Display',
                'input' => 'select',
                'source' => 'Rootways\Megamenu\Model\Attribute\SubCatLevel',
                'required' => false,
                'sort_order' => 180,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
             ],
            'megamenu_type_showtitle' => [
                'type' => 'int',
                'label' => 'Show Title With Image',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required' => false,
                'sort_order' => 190,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
             ],
             'megamenu_type_subcol' => [
                'type' => 'varchar',
                'label' => 'Number of Columns Under One Title',
                'input' => 'select',
                'source' => 'Rootways\Megamenu\Model\Attribute\Numberofcol',
                'required' => false,
                'sort_order' => 200,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
             ],
             'megamenu_type_imgpos' => [
                'type' => 'varchar',
                'label' => 'Image Position',
                'input' => 'select',
                'source' => 'Rootways\Megamenu\Model\Attribute\ImgPosition',
                'required' => false,
                'sort_order' => 210,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Rootways Menu'
             ]
        ];

        foreach ($menu_attributes as $item => $data) {
            $categorySetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, $item, $data);
        }
        $idg =  $categorySetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'Rootways Mega Menu');
        foreach ($menu_attributes as $item => $data) {
            $categorySetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $idg,
                $item,
                $data['sort_order']
            );
        }
   }

   /**
    * {@inheritdoc}
    */
   public static function getDependencies()
   {
       return [];
   }

   /**
    *
    */
   public function revert()
   {
   }

   /**
    * {@inheritdoc}
    */
   public function getAliases()
   {
       return [];
   }
}
