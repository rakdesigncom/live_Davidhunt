<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Controller\Adminhtml\Connection;

use GuzzleHttp\Exception\GuzzleException;
use Magento\AdobeCommerceEventsClient\Event\ClientInterface;
use Magento\AdobeCommerceEventsClient\Event\InvalidConfigurationException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sends a test message to the Events Service.
 */
class TestConnection extends Action implements HttpGetActionInterface
{
    private const HTTP_OK = 200;

    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_AdobeCommerceEventsClient::test_connection';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ClientInterface $client
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Context $context,
        private JsonFactory $resultJsonFactory,
        private ClientInterface $client,
        private LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultJsonFactory->create();
        try {
            $response = $this->client->sendEventDataBatch([
                [
                    'eventCode' => 'connection_testing',
                    'eventData' => []
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody()->getContents(), true);
            if ($statusCode != self::HTTP_OK) {
                $this->logFailure(sprintf(
                    'Error code: %d; reason: %s %s',
                    $statusCode,
                    $response->getReasonPhrase(),
                    json_encode($responseData)
                ));
                $resultJson->setData([
                    'error' => $responseData['error']['message'] ?? $responseData['message'] ?? null
                ]);
            } else {
                $resultJson->setData(['success' => true]);
            }
        } catch (InvalidConfigurationException | GuzzleException $e) {
            $this->logFailure($e->getMessage());
            $resultJson->setData(['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            $this->logger->error(sprintf('Unable to test connection: %s', $e->getMessage()));
            $resultJson->setData(['error' => $e->getMessage()]);
        }

        return $resultJson;
    }

    /**
     * Logs failure information for test events.
     *
     * @param string $message
     * @return void
     */
    private function logFailure(string $message): void
    {
        $this->logger->error(
            sprintf('Sending of test message failed: %s', $message),
            ['destination' => ['internal', 'external']]
        );
    }
}
