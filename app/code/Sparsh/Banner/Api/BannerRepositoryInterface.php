<?php
/**
 * Interface BannerRepositoryInterface
 *
 * PHP version 8.2
 *
 * @category Sparsh
 * @package  Sparsh_Banner
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\Banner\Api;

/**
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
interface BannerRepositoryInterface
{
    /**
     * Retrieve Banner.
     *
     * @param int $bannerId
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($bannerId);

    /**
     * Delete Banner.
     *
     * @param \Sparsh\Banner\Api\Data\BannerInterface $banner
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Sparsh\Banner\Api\Data\BannerInterface $banner);

    /**
     * Delete Banner by ID.
     *
     * @param int $bannerId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($bannerId);

    /**
     * @return \Sparsh\Banner\Api\Data\BannerInterface[]
     */
    public function getCurrentBanner();
}
