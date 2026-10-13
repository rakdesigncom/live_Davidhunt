<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\Variable\Params;

use Magento\Framework\HTTP\PhpEnvironment\Request;

/**
 * @since 1.0.0
 */
class Processor
{

    /**
     * Move variables from path to params.
     *
     * @param \Magento\Framework\HTTP\PhpEnvironment\Request $request
     * @param array                                          $variables
     */
    public function moveToParams(Request $request, array $variables): void
    {
        $request->setParam('prfilter_ajax', null);
        $request->setParam('prfilter_variables', null);
        $queryParams = $request->getQuery()->toArray();
        unset($queryParams['prfilter_ajax'], $queryParams['prfilter_variables']);

        // Laminas package can be missing in magento 2.3
        if (class_exists('\Laminas\Stdlib\Parameters')) {
            $request->setQuery(new \Laminas\Stdlib\Parameters($queryParams));
        } else {
            $request->setQuery(new \Zend\Stdlib\Parameters($queryParams));
        }

        $requestUri = parse_url($request->getRequestUri(), PHP_URL_PATH);
        if ($request->getParams()) {
            $requestUri .= '?' . http_build_query($request->getParams());
        }
        $request->setRequestUri($requestUri);

        foreach ($variables as $variable => $values) {
            $request->setParam($variable, implode(',', $values));
        }
    }

    /**
     * Parse only get params.
     *
     * @param \Magento\Framework\HTTP\PhpEnvironment\Request $request
     * @return array
     * @since 1.3.1
     */
    public function parseGetParams(Request $request): array
    {
        $query = parse_url($request->getRequestUri(), PHP_URL_QUERY);
        if (! $query) {
            return [];
        }
        parse_str($query, $getParams);
        return $getParams;
    }
}
