<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceEventsClient\Event\Validator\Rule;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorFactory;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\EventValidatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Validator\ValidatorException;

/**
 * Validator of rules for a provided event
 */
class EventRuleValidator implements EventValidatorInterface
{
    /**
     * @var OperatorFactory
     */
    private OperatorFactory $operatorFactory;

    /**
     * @param OperatorFactory $operatorFactory
     */
    public function __construct(OperatorFactory $operatorFactory)
    {
        $this->operatorFactory = $operatorFactory;
    }

    /**
     * Validates that rules are provided with a parent event name and all rules are defined using valid operator names
     *
     * @param Event $event
     * @param bool $force
     * @return void
     * @throws ValidatorException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(Event $event, bool $force = false): void
    {
        if (!empty($event->getRules() && empty($event->getParent()))) {
            throw new ValidatorException(
                __('A parent event name must be set for rule-based event subscriptions')
            );
        }

        $validOperators = $this->operatorFactory->getOperatorNames();

        foreach ($event->getRules() as $rule) {
            if (!in_array($rule[RuleInterface::RULE_OPERATOR], $validOperators)) {
                throw new ValidatorException(
                    __('"%1" is an invalid event rule operator name', $rule[RuleInterface::RULE_OPERATOR])
                );
            }
        }
    }
}
