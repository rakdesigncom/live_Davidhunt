<?php

namespace Fyb\Pimberly\Model\Api;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use Fyb\Pimberly\Model\Api\PimService;

class ExportService extends PimService
{
    public const API_REQUEST_ENDPOINT = 'core/products/';

    /**
     * @var \Fyb\Pimberly\Model\QueueRepository
     */
    protected $queueRepository;

    /**
     * @var int
     */
    protected $totalCount = 0;

    public function __construct(
        \Fyb\Pimberly\Model\QueueRepository $queueRepository,
        \Fyb\Pimberly\Helper\Data $helper,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory
    ) {
        parent::__construct($helper, $jsonSerializer, $clientFactory, $responseFactory);

        $this->queueRepository = $queueRepository;
    }

    public function execute($fromDate = null)
    {
        $syncDate = date('Y-m-d H:i:s');
        $this->totalCount = 0;

        try {
            $this->exportProducts($fromDate);
            $this->queueRepository->saveLog("Exported " . $this->totalCount . " products", 1, $syncDate);
        } catch (\Exception $e) {
            $this->queueRepository->saveLog($e->getMessage(), 0, $syncDate);
        }
    }

    protected function exportProducts($fromDate = null, $fromId = null, $totalCount = 0)
    {
        $params = $this->buildParams($fromDate, $fromId);
        $response = $this->doRequest(static::API_REQUEST_ENDPOINT, $params);

        if (!$response['status']) {
            throw new \Exception($response['error']);
        }

        $products = $response['response']['data'] ?? [];
        $nextExists = count($products) >= $params['limit'];
        $this->totalCount += count($products);

        $this->processProducts($products);

        if ($nextExists && !empty($response['response']['maxId'])) {
            $this->exportProducts($fromDate, $response['response']['maxId'], $totalCount);
        }
    }

    protected function buildParams($fromDate, $fromId)
    {
        $params = [
            'access_token' => $this->helper->getAccessToken(),
            'limit' => $this->helper->getLimit(),
        ];
        if ($fromDate) {
            $fromDate = date("Y-m-d\TH:i:s", strtotime($fromDate));
            $params['filters'] = '{"dateUpdated": {"$gte": "' . $fromDate . '.000Z"}}';
        }

        if ($fromId) {
            $params['sinceId'] = $fromId;
        }

        return $params;
    }

    protected function processProducts($products)
    {
        foreach ($products as $product) {
            $data = [
                'sku' => $product['Primary ID'],
                'product_id' => $product['_id'],
                'data' => $this->jsonSerializer->serialize($product),
            ];
            $this->queueRepository->addToQueue($data);
        }
    }
}

