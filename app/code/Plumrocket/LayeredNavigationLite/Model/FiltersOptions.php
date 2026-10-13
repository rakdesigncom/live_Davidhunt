<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2021 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface;
use Plumrocket\LayeredNavigationLite\Api\FiltersOptionsInterface;

/**
 * @since 1.0.0
 */
class FiltersOptions implements FiltersOptionsInterface
{

    public const ATTRIBUTES_CACHE_IDENTIFIER = 'product_filter_attribute_cache';

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * @var array|null
     */
    private $options;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface
     */
    private $filterMetaRepository;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\FilterOption\CollectorInterface[]
     */
    private $filterOptionCollectors;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Framework\App\CacheInterface                               $cache
     * @param \Magento\Framework\Serialize\SerializerInterface                    $serializer
     * @param \Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface $filterMetaRepository
     * @param \Magento\Store\Model\StoreManagerInterface                          $storeManager
     * @param array                                                               $filterOptionCollectors
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        FilterMetaRepositoryInterface $filterMetaRepository,
        StoreManagerInterface $storeManager,
        array $filterOptionCollectors = []
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->filterMetaRepository = $filterMetaRepository;
        $this->storeManager = $storeManager;
        $this->filterOptionCollectors = $filterOptionCollectors;
    }

    /**
     * Get attribute options.
     *
     * @param string $requestVar
     * @return array
     * [
     *     'option_id' => [
     *         'code' => 'code',
     *         'label' => 'label',
     *     ],
     * ]
     */
    public function getOptions(string $requestVar): array
    {
        return $this->getAllOptions()[$requestVar] ?? [];
    }

    /**
     * Get attribute option id by its escaped label.
     *
     * @param string $requestVar
     * @param string $optionCode
     * @return int|string
     */
    public function toOptionValue(string $requestVar, string $optionCode)
    {
        $filterOptions = $this->getOptions($requestVar);
        foreach ($filterOptions as $optionId => $filterOption) {
            if ($optionCode === $filterOption['code']) {
                return $optionId;
            }
        }
        return 0;
    }

    /**
     * Get attribute option code by its id.
     *
     * @param string     $requestVar
     * @param int|string $optionValue
     * @return string
     */
    public function toOptionCode(string $requestVar, $optionValue): string
    {
        try {
            $filterMeta = $this->filterMetaRepository->get($requestVar);
            if ($filterMeta->isToolbarVariable()) {
                return $optionValue;
            }
        } catch (NoSuchEntityException $e) {
            return $optionValue;
        }

        $filterOptions = $this->getOptions($requestVar);
        foreach ($filterOptions as $optionId => $filterOption) {
            if ((string) $optionValue === (string) $optionId) {
                return $filterOption['code'];
            }
        }
        return '';
    }

    /**
     * Get attribute option label by its id.
     *
     * @param string     $requestVar
     * @param int|string $optionValue
     * @return string
     */
    public function toOptionLabel(string $requestVar, $optionValue): string
    {
        try {
            $filterMeta = $this->filterMetaRepository->get($requestVar);
            if ($filterMeta->isToolbarVariable()) {
                return (string) $optionValue;
            }
        } catch (NoSuchEntityException $e) {
            return (string) $optionValue;
        }

        $filterOptions = $this->getOptions($requestVar);
        foreach ($filterOptions as $optionId => $filterOption) {
            if ((string) $optionValue === (string) $optionId) {
                return $filterOption['label'];
            }
        }
        return '';
    }

    /**
     * Get category id by url key.
     *
     * @param string $urlKey
     * @return int
     */
    public function getCategoryId(string $urlKey): int
    {
        return (int) $urlKey;
    }

    /**
     * Get options for all filters.
     *
     * @return array[]
     */
    public function getAllOptions(): array
    {
        $this->collectOptionDetail();
        return $this->options;
    }

    /**
     * Collect options.
     *
     * @return void
     */
    private function collectOptionDetail(): void
    {
        if (null === $this->options) {
            if ($options = $this->fromCache()) {
                $this->options = $options;
                return;
            }

            $options = [];
            foreach ($this->filterOptionCollectors as $filterOptionCollector) {
                $options = $filterOptionCollector->collect($options);
            }

            $this->options = $options;
            $this->toCache($options);
        }
    }

    /**
     * Get options from cache.
     *
     * @return array
     */
    private function fromCache(): array
    {
        try {
            $identifier = self::ATTRIBUTES_CACHE_IDENTIFIER . '|' . $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            $identifier = self::ATTRIBUTES_CACHE_IDENTIFIER . '|' . 0;
        }

        $data = $this->cache->load($identifier);
        if (! $data) {
            return [];
        }
        return $this->serializer->unserialize($data);
    }

    /**
     * Save options to cache.
     *
     * @param array[] $options
     */
    private function toCache(array $options): void
    {
        try {
            $identifier = self::ATTRIBUTES_CACHE_IDENTIFIER . '|' . $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $e) {
            $identifier = self::ATTRIBUTES_CACHE_IDENTIFIER . '|' . 0;
        }

        $this->cache->save(
            $this->serializer->serialize($options),
            $identifier,
            [Config::CACHE_TAG],
            3600
        );
    }
}
