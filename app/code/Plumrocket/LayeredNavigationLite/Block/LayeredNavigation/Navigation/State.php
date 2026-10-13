<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Block\LayeredNavigation\Navigation;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\View\Element\Template\Context;
use Plumrocket\LayeredNavigationLite\Helper\Config;
use Plumrocket\LayeredNavigationLite\Model\CatalogSearch\IsSearchResultsPage;

class State extends \Magento\LayeredNavigation\Block\Navigation\State
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\CatalogSearch\IsSearchResultsPage
     */
    private $isSearchResultsPage;

    /**
     * @param \Magento\Framework\View\Element\Template\Context                          $context
     * @param \Magento\Catalog\Model\Layer\Resolver                                     $layerResolver
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config                           $config
     * @param \Plumrocket\LayeredNavigationLite\Model\CatalogSearch\IsSearchResultsPage $isSearchResultsPage
     * @param array                                                                     $data
     */
    public function __construct(
        Context $context,
        Resolver $layerResolver,
        Config $config,
        IsSearchResultsPage $isSearchResultsPage,
        array $data = []
    ) {
        parent::__construct($context, $layerResolver, $data);
        $this->config = $config;
        $this->isSearchResultsPage = $isSearchResultsPage;
    }

    /**
     * @inheritdoc
     */
    public function getTemplate()
    {
        if ($this->config->isModuleEnabled() && $this->getPlumTemplate()) {
            return $this->getPlumTemplate();
        }
        return parent::getTemplate();
    }

    /**
     * @inheritdoc
     */
    public function getClearUrl()
    {
        $clearUrl = parent::getClearUrl();

        if ($this->config->isModuleEnabled()) {
            $additionalParam = '';

            $toolbarVars = $this->config->getToolbarVars();
            foreach ($this->_request->getParams() as $param => $value) {
                if (in_array($param, $toolbarVars, true)) {
                    if ($this->isSearchResultsPage->execute($clearUrl)) {
                        $additionalParam .= '/' . $param . Config::FILTER_PARAM_SEPARATOR . $value;
                    } else {
                        $clearUrl .= '/' . $param . Config::FILTER_PARAM_SEPARATOR . $value;
                    }
                }

            }

            if (false !== strpos($clearUrl, 'amfinder')) {
                /** Integration with Amasty Product Parts Finder */
                $clearUrl = preg_replace(
                    '/(amfinder)\/.*?\/(.*?\/\?)/',
                    "$1{$additionalParam}{$this->config->getCategoryUrlSuffix()}?",
                    $clearUrl
                );
            } else {
                $clearUrl = preg_replace(
                    '/(catalogsearch\/result)\/.*?\/(.*?\/\?)/',
                    "$1{$additionalParam}{$this->config->getCategoryUrlSuffix()}?",
                    $clearUrl
                );
            }
        } elseif ($this->_request->getParam('q')) {
            $clearUrl = $this->getUrl('*/*/*', [ '_query' => ['q' => $this->_request->getParam('q')]]);
        }

        return $clearUrl;
    }
}
