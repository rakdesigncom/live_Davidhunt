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

use Magento\AdobeCommerceEventsClient\Config\Validator\WorkspaceFormatValidator;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see WorkspaceFormatValidator
 */
class WorkspaceFormatValidatorTest extends TestCase
{
    /**
     * @var WorkspaceFormatValidator
     */
    private WorkspaceFormatValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new WorkspaceFormatValidator(new Json());
    }

    public function testNotValidJson()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Unable to unserialize value');

        $this->validator->validate('{');
    }

    public function testEmptyJson()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Workspace Configuration has the wrong format');

        $this->validator->validate('{}');
    }

    public function testMissedParameters()
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Workspace Configuration has the wrong format');

        $this->validator->validate('{"project":{"id":"4566206088345142631"}}');
    }

    public function testValidConfiguration()
    {
        self::assertTrue(
            $this->validator->validate('{"project":{"workspace":{"details":{"credentials": {"test": "value"}}}}}')
        );
    }
}
