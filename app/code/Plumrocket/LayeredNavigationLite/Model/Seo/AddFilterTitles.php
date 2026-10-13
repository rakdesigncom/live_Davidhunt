<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\Seo;

use Magento\Framework\Exception\NoSuchEntityException;
use Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface;
use Plumrocket\LayeredNavigationLite\Api\FiltersOptionsInterface;
use Plumrocket\LayeredNavigationLite\Model\OptionSource\AbstractTitlePosition;
use Plumrocket\LayeredNavigationLite\Model\Variable\Registry;

/**
 * @since 1.0.0
 */
class AddFilterTitles
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\Variable\Registry
     */
    private $variableRegistry;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Api\FiltersOptionsInterface
     */
    private $filtersOptions;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface
     */
    private $filterMetaRepository;

    /**
     * @param \Plumrocket\LayeredNavigationLite\Model\Variable\Registry           $variableRegistry
     * @param \Plumrocket\LayeredNavigationLite\Api\FiltersOptionsInterface       $filtersOptions
     * @param \Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface $filterMetaRepository
     */
    public function __construct(
        Registry $variableRegistry,
        FiltersOptionsInterface $filtersOptions,
        FilterMetaRepositoryInterface $filterMetaRepository
    ) {
        $this->variableRegistry = $variableRegistry;
        $this->filtersOptions = $filtersOptions;
        $this->filterMetaRepository = $filterMetaRepository;
    }

    /**
     * Add active filter titles to title.
     *
     * @param string $title
     * @param string $position
     * @param array  $allowList
     * @param string $separator
     * @return string
     */
    public function execute(
        string $title,
        string $position,
        array $allowList,
        string $separator
    ): string {
        if (AbstractTitlePosition::POSITION_NONE === $position) {
            return $title;
        }

        $titles = $this->toTitles($this->variableRegistry->get(), $allowList);
        if (! $titles) {
            return $title;
        }

        if (AbstractTitlePosition::POSITION_BEFORE === $position) {
            $titles[] = $title;
        } else {
            array_unshift($titles, $title);
        }

        return implode($separator, $titles);
    }

    /**
     * Make titles from active filters.
     *
     * @param array $variables
     * @param array $allowList
     * @return string[]
     */
    private function toTitles(array $variables, array $allowList): array
    {
        $allowAll = in_array('all', $allowList, true);
        if (! $allowAll && in_array('category', $allowList, true)) {
            $allowList[] = 'cat';
        }

        $titles = [];
        foreach ($variables as $variable => $values) {
            try {
                if (! $this->filterMetaRepository->get($variable)->isAttribute()
                    && ! $this->filterMetaRepository->get($variable)->isCategory()
                ) {
                    continue;
                }
            } catch (NoSuchEntityException $e) {
                continue;
            }

            if (! $allowAll && ! in_array($variable, $allowList, true)) {
                continue;
            }
            foreach ($values as $value) {
                $titles[] = $this->filtersOptions->toOptionLabel($variable, $value);
            }
        }
        return array_filter($titles);
    }
}
