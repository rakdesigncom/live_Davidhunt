<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Block\Swatches\LayeredNavigation;

use Magento\Catalog\Model\Layer\Filter\Item as FilterItem;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Option;
use Plumrocket\LayeredNavigationLite\Api\FilterItemUrlBuilderInterface;
use Plumrocket\LayeredNavigationLite\Helper\Config;
use Plumrocket\LayeredNavigationLite\Model\Variable\Value\Slugify;

class RenderLayered extends \Magento\Swatches\Block\LayeredNavigation\RenderLayered
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Api\FilterItemUrlBuilderInterface
     */
    private $filterItemUrlBuilder;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\Variable\Value\Slugify
     */
    private $slugify;

    /**
     * @param \Magento\Framework\View\Element\Template\Context                    $context
     * @param \Magento\Eav\Model\Entity\Attribute                                 $eavAttribute
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory  $layerAttribute
     * @param \Magento\Swatches\Helper\Data                                       $swatchHelper
     * @param \Magento\Swatches\Helper\Media                                      $mediaHelper
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config                     $config
     * @param \Plumrocket\LayeredNavigationLite\Api\FilterItemUrlBuilderInterface $filterItemUrlBuilder
     * @param \Plumrocket\LayeredNavigationLite\Model\Variable\Value\Slugify      $slugify
     * @param array                                                               $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Attribute $eavAttribute,
        AttributeFactory $layerAttribute,
        \Magento\Swatches\Helper\Data $swatchHelper,
        \Magento\Swatches\Helper\Media $mediaHelper,
        Config $config,
        FilterItemUrlBuilderInterface $filterItemUrlBuilder,
        Slugify $slugify,
        array $data = []
    ) {
        $this->config = $config;
        $this->filterItemUrlBuilder = $filterItemUrlBuilder;
        parent::__construct(
            $context,
            $eavAttribute,
            $layerAttribute,
            $swatchHelper,
            $mediaHelper,
            $data
        );
        $this->slugify = $slugify;
    }

    /**
     * Get attribute swatch data
     *
     * @return array
     */
    public function getSwatchData()
    {
        $data = parent::getSwatchData();
        if (! $this->config->isModuleEnabled()) {
            return $data;
        }

        $attributeOptions = $data['options'];
        $swatches = $data['swatches'];

        $newAttributeOptions = [];
        $newSwatches = [];

        foreach ($this->eavAttribute->getOptions() as $option) {
            if (isset($attributeOptions[$option->getValue()])) {
                $newAttributeOptions[$this->slugify->execute($option->getValue())]
                    = $attributeOptions[$option->getValue()];
            }
            if (isset($swatches[$option->getValue()])) {
                $newSwatches[$this->slugify->execute($option->getValue())]
                    = $swatches[$option->getValue()];
            }
        }

        $data['options'] = $newAttributeOptions;
        $data['swatches'] = $newSwatches;

        return $data;
    }

    /**
     * Build filter option url.
     *
     * @param string $attributeCode
     * @param string $optionId
     * @return string
     */
    public function buildUrl($attributeCode, $optionId)
    {
        if ($this->config->isModuleEnabled()) {
            return $this->filterItemUrlBuilder->toggleFilterUrl($attributeCode, (string) $optionId);
        }
        return parent::buildUrl($attributeCode, $optionId);
    }

    /**
     * @inheritDoc
     */
    protected function getOptionViewData(FilterItem $filterItem, Option $swatchOption)
    {
        if (! $this->config->isModuleEnabled()) {
            return parent::getOptionViewData($filterItem, $swatchOption);
        }

        if ($this->isOptionDisabled($filterItem)) {
            $link = 'javascript:void();';
            $style = 'disabled';
        } else {
            $style = '';
            $link = $this->buildUrl($this->eavAttribute->getAttributeCode(), $filterItem->getValueString());
        }
        return [
            'link' => $link,
            'custom_style' => $style,
            'label' => $swatchOption->getLabel()
        ];
    }
}
