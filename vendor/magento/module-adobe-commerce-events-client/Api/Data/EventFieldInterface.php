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

namespace Magento\AdobeCommerceEventsClient\Api\Data;

/**
 * Interface for event field data from webapi requests
 *
 * @api
 */
interface EventFieldInterface
{
    /**
     * Sets event field name
     *
     * @param string $name
     * @return EventFieldInterface
     */
    public function setName(string $name): EventFieldInterface;

    /**
     * Returns event field name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Sets event field converter
     *
     * @param string $converter
     * @return EventFieldInterface
     */
    public function setConverter(string $converter): EventFieldInterface;

    /**
     * Returns event field converter
     *
     * @return string
     */
    public function getConverter(): string;

    /**
     * Sets event field source
     *
     * @param string $source
     * @return EventFieldInterface
     */
    public function setSource(string $source): EventFieldInterface;

    /**
     * Returns event field source
     *
     * @return string
     */
    public function getSource(): string;
}
