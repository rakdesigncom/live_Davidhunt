<?php
/**
 * @package     Plumrocket_LayeredNavigationLite
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\LayeredNavigationLite\Model\OptionSource;

use Magento\Framework\Exception\NoSuchEntityException;
use Plumrocket\Base\Model\OptionSource\AbstractSource;
use Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface;
use Plumrocket\LayeredNavigationLite\Model\FilterList;

/**
 * @since 1.0.0
 */
class Filters extends AbstractSource
{

    /**
     * @var \Plumrocket\LayeredNavigationLite\Model\FilterList
     */
    private $filterList;

    /**
     * @var \Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface
     */
    private $filterMetaRepository;

    /**
     * @param \Plumrocket\LayeredNavigationLite\Model\FilterList                  $filterList
     * @param \Plumrocket\LayeredNavigationLite\Api\FilterMetaRepositoryInterface $filterMetaRepository
     */
    public function __construct(
        FilterList $filterList,
        FilterMetaRepositoryInterface $filterMetaRepository
    ) {
        $this->filterList = $filterList;
        $this->filterMetaRepository = $filterMetaRepository;
    }

    /**
     * Get filter title positions.
     *
     * @return array
     */
    public function toOptionHash(): array
    {
        $options = ['all' => 'All Attributes'];
        foreach ($this->filterList->getAttributeList() as $code => $label) {
            try {
                if ($this->filterMetaRepository->get($code)->isAttribute()
                    || $this->filterMetaRepository->get($code)->isCategory()
                ) {
                    $options[$code] = $label;
                }
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }
        return $options;
    }
}
