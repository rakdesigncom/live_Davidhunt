<?php

namespace Fyb\StockistLocator\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

class GoogleApi
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $clientFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @var string
     */
    protected $apiKey = '';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\Client\CurlFactory $clientFactory,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer

    ) {
        $this->scopeConfig = $scopeConfig;
        $this->clientFactory = $clientFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param string $address
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getGeocode($address)
    {
        if (strlen($address) < 5) {
            $address .= ' United Kingdom';
        }

        $result = $this->request(
            'https://maps.googleapis.com/maps/api/geocode/json',
            ['address' => trim($address)]
        );

        return $result;
    }

    protected function _init()
    {
        $this->apiKey = $this->scopeConfig->getValue(
            'stockistlocator/general/access_token', ScopeInterface::SCOPE_STORE
        );
        if (!$this->apiKey) {
            throw new LocalizedException(__('Google Api Key is empty!'));
        }
    }

    protected function request($url, $params, $type = "GET")
    {
        $this->_init();

        $params['key'] = $this->apiKey;

        $curl = $this->clientFactory->create();
        $curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $curl->addHeader("Content-Type", "application/json");


        if ($type == 'GET') {
            $url = $url . '?' . http_build_query($params);
            $curl->get($url);
        }

        $result = $this->jsonSerializer->unserialize($curl->getBody());
        if (!isset($result['status']) || $result['status'] !== 'OK') {
            throw new LocalizedException(__('Google Api request error!'));
        }

        return $result['results'];
    }
}
