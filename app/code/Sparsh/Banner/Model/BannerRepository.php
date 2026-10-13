<?php

namespace Sparsh\Banner\Model;

use Magento\Framework\App\ObjectManager;
use Sparsh\Banner\Api\BannerRepositoryInterface;
use Sparsh\Banner\Api\Data\BannerInterface;
use Sparsh\Banner\Model\BannerFactory;
use Sparsh\Banner\Model\ResourceModel\Banner\Collection as BannerCollection;
use Sparsh\Banner\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\SearchResultsInterfaceFactory;

class BannerRepository implements BannerRepositoryInterface
{
    protected $objectFactory;

    protected $dataBannerFactory;

    protected $dataObjectHelper;

    protected $dataObjectProcessor;

    protected $collectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Framework\Api\SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    public function __construct(
        BannerFactory $objectFactory,
        CollectionFactory $collectionFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        \Sparsh\Banner\Api\Data\BannerInterfaceFactory $dataBannerFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
    ) {
        $this->objectFactory = $objectFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataBannerFactory = $dataBannerFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->storeManager = $storeManager;
        $this->moduleManager = $moduleManager;
        $this->timezoneInterface = $timezoneInterface;
    }

    public function getById($id)
    {
        $banner = $this->objectFactory->create();
        $banner->load($id);
        if (!$banner->getId()) {
            throw new NoSuchEntityException(__('Banner with id "%1" does not exist.', $id));
        }

        return $banner;
    }

    public function delete(BannerInterface $banner)
    {
        try {
            $banner->delete();
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }

        return true;
    }

    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    public function getCurrentBanner()
    {
        $bannerToShow = [];

        $date = $this->timezoneInterface->date()->getTimestamp();
        $bannerCollection = $this->collectionFactory->create()
            ->addFilter('is_active', 1)
            ->addFieldToFilter('store', $this->storeManager->getStore()->getId());
        $bannerCollection->getSelect()->group('banner_id')->order(BannerInterface::POSITION, 'ASC');

        foreach ($bannerCollection as $banner) {
            $startTime = $banner->getStartDate() ? $this->timezoneInterface->date(
                new \DateTime($banner->getStartDate())
            )->getTimestamp(): null;
            $endTime = $banner->getEndDate() ? $this->timezoneInterface->date(
                new \DateTime($banner->getEndDate())
            )->getTimestamp(): null;

            if ((!$startTime || $startTime <= $date) && (!$endTime || $endTime >= $date)) {
                if ($banner->getBannerVimeo()) {
                    $bannerToShow = [$banner];
                    break;
                }
                $bannerToShow[] = $banner;
                $this->setBannerImageUrl($banner);
            }
        }

        return $bannerToShow;
    }

    protected function setBannerImageUrl($banner)
    {
        $mediaPath = $this->storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $banner->setBannerImage(trim($mediaPath, '/') . '/' . $banner->getBannerImage());

        if ($this->moduleManager->isEnabled('Yireo_NextGenImages')) {
            $webpConfig = ObjectManager::getInstance()->create('\Yireo\NextGenImages\Config\Config');
            $webpConvertor = ObjectManager::getInstance()->create('\Yireo\Webp2\Convertor\Convertor');

            if ($webpConfig->enabled()) {
                try {
                    $webpUrl = $webpConvertor->getSourceImage($banner->getBannerImage())->getUrl();
                    $banner->setBannerImage($webpUrl);
                } catch (\Exception $e) {
                }
            }
        }
    }
}
