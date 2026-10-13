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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Config\Validator;

use Magento\AdobeCommerceEventsClient\Config\Validator\EventingIdValidator;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see EventingIdValidator
 */
class EventingIdValidatorTest extends TestCase
{
    /**
     * @var EventingIdValidator
     */
    private EventingIdValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new EventingIdValidator('Test ID');
    }

    /**
     * @param string $id
     */
    #[DataProvider('validIdProvider')]
    public function testValidId(string $id)
    {
        self::assertTrue($this->validator->validate($id));
    }

    public static function validIdProvider()
    {
        return [
            ['testId'],
            ['test_9'],
            ['Test8_'],
            ['9id'],
            ['_id'],
        ];
    }

    /**
     * @param string $id
     */
    #[DataProvider('invalidIdProvider')]
    public function testInvalidId(string $id)
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Test ID is invalid');

        $this->validator->validate($id);
    }

    public static function invalidIdProvider()
    {
        return [
            ['testId-'],
            ['id with space'],
            ['id@example.com'],
        ];
    }
}
