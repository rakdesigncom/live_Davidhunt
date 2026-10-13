<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Plugin\Elasticsearch;

class Client
{

    /**
     * Fix condition in
     *
     * @param \Wyomind\Elasticsearch\Model\Client $subject
     * @param                                     $indices
     * @param                                     $types
     * @param array                               $params
     * @return array
     */
    public function beforeQuery(\Wyomind\Elasticsearch\Model\Client $subject, $indices, $types, array $params = [])
    {
        if (isset($params['body']['query']['filtered']['filter'])) {
            foreach ($params['body']['query']['filtered']['filter'] as $filterKey => $filterValue) {
                foreach ($filterValue as $andKey => $andValue) {
                    foreach ($andValue as $beforeTermKey => $beforeTermValue) {
                        foreach ($beforeTermValue as $termKey => $termValue) {
                            if (is_array($termValue)) {
                                foreach ($termValue as $afterTermKey => $afterTermValue) {
                                    if ($afterTermKey === 'in') {
                                        if (is_array($afterTermValue)) {
                                            $params['body']['query']['filtered']['filter'][$filterKey][$andKey][$beforeTermKey][$termKey] = implode(
                                                ',',
                                                $afterTermValue
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return [$indices, $types, $params];
    }
}
