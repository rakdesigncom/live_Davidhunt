<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\Seo;

use Magento\Framework\View\Page\Config as PageConfig;
use Plumrocket\LayeredNavigationLite\Helper\Config\Seo;

/**
 * @since 1.0.0
 */
class MetaTitleResolver
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config\Seo
     */
    private $seoConfig;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\Seo\AddFilterTitles
     */
    private $addFilterTitles;

    /**
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config\Seo         $seoConfig
     * @param \Plumrocket\LayeredNavigationLite\Model\Seo\AddFilterTitles $addFilterTitles
     */
    public function __construct(
        Seo $seoConfig,
        AddFilterTitles $addFilterTitles
    ) {
        $this->seoConfig = $seoConfig;
        $this->addFilterTitles = $addFilterTitles;
    }

    /**
     * Get pae titles with active filter titles.
     *
     * @param \Magento\Framework\View\Page\Config $pageConfig
     * @return string
     */
    public function resolve(PageConfig $pageConfig): string
    {
        return $this->addFilterTitles->execute(
            $pageConfig->getTitle()->get(),
            $this->seoConfig->getFilterMetaTitlePosition(),
            $this->seoConfig->getMetaTitleFilters(),
            $this->seoConfig->getFilterMetaTitleSeparator()
        );
    }
}
