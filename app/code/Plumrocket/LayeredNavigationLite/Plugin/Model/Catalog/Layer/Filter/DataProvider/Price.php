<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\Plugin\Model\Catalog\Layer\Filter\DataProvider;

use Plumrocket\LayeredNavigationLite\Helper\Config;

class Price
{
    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Skip max price modification
     *
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\Price $subject
     * @param mixed                                                  $result
     * @return mixed
     */
    public function afterValidateFilter(\Magento\Catalog\Model\Layer\Filter\DataProvider\Price $subject, $result)
    {
        if (false === $result || ! $this->config->isModuleEnabled()) {
            return $result;
        }

        $count = is_array($result) ? count($result) : count(explode('-', (string) $result));

        if ($count !== 2) {
            return $result;
        }

        if (! defined('Magento\CatalogSearch\Model\Layer\Filter\Price::PRICE_DELTA')) {
            return $result;
        }

        if (! empty($result[1])) {
            $result[1] += \Magento\CatalogSearch\Model\Layer\Filter\Price::PRICE_DELTA;
        }

        return $result;
    }
}
