<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\RequestOptions;
use Magento\AdobeCommerceEventsClient\Event\Config as EventsConfig;
use Magento\AdobeCommerceEventsClient\Event\Dispatcher\DataSendDispatcherInterface;
use Magento\AdobeIoEventsClient\Exception\InvalidConfigurationException as AdobeIOConfigurationException;
use Magento\AdobeIoEventsClient\Model\Credentials\ScopeConfigCredentialsFactory;
use Magento\AdobeIoEventsClient\Model\TokenCacheHandler;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;

/**
 * Client class for Commerce Events
 */
class Client implements ClientInterface
{
    private const HTTP_UNAUTHORIZED = 401;

    /**
     * @param Config $config
     * @param ClientFactory $clientFactory
     * @param ScopeConfigCredentialsFactory $credentialsFactory
     * @param TokenCacheHandler $tokenCacheHandler
     * @param DataSendDispatcherInterface $dataSendDispatcher
     */
    public function __construct(
        private EventsConfig $config,
        private ClientFactory $clientFactory,
        private ScopeConfigCredentialsFactory $credentialsFactory,
        private TokenCacheHandler $tokenCacheHandler,
        private DataSendDispatcherInterface $dataSendDispatcher
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sendEventDataBatch(array $messages): ResponseInterface
    {
        try {
            $url = sprintf('%s/v1/publish-batch', $this->config->getEndpointUrl());

            $payload = [
                'merchantId' => $this->config->getMerchantId(),
                'environmentId' => $this->config->getEnvironmentId(),
                'messages' => $messages,
                self::INSTANCE_ID => $this->config->getInstanceId()
            ];

            $credentials = $this->credentialsFactory->create();
            $params = [
                RequestOptions::JSON => $payload,
                'http_errors' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $credentials->getToken()->getAccessToken(),
                    'x-api-key' => $credentials->getClientId(),
                    'x-ims-org-id' => $credentials->getImsOrgId()
                ]
            ];

            $this->dataSendDispatcher->dispatch($payload);

            $client = $this->clientFactory->create();
            $response = $client->request('POST', $url, $params);

            if ($response->getStatusCode() == self::HTTP_UNAUTHORIZED) {
                $this->tokenCacheHandler->removeTokenData();
            }

            return $response;
        } catch (AuthorizationException|NotFoundException|AdobeIOConfigurationException $exception) {
            throw new InvalidConfigurationException(__($exception->getMessage()));
        }
    }
}
