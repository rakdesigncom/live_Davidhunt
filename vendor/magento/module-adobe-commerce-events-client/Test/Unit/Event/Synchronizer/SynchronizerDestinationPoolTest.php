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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\Synchronizer;

use Magento\AdobeCommerceEventsClient\Event\Synchronizer\SynchronizerDestinationPool;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see SynchronizerDestinationPool
 */
class SynchronizerDestinationPoolTest extends TestCase
{
    public function testEmptyPoolByDefault()
    {
        $destinations = (new SynchronizerDestinationPool())->getList();
        self::assertIsArray($destinations);
        self::assertEmpty($destinations);
    }

    public function testGetList()
    {
        $registeredDestinations = [
            'default' => 'default',
            'saas' => 'saas'
        ];

        self::assertEquals(
            ['default', 'saas'],
            (new SynchronizerDestinationPool($registeredDestinations))->getList()
        );
    }
}
