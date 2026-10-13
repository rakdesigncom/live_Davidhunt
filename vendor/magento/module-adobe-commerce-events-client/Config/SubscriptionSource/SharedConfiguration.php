<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Config\SubscriptionSource;

use Magento\AdobeCommerceEventsClient\Config\SubscriptionSourceInterface;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventField;
use Magento\AdobeCommerceEventsClient\Event\EventSubscriberInterface;
use Magento\AdobeCommerceEventsClient\Event\InvalidConfigurationException;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\LocalizedException;

/**
 * Returns event configuration from the shared configuration file
 */
class SharedConfiguration implements SubscriptionSourceInterface
{
    /**
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(private DeploymentConfig $deploymentConfig)
    {
    }

    /**
     * Returns list of event subscriptions from application configuration files.
     *
     * @return array
     * @throws InvalidConfigurationException if event configuration is invalid
     * or shared configuration file can't be read
     */
    public function getEventSubscriptions(): array
    {
        try {
            $eventSubscriptions = $this->deploymentConfig->get(EventSubscriberInterface::IO_EVENTS_CONFIG_NAME);
        } catch (LocalizedException $e) {
            throw new InvalidConfigurationException(__($e->getMessage()));
        }

        if (empty($eventSubscriptions) || !is_array($eventSubscriptions)) {
            return [];
        }

        $result = [];
        foreach ($eventSubscriptions as $eventName => $eventData) {
            $eventName = strtolower($eventName);

            if (!is_array($eventData) || empty($eventData['fields'])) {
                throw new InvalidConfigurationException(
                    __(
                        'Wrong configuration in "%1" section of app/etc/env.php or app/etc/config.php files for the ' .
                        'event "%2". The configuration must be in array format with at least one field configured.',
                        EventSubscriberInterface::IO_EVENTS_CONFIG_NAME,
                        $eventName
                    )
                );
            }

            $result[$eventName] = $this->prepareEventData($eventName, $eventData);
        }

        return $result;
    }

    /**
     * The subscriptions from shared configuration can't override the required ones from XML configuration.
     *
     * @return bool
     */
    public function isOptional(): bool
    {
        return true;
    }

    /**
     * Prepares event data for the event object creation.
     *
     * @param string $eventName
     * @param array $eventData
     * @return array
     */
    private function prepareEventData(string $eventName, array $eventData): array
    {
        return [
            Event::EVENT_NAME => $eventName,
            Event::EVENT_PARENT => $eventData['parent'] ?? null,
            Event::EVENT_OPTIONAL => true,
            Event::EVENT_FIELDS => $this->processFields($eventData['fields']),
            Event::EVENT_RULES => $eventData['rules'] ?? [],
            Event::EVENT_PROCESSORS => $eventData['processors'] ?? [],
            Event::EVENT_ENABLED => !isset($eventData['enabled']) || $eventData['enabled'] === 1,
            Event::EVENT_PRIORITY =>
                isset($eventData[Event::EVENT_PRIORITY]) && $eventData[Event::EVENT_PRIORITY] === 1,
            Event::EVENT_DESTINATION => $eventData[Event::EVENT_DESTINATION] ?? Event::DESTINATION_DEFAULT,
            Event::EVENT_HIPAA_AUDIT_REQUIRED => isset($eventData[Event::EVENT_HIPAA_AUDIT_REQUIRED])
                && $eventData[Event::EVENT_HIPAA_AUDIT_REQUIRED] === 1,
            Event::EVENT_PROVIDER_ID => $eventData[Event::EVENT_PROVIDER_ID] ?? null,
        ];
    }

    /**
     * Reformatting event fields having no converter and source into a field array
     *
     * @param array $eventFields
     * @return array
     */
    private function processFields(array $eventFields): array
    {
        foreach ($eventFields as $fieldIndex => $fieldValue) {
            if (!is_array($fieldValue)) {
                $eventFields[$fieldIndex] = [
                    EventField::NAME => $fieldValue,
                    EventField::CONVERTER => null,
                    EventField::SOURCE => null,
                ];
            }
        }

        return $eventFields;
    }
}
