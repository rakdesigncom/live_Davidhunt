<?php

namespace Fyb\Pimberly\Model\Api;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Webapi\Rest\Request;

class PimService
{
    public const API_REQUEST_URI = 'https://api.pimberly.io/';

    /**
     * @var \Fyb\Pimberly\Helper\Data
     */
    protected $helper;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonSerializer;

    /**
     * @param \Fyb\Pimberly\Helper\Data $helper
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \GuzzleHttp\ClientFactory $clientFactory
     * @param \GuzzleHttp\Psr7\ResponseFactory $responseFactory
     */
    public function __construct(
        \Fyb\Pimberly\Helper\Data $helper,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory
    ) {
        $this->helper = $helper;
        $this->jsonSerializer = $jsonSerializer;
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param string $uriEndpoint
     * @param array $params
     * @param string $requestMethod
     *
     * @return array
     */
    protected function doRequest(
        string $uriEndpoint,
        array $params = [],
        string $requestMethod = Request::HTTP_METHOD_GET
    ): array {
        $client = $this->clientFactory->create([
            'config' => [
                'base_uri' => self::API_REQUEST_URI,
            ],
        ]);

        if ($requestMethod === Request::HTTP_METHOD_GET && $params) {
            $params = ['query' => $params];
        }

        $params['headers'] = array_merge($params['headers'] ?? [], [
            'Accept' => 'application/json',
        ]);

        try {
            $response = $client->request(
                $requestMethod,
                $uriEndpoint,
                $params
            );

            $responseBody = $response->getBody();
            $responseContent = $responseBody->getContents();
            $response = ['status' => true, 'response' => $this->jsonSerializer->unserialize($responseContent), 'error' => ''];
        } catch (GuzzleException $exception) {
            if ($exception->getCode() === 404) {
                $response = ['status' => true, 'response' => [], 'error' => ''];
            } else {
                $response = ['status' => false, 'response' => [], 'error' => $exception->getMessage()];
            }
        }

        return $response;
    }
}
