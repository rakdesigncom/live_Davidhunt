<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2026 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Context;

use Magento\AdobeCommerceEventsClient\Event\Converter\EventDataConverter;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextArrayFieldFilter;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetriever as Retriever;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetrieverException;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextSourceParser;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Retrieves a value from the application context.
 *
 * Supports an optional field-filter expression appended to the source string:
 *   context_foo.get_bar.get_items[sku,qty,product.name]
 * When present, only the listed dot-path fields are kept in every item of the
 * returned array.
 */
class ContextRetriever
{
    /**
     * @param Retriever $retriever
     * @param EventDataConverter $eventDataConverter
     * @param LoggerInterface $logger
     * @param ContextSourceParser $contextSourceParser
     * @param ContextArrayFieldFilter $contextArrayFieldFilter
     */
    public function __construct(
        private readonly Retriever $retriever,
        private readonly EventDataConverter $eventDataConverter,
        private readonly LoggerInterface $logger,
        private readonly ContextSourceParser $contextSourceParser,
        private readonly ContextArrayFieldFilter $contextArrayFieldFilter,
    ) {
    }

    /**
     * Retrieves a value from a supported application context based on the provided source string.
     *
     * Logs error message for the given event if retrieval or conversion fails and returns null.
     * Add filtration of fields if field filters are specified in the source string.
     * Example of source with filtered fields:
     * `context_checkout_session.get_quote.get_items[qty,product.name,product.attributes.url_key]`
     *
     * @param string $source
     * @param Event $event
     * @return array|mixed|null
     */
    public function getContextValue(string $source, Event $event)
    {
        $retrieverPath = $this->contextSourceParser->getRetrieverPath($source);
        $fieldFilters = $this->contextSourceParser->getFieldFilters($source);

        try {
            $contextEntity = $this->retriever->getContextValue($retrieverPath);
        } catch (ContextRetrieverException $e) {
            $this->logWarning($e->getMessage(), $event);
            return null;
        }

        try {
            if (is_object($contextEntity)) {
                $contextEntity = $this->convertObject($contextEntity);
            } elseif (is_array($contextEntity)) {
                $contextEntity = $this->convertArray($contextEntity);
            }
        } catch (Throwable $e) {
            $this->logWarning(
                sprintf(
                    'Can not convert context value retrieved with source \'%s\'.',
                    $source
                ),
                $event
            );
            return null;
        }

        if (!empty($fieldFilters) && is_array($contextEntity)) {
            $contextEntity = array_is_list($contextEntity)
                ? $this->contextArrayFieldFilter->filter($contextEntity, $fieldFilters)
                : $this->contextArrayFieldFilter->filterItem($contextEntity, $fieldFilters);
        }

        return $contextEntity;
    }

    /**
     * Converts an array, converting any object elements to their array representation.
     *
     * @param array $items
     * @return array
     */
    private function convertArray(array $items): array
    {
        return array_map(function ($item) {
            if (is_object($item)) {
                return $this->convertObject($item);
            }
            return $item;
        }, $items);
    }

    /**
     * Converts an object to an array representation.
     *
     * @param object $contextEntity
     * @return array
     */
    private function convertObject(object $contextEntity): array
    {
        $result = $this->eventDataConverter->convert($contextEntity);
        return is_array($result) ? $result : [];
    }

    /**
     * Logs a warning message for the given event if context retrieval fails.
     *
     * @param string $message
     * @param Event $event
     * @return void
     */
    private function logWarning(string $message, Event $event): void
    {
        $this->logger->warning(
            sprintf('Failed to retrieve context value for event %s: %s', $event->getName(), $message),
            [
                'destination' => ['internal', 'external']
            ]
        );
    }
}
