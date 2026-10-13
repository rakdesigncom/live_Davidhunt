<?php
/**
 * Class Banner
 *
 * PHP version 8.2
 *
 * @category Sparsh
 * @package  Sparsh_Banner
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */

namespace Sparsh\Banner\ViewModel;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Sparsh\Banner\Api\Data\BannerInterface;
use Sparsh\Banner\Model\ResourceModel\Banner\Collection as BannerCollection;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\DataObject\IdentityInterface;
/**
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Banner implements ArgumentInterface
{

    /**
     * StoreManager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * DataCollection
     *
     * @var \Magento\Framework\Data\Collection
     */
    protected $dataCollection;

    /**
     * BannerCollectionFactory
     *
     * @var \Sparsh\Banner\Model\ResourceModel\Banner\CollectionFactory
     */
    protected $bannerCollectionFactory;

    /**
     * Timezone
     *
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezoneInterface;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSesion;

    /**
     * DateTime
     *
     * @var DateTime
     */
    protected $date;

    /**
     * BannerHelper
     *
     * @var \Sparsh\Banner\Helper\Data
     */
    protected $bannerHelper;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var \Sparsh\Banner\Model\ResourceModel\Banner
     */
    private $resourceBanner;

    /**
     * Banner constructor.
     * @param DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \Sparsh\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerCollectionFactory
     * @param \Magento\Framework\Data\Collection $dataCollection
     * @param \Sparsh\Banner\Helper\Data $bannerHelper
     * @param \Sparsh\Banner\Model\ResourceModel\Banner $resourceBanner
     * @param \Magento\Customer\Model\Session $customerSesion
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     */
    public function __construct(
        DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Sparsh\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerCollectionFactory,
        \Magento\Framework\Data\Collection $dataCollection,
        \Sparsh\Banner\Helper\Data $bannerHelper,
        \Sparsh\Banner\Model\ResourceModel\Banner $resourceBanner,
        \Magento\Customer\Model\Session $customerSesion,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider
    ) {
        $this->storeManager = $storeManager;
        $this->bannerCollectionFactory = $bannerCollectionFactory;
        $this->timezoneInterface = $timezoneInterface;
        $this->dataCollection = $dataCollection;
        $this->customerSesion = $customerSesion;
        $this->date = $date;
        $this->bannerHelper = $bannerHelper;
        $this->resourceBanner = $resourceBanner;
        $this->_filterProvider = $filterProvider;
    }

    /**
     * Retrieve config value
     *
     * @return string
     */
    public function getConfig($config)
    {
        return $this->bannerHelper->getConfig($config);
    }

    /**
     * Return Banner Collection
     *
     * @return \Sparsh\Banner\Model\ResourceModel\Banner\Collection
     * @throws \Exception
     */
    public function getBanner($block)
    {
        if (!$block->hasData('banner')) {
            $bannerToShow = null;

            $date = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $bannerCollection = $this->bannerCollectionFactory->create()->addFilter('is_active', 1)
                ->addFieldToFilter('store', $this->storeManager->getStore()->getId());
            $bannerCollection->getSelect()->group('banner_id');
            $bannerCollection->getSelect()->order(BannerInterface::POSITION, BannerCollection::SORT_ORDER_ASC);
            $collection = $this->dataCollection;

            foreach ($bannerCollection as $banner) {
                $data = $banner->getData();
                $date = $this->date->timestamp($date);
                $startTime = $this->date->timestamp($data['start_date']);
                $endTime = $this->date->timestamp($data['end_date']);
                if ((($startTime <= $date) || ($data['start_date'] == null) || ($data['start_date'] == "0000-00-00 00:00:00"))
                    && (($endTime >= $date) || ($data['end_date'] == null) || ($data['end_date'] == "0000-00-00 00:00:00"))
                ) {
                    if ($bannerToShow == null || !$bannerToShow->getStartDate()) {
                        $bannerToShow = $banner;
                        continue;
                    }

                    $dateSelected = $this->date->timestamp($bannerToShow->getStartDate());
                    $startTimeSelected = $this->date->timestamp($banner->getStartDate());
                    if ($banner->getStartDate() && $startTimeSelected >= $dateSelected) {
                        $bannerToShow = $banner;
                    }
                }
            }

            $rowObj = new \Magento\Framework\DataObject();
            $rowObj->setData($bannerToShow->getData());
            $collection->addItem($rowObj);
            $block->setData('banner', $collection);
        }
        return $block;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        return [\Sparsh\Banner\Model\Banner::CACHE_TAG . '_' . 'list'];
    }

    /**
     * Return Media Path
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaPath()
    {
        return $this->storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Prepare HTML content
     *
     * @return string
     */
    public function getCmsFilterContent($value = '')
    {
        $html = $this->_filterProvider->getPageFilter()->filter($value);
        return $html;
    }
}
