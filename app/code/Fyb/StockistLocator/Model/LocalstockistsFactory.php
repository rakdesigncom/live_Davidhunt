<?php

namespace Fyb\StockistLocator\Model;

use Magento\Store\Model\ScopeInterface;

class LocalstockistsFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Fyb\StockistLocator\Model\GoogleApi
     */
    protected $googleApi;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Fyb\StockistLocator\Model\GoogleApi $googleApi
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Fyb\StockistLocator\Model\GoogleApi $googleApi
    ) {
        $this->_objectManager = $objectManager;
        $this->_scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->googleApi = $googleApi;
    }

    /**
     * Create new country model
     *
     * @param array $arguments
     *
     * @return \Magento\Directory\Model\Country
     */
    public function create(array $arguments = [])
    {
        return $this->_objectManager->create('Fyb\StockistLocator\Model\Localstockists', $arguments, false);
    }

    /**
     * @param string $address
     * @param float $radius
     * @param boolean $isFirstSearch
     *
     * @return mixed
     */
    public function getNearestStockists($address, $radius)
    {
        $stores = $this->create()->getCollection()->addFieldToSelect('*');
        $customerPoints = $this->getLonLat($address);

        if ($customerPoints['lon'] == 0 && $customerPoints['lat'] == 0) {
            return [];
        } else {
            $relevantStores = [];

            foreach ($stores as $key => $store) {
                $lonlat['lon'] = $store->getLongitude();
                $lonlat['lat'] = $store->getLatitude();

                $unit = "miles";

                if ($lonlat['lon'] != 0 && $lonlat['lat'] != 0) {
                    $distance = $this->calculateDistance($customerPoints, $lonlat, $unit);

                    if ($distance <= $radius) {
                        $storeArray["store"] = $store;
                        $storeArray["location"] = $lonlat;
                        $storeArray["distance"] = $distance;

                        $relevantStores[] = $storeArray;
                    }
                }
            }

            $relevantStores = $this->sortStores($relevantStores);

            $relevantStores['customerPoints'] = $customerPoints;

            return $relevantStores;
        }
    }

    /**
     * @param mixed $stores
     *
     * @return mixed
     */
    public function sortStores($stores)
    {
        $m = 0;
        $upper = [];
        $lower = [];

        if (count($stores) <= 1) {
            return $stores;
        }

        $pivot = null;
        foreach ($stores as $key => $store) {
            if ($m == 0) {
                $pivot = $store;
                $m++;

                continue;
            }

            if ($pivot['distance'] >= $store['distance']) {
                array_push($lower, $store);
            } else {
                array_push($upper, $store);
            }
        }

        $sUpper = $this->sortStores($upper);
        $sLower = $this->sortStores($lower);

        return array_merge($sLower, [$pivot], $sUpper);
    }

    /**
     * @param array $points1
     * @param array $points2
     * @param string $unit
     *
     * @return float
     */
    public function calculateDistance($points1, $points2, $unit)
    {
        $lon1 = $points1['lon'];
        $lon2 = $points2['lon'];

        $lat1 = $points1['lat'];
        $lat2 = $points2['lat'];

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(
                deg2rad($theta)
            );
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        return match ($unit) {
            'km' => ($miles * 1.609344),
            'nm' => ($miles * 1.609344),
            default => $miles,
        };
    }

    /**
     * @param string $address
     *
     * @return array
     */
    public function getLonLat($address)
    {
        $points = [];

        if (strlen($address) < 5) {
            $address .= ' United Kingdom';
        }

        try {
            $response = $this->googleApi->getGeocode($address);
        } catch (\Exception $e) {
            $response = null;
        }
        if ($response) {
            $coordinates = $response[0]['geometry']['location'];

            $points['lon'] = $coordinates['lng'];
            $points['lat'] = $coordinates['lat'];
            $points['address'] = $response[0]['formatted_address'];
        } else {
            $points['lon'] = 0;
            $points['lat'] = 0;
            $points['address'] = '';
        }

        return $points;
    }
}
