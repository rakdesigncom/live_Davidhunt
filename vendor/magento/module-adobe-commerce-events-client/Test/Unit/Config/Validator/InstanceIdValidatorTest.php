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

use Magento\AdobeCommerceEventsClient\Config\Validator\InstanceIdValidator;
use Magento\Framework\Exception\ValidatorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see InstanceIdValidator
 */
class InstanceIdValidatorTest extends TestCase
{
    /**
     * @var InstanceIdValidator
     */
    private InstanceIdValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new InstanceIdValidator();
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
            ['test_Id9_-'],
            ['987123'],
        ];
    }

    public function testInvalidId()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Instance ID contains invalid characters');

        $this->validator->validate('testId*');
    }
}
