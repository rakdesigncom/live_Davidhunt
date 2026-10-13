<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2020 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Model\Utils;

use Magento\Store\Model\StoreManagerInterface;

/**
 * @since 2.3.1
 */
class GetRelativePathFromUrl
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Remove base url from giving url.
     *
     * @param string $url
     * @param bool   $removeGetParams
     * @param bool   $removeFragment
     * @return string
     */
    public function execute(string $url, bool $removeGetParams = true, bool $removeFragment = true): string
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();

        $url = str_replace(["\n", "\r"], '', $url);

        if ($removeGetParams && $this->hasParams($url)) {
            $paramsWithFragment = strstr($url, '?');
            if ($removeFragment) {
                $url = str_replace($paramsWithFragment, '', $url);
            } else {
                $url = str_replace(strstr($paramsWithFragment, '#', true), '', $url);
            }
        }

        if ($removeFragment && $this->hasFragment($url)) {
            $url = str_replace(strstr($url, '#'), '', $url);
        }

        $url = str_replace($baseUrl, '', $url);
        if ('' === $url || $url[0] !== '/') {
            $url = '/' . $url;
        }

        return $url;
    }

    /**
     * Check if url has get params.
     *
     * @param string $url
     * @return bool
     */
    private function hasParams(string $url): bool
    {
        return strpos($url, '?') !== false;
    }

    /**
     * Check if url has fragment (hash).
     *
     * @param string $url
     * @return bool
     */
    private function hasFragment(string $url): bool
    {
        return strpos($url, '#') !== false;
    }
}
