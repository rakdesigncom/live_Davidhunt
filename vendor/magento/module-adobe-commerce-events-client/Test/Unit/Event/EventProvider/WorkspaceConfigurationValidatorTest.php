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

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\EventProvider;

use Magento\AdobeCommerceEventsClient\Api\Data\EventProviderInterface;
use Magento\AdobeCommerceEventsClient\Config\Validator\WorkspaceFormatValidator;
use Magento\AdobeCommerceEventsClient\Event\EventProvider\Validator\WorkspaceConfigurationValidator;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test for @see WorkspaceConfigurationValidator
 */
class WorkspaceConfigurationValidatorTest extends TestCase
{
    /**
     * @var WorkspaceConfigurationValidator
     */
    private WorkspaceConfigurationValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new WorkspaceConfigurationValidator(new WorkspaceFormatValidator(new Json()));
    }

    /**
     * @param string $value
     * @return void
     * @throws ValidatorException
     */
    #[DataProvider('successDataProvider')]
    public function testValidateWithValidJson(string $json): void
    {
        $eventProviderMock = $this->createMock(EventProviderInterface::class);
        $eventProviderMock->method('getProviderId')
            ->willReturn('test_provider_id');
        $eventProviderMock->method('getWorkspaceConfiguration')
            ->willReturn($json);

        $this->validator->validate(
            $eventProviderMock,
            [
                'test_provider_id' => $this->createMock(EventProviderInterface::class)
            ]
        );

        $this->assertTrue(true);
    }

    public function testValidateWithNotCorrectJson(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Workspace Configuration has the wrong format. Missed the required properties.');

        $eventProviderMock = $this->createMock(EventProviderInterface::class);
        $eventProviderMock->method('getWorkspaceConfiguration')
            ->willReturn('{"project":{"id":"4566206088345142631"}}');

        $this->validator->validate($eventProviderMock);
    }

    public function testValidateWithInvalidJson(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Workspace Configuration has the wrong format');

        $eventProviderMock = $this->createMock(EventProviderInterface::class);
        $eventProviderMock->method('getWorkspaceConfiguration')
            ->willReturn('invalid json');

        $this->validator->validate($eventProviderMock);
    }

    public function testValidateWithAsterisksAndProviderNotExists(): void
    {
        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('The workspace configuration has the wrong format. Provide a valid JSON string.');

        $eventProviderMock = $this->createMock(EventProviderInterface::class);
        $eventProviderMock->method('getProviderId')
            ->willReturn('test_provider_id');
        $eventProviderMock->method('getWorkspaceConfiguration')
            ->willReturn('***');

        $this->validator->validate($eventProviderMock);
    }

    /**
     * @return array[]
     */
    public static function successDataProvider(): array
    {
        return [
            'valid json' => [
                'json' => '{"project":{"workspace":{"details":{"credentials": {"test": "value"}}}}}',
            ],
            'empty string' => [
                'json' => '',
            ],
            'asterisks' => [
                'json' => '****',
            ],
        ];
    }
}
