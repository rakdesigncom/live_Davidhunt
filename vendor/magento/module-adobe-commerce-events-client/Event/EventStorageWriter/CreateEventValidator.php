<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\EventStorageWriter;

use Magento\AdobeCommerceEventsClient\Event\Config;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleChecker;

/**
 * Checks if event can be created
 * - configuration is enabled
 * - verification passes for all rules
 * - event data size is within limits
 */
class CreateEventValidator
{
    /**
     * @param Config $eventConfiguration
     * @param RuleChecker $ruleChecker
     */
    public function __construct(
        private readonly Config $eventConfiguration,
        private readonly RuleChecker $ruleChecker,
    ) {
    }

    /**
     * Checks if event can be created
     *
     * @param Event $event
     * @param array $eventData
     * @return bool
     * @throws OperatorException
     */
    public function validate(Event $event, array $eventData): bool
    {
        return $this->eventConfiguration->isEnabled()
            && $this->ruleChecker->verify($event, $eventData);
    }
}
