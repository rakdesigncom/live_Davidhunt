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

namespace Magento\AdobeCommerceEventsClient\Event\Data;

use Magento\AdobeCommerceEventsClient\Api\Data\EventRuleInterface;
use Magento\AdobeCommerceEventsClient\Event\Rule\RuleInterface;
use Magento\Framework\DataObject;

/**
 * Data object for event rules
 */
class EventRuleData extends DataObject implements EventRuleInterface
{
    /**
     * @inheritDoc
     */
    public function setField(string $field): EventRuleInterface
    {
        $this->setData(RuleInterface::RULE_FIELD, $field);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getField(): string
    {
        return $this->getData(RuleInterface::RULE_FIELD);
    }

    /**
     * @inheritDoc
     */
    public function setOperator(string $operator): EventRuleInterface
    {
        $this->setData(RuleInterface::RULE_OPERATOR, $operator);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOperator(): string
    {
        return $this->getData(RuleInterface::RULE_OPERATOR);
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value): EventRuleInterface
    {
        $this->setData(RuleInterface::RULE_VALUE, $value);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return $this->getData(RuleInterface::RULE_VALUE);
    }
}
