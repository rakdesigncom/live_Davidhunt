<?php
/**
 * Mega Menu Menutype Model.
 *
 * @category  Site Search & Navigation
 * @package   Rootways_Megamenu
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Megamenu\Model\Attribute;

class Menutype extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions()
    {
        $options = [];
        if (!$this->_options) {
            $options = [
                ['value' => '0', 'label' => __('Please Select')]
            ];

            $packageOption = ['label' => 'Dropdown Menu Type'];
            $themeOptions = [
                ['value' => '1', 'label' => __('Simple dropdown')],
                /*['value' => '2', 'label' => __('Dropdown With Mega Layout - BETA')],*/
                ['value' => '3', 'label' => __('Dropdown With Title')]
            ];
            $packageOption['value'] = $themeOptions;
            $options[] = $packageOption;

            $packageOption = ['label' => 'Half Width Menu Type'];
            $themeOptions0 = [
                ['value' => '4', 'label' => __('Half width')],
                ['value' => '5', 'label' => __('Half width - with sub category as title')],
                ['value' => '12', 'label' => __('Half width - Content Only')],
                ['value' => '15', 'label' => __('Half width - Image Only')]
            ];
            $packageOption['value'] = $themeOptions0;
            $options[] = $packageOption;

            $packageOption = ['label' => 'Full Width Menu Type'];
            $themeOptions1 = [
                ['value' => '6', 'label' => __('Full width')],
                ['value' => '7', 'label' => __('Full width - with sub category as title')],
                ['value' => '13', 'label' => __('Full width - Content Only')],
                ['value' => '16', 'label' => __('Full width - Image Only')]
            ];
            $packageOption['value'] = $themeOptions1;
            $options[] = $packageOption;

            $packageOption = ['label' => 'Tabbing Menu Type'];
            $themeOptions2 = [
                ['value' => '8', 'label' => __('Tabbing - Vertical')],
                ['value' => '9', 'label' => __('Tabbing - Horizontal')],
                ['value' => '11', 'label' => __('Multi Tabbing')]
            ];
            $packageOption['value'] = $themeOptions2;
            $options[] = $packageOption;

            $packageOption = ['label' => 'Product Menu Type'];
            $themeOptions3 = [
                ['value' => '10', 'label' => __('Products only')],
                ['value' => '14', 'label' => __('Category Products')]
            ];

            $packageOption['value'] = $themeOptions3;
            $options[] = $packageOption;

            $this->_options = $options;
        }
        return $options;
    }
}
