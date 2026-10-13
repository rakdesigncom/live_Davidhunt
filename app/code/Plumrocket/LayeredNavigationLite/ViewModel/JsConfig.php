<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

namespace Plumrocket\LayeredNavigationLite\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Plumrocket\LayeredNavigationLite\Helper\Config;
use Plumrocket\LayeredNavigationLite\Model\Variable\Registry;

/**
 * @since 1.0.0
 */
class JsConfig implements ArgumentInterface
{

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\Variable\Registry
     */
    private $variableRegistry;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepo;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Helper\Config
     */
    private $config;

    /**
     * @param \Magento\Framework\Serialize\SerializerInterface          $serializer
     * @param \Magento\Framework\UrlInterface                           $urlBuilder
     * @param \Plumrocket\LayeredNavigationLite\Model\Variable\Registry $variableRegistry
     * @param \Magento\Framework\View\Asset\Repository                  $assetRepo
     * @param \Magento\Framework\App\RequestInterface                   $request
     * @param \Plumrocket\LayeredNavigationLite\Helper\Config           $config
     */
    public function __construct(
        SerializerInterface $serializer,
        UrlInterface $urlBuilder,
        Registry $variableRegistry,
        Repository $assetRepo,
        RequestInterface $request,
        Config  $config
    ) {
        $this->serializer = $serializer;
        $this->urlBuilder = $urlBuilder;
        $this->variableRegistry = $variableRegistry;
        $this->assetRepo = $assetRepo;
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * Get serialized params for product filter js.
     *
     * @return string
     */
    public function getJson(): string
    {
        return $this->serializer->serialize($this->get());
    }

    /**
     * Get params for product filter js.
     *
     * @return array
     */
    public function get(): array
    {
        $variables = $this->variableRegistry->get();

        return [
            'auto' => true,
            'cleanUrl' => $this->getCurrentUrl($variables),
            'variables' => $this->convertValuesToStrings($variables),
            'loader' => [
                'icon' => $this->getViewFileUrl('images/loader-1.gif'),
            ],
            'scrollUp' => $this->config->shouldScrollUpAfterUpdate(),
            'scrollUpTo' => $this->config->getScrollUpSelector(),
        ];
    }

    /**
     * Retrieve formatted current url
     *
     * @param array $variables
     * @return string
     */
    public function getCurrentUrl(array $variables = []): string
    {
        $params = ['_current' => false, '_use_rewrite' => true, 'p' => null];
        $params += array_fill_keys(array_keys($variables), null);
        $url = $this->urlBuilder->getUrl('*/*/*', $params);
        return str_replace('catalogsearch/result/index', 'catalogsearch/result', $url);
    }

    /**
     * Convert all values to string to simplify comparison in js.
     *
     * @param array $variables
     * @return array
     */
    private function convertValuesToStrings(array $variables): array
    {
        $variables = array_map(static function ($values) {
            return array_map('strval', $values);
        }, $variables);

        if (isset($variables['price'])) {
            $variables['price'] = array_map(static function ($value) {
                return str_replace('-', '_', $value);
            }, $variables['price']);
        }

        return $variables;
    }

    /**
     * Get static file url.
     *
     * @param string $fileId
     * @return string
     */
    private function getViewFileUrl(string $fileId): string
    {
        try {
            return $this->assetRepo->getUrlWithParams($fileId, ['_secure' => $this->request->isSecure()]);
        } catch (LocalizedException $e) {
            return '';
        }
    }
}
