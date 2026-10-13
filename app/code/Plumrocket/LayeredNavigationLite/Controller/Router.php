<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Controller;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Plumrocket\LayeredNavigationLite\Api\GetUrlVariablesInterface;
use Plumrocket\LayeredNavigationLite\Helper\Config;
use Plumrocket\LayeredNavigationLite\Helper\Config\Seo;
use Plumrocket\LayeredNavigationLite\Model\AjaxRequestLocator;
use Plumrocket\LayeredNavigationLite\Model\OptionSource\InsertFiltersIn;
use Plumrocket\LayeredNavigationLite\Model\Variable\Params\Processor as ParamsProcessor;
use Plumrocket\LayeredNavigationLite\Model\Variable\Path\Processor as PathProcessor;
use Plumrocket\LayeredNavigationLite\Model\Variable\Registry;
use Plumrocket\LayeredNavigationLite\Model\Variable\Value;

/**
 * @since 1.0.0
 */
class Router implements RouterInterface
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\Variable\GetList
     */
    private $getUrlVariables;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\Variable\Value
     */
    private $variableValue;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\Variable\Path\Processor
     */
    private $pathProcessor;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\Variable\Registry
     */
    private $variableRegistry;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\Variable\Params\Processor
     */
    private $paramsProcessor;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\AjaxRequestLocator
     */
    private $ajaxRequestLocator;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config\Seo
     */
    private $seoConfig;

    /**
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config                   $config
     * @param \Plumrocket\LayeredNavigationLite\Api\GetUrlVariablesInterface    $getUrlVariables
     * @param \Plumrocket\LayeredNavigationLite\Model\Variable\Value            $variableValue
     * @param \Plumrocket\LayeredNavigationLite\Model\Variable\Path\Processor   $pathProcessor
     * @param \Plumrocket\LayeredNavigationLite\Model\Variable\Registry         $variableRegistry
     * @param \Plumrocket\LayeredNavigationLite\Model\Variable\Params\Processor $paramsProcessor
     * @param \Plumrocket\LayeredNavigationLite\Model\AjaxRequestLocator        $ajaxRequestLocator
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config\Seo               $seoConfig
     */
    public function __construct(
        Config $config,
        GetUrlVariablesInterface $getUrlVariables,
        Value $variableValue,
        PathProcessor $pathProcessor,
        Registry $variableRegistry,
        ParamsProcessor $paramsProcessor,
        AjaxRequestLocator $ajaxRequestLocator,
        Seo $seoConfig
    ) {
        $this->config = $config;
        $this->getUrlVariables = $getUrlVariables;
        $this->variableValue = $variableValue;
        $this->pathProcessor = $pathProcessor;
        $this->variableRegistry = $variableRegistry;
        $this->paramsProcessor = $paramsProcessor;
        $this->ajaxRequestLocator = $ajaxRequestLocator;
        $this->seoConfig = $seoConfig;
    }

    /**
     * Parse, convert and move filters variables.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return void
     */
    public function match(RequestInterface $request): void
    {
        if (! $request instanceof Request
            || ! $this->config->isModuleEnabled()
            || $this->ajaxRequestLocator->isActive()
        ) {
            return;
        }

        if ($request->getParam('prfilter_ajax')) {
            $this->handleAjaxRequest($request);
            return;
        }

        $this->handlePageRequest($request);
    }

    /**
     * Process product filter ajax request.
     *
     * @param \Magento\Framework\HTTP\PhpEnvironment\Request $request
     */
    private function handleAjaxRequest(Request $request): void
    {
        $this->ajaxRequestLocator->setActive(true);
        $variables = $this->getUrlVariables->getFromAjaxParams($request->getParam('prfilter_variables', []));
        $variables = $this->variableValue->preparePriceValues($variables);
        $this->variableRegistry->set($variables);
        $this->paramsProcessor->moveToParams($request, $variables);
    }

    /**
     * Process product filter regular request.
     *
     * @param \Magento\Framework\HTTP\PhpEnvironment\Request $request
     */
    private function handlePageRequest(Request $request): void
    {
        if (InsertFiltersIn::GET_PARAMS === $this->seoConfig->getInsertFiltersIn()) {
            $variables = $this->getUrlVariables->getFromParams($this->paramsProcessor->parseGetParams($request));
        } else {
            $variables = $this->getUrlVariables->get($request->getPathInfo());
        }

        if (! $variables) {
            return;
        }
        $variables = $this->variableValue->prepareVariableValues($variables);
        $this->variableRegistry->set($variables);
        $this->pathProcessor->moveToParams($request, $variables);
    }
}
