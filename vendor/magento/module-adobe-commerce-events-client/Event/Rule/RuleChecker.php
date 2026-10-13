<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Rule;

use Magento\AdobeCommerceEventsClient\Event\Context\ContextRetriever;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\Filter\EventFieldsFilter;
use Magento\AdobeCommerceEventsClient\Event\Operator\CustomOperatorInterface;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorException;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorFactory;
use Magento\AdobeCommerceEventsClient\Event\Operator\OperatorInterface;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextPool;

/**
 * Checks if event data passed a list of rules, do nothing if event does not have configured rules.
 */
class RuleChecker
{
    /**
     * @param RuleFactory $ruleFactory
     * @param OperatorFactory $operatorFactory
     * @param ContextRetriever $contextRetriever
     * @param ContextPool $contextPool
     */
    public function __construct(
        private readonly RuleFactory $ruleFactory,
        private readonly OperatorFactory $operatorFactory,
        private readonly ContextRetriever $contextRetriever,
        private readonly ContextPool $contextPool
    ) {
    }

    /**
     * Checks if event data passed a list of rules.
     * Return false if event data does not contain a field that is added in the rule as it is impossible to verify.
     * Return false in the case when any of the rule not verified.
     *
     * @param Event $event
     * @param array $eventData
     * @return bool
     * @throws OperatorException
     */
    public function verify(Event $event, array $eventData): bool
    {
        $rules = $event->getRules();
        if (empty($rules)) {
            return true;
        }

        foreach ($rules as $ruleData) {
            if (!$this->verifySingleRule($ruleData, $event, $eventData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifies a single rule against the event or event data.
     *
     * @param array $ruleData
     * @param Event $event
     * @param array $eventData
     * @return bool
     * @throws OperatorException
     */
    private function verifySingleRule(array $ruleData, Event $event, array $eventData): bool
    {
        $rule = $this->ruleFactory->create($ruleData);
        $operator = $this->operatorFactory->create($rule->getOperator());

        if ($operator instanceof CustomOperatorInterface) {
            return $operator->verify($rule, $eventData);
        }

        if ($this->isContextField($rule->getField())) {
            return $this->verifyContextField($rule, $operator, $event);
        }

        return $this->verifyEventDataField($rule, $operator, $eventData);
    }

    /**
     * Verifies a rule against a context field.
     *
     * @param Rule $rule
     * @param OperatorInterface $operator
     * @param Event $event
     * @return bool
     * @throws OperatorException
     */
    private function verifyContextField(Rule $rule, OperatorInterface $operator, Event $event): bool
    {
        $contextValue = $this->contextRetriever->getContextValue($rule->getField(), $event);
        return $operator->verify($rule->getValue(), $contextValue);
    }

    /**
     * Verifies a rule against event data fields.
     *
     * Return false if the field specified in the rule does not exist in the event data.
     *
     * @param Rule $rule
     * @param OperatorInterface $operator
     * @param array $eventData
     * @return bool
     * @throws OperatorException
     */
    private function verifyEventDataField(Rule $rule, OperatorInterface $operator, array $eventData): bool
    {
        $data = $eventData;
        $field = $rule->getField();

        if (str_contains($field, '.')) {
            $keys = explode('.', $field);
            $keysCount = count($keys);
            for ($i = 0; $i < $keysCount - 1; $i++) {
                if (!isset($data[$keys[$i]])) {
                    return false;
                }
                $data = $data[$keys[$i]];
            }
            $field = end($keys);
        }

        if (!isset($data[$field])) {
            return false;
        }

        return $operator->verify($rule->getValue(), $data[$field]);
    }

    /**
     * Checks if the rule field is a context field.
     *
     * @param string $field
     * @return bool
     */
    private function isContextField(string $field): bool
    {
        if (!str_starts_with($field, EventFieldsFilter::FIELD_CONTEXT_PREFIX)) {
            return false;
        }

        $sourceParts = explode('.', $field);
        return $this->contextPool->has($sourceParts[0]);
    }
}
