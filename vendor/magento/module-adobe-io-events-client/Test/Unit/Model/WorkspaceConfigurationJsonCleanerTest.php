<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2026 Adobe
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
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Test\Unit\Model;

use InvalidArgumentException;
use Magento\AdobeIoEventsClient\Model\WorkspaceConfigurationJsonCleaner;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @WorkspaceConfigurationJsonCleaner
 */
class WorkspaceConfigurationJsonCleanerTest extends TestCase
{
    /**
     * @var Json|MockObject
     */
    private Json|MockObject  $jsonMock;

    private WorkspaceConfigurationJsonCleaner $subject;

    protected function setUp(): void
    {
        $this->jsonMock = $this->createMock(Json::class);
        $this->subject = new WorkspaceConfigurationJsonCleaner($this->jsonMock);
    }

    /**
     * Extra keys under project.workspace.details are stripped; credentials and runtime are kept.
     */
    public function testCleanValidJsonArray(): void
    {
        $input = '{"project":{"workspace":{"details":{"credentials":[],"services":[],"events":[],"runtime":{}}}}}';
        $decoded = [
            'project' => [
                'workspace' => [
                    'details' => ['credentials' => [], 'services' => [], 'runtime' => [], 'events' => []],
                ],
            ],
        ];
        $expected = '{"project":{"workspace":{"details":{"credentials":[],"runtime":{}}}}}';

        $this->jsonMock->expects(self::once())
            ->method('unserialize')
            ->with($input)
            ->willReturn($decoded);
        $this->jsonMock->expects(self::once())
            ->method('serialize')
            ->with([
                'project' => [
                    'workspace' => [
                        'details' => ['credentials' => [], 'runtime' => []],
                    ],
                ],
            ])
            ->willReturn($expected);

        self::assertSame($expected, $this->subject->clean($input));
    }

    /**
     * A valid JSON value that decodes to a non-array is returned unchanged.
     */
    public function testCleanNonArrayJsonReturnsOriginal(): void
    {
        $input = '"just a string"';

        $this->jsonMock->expects(self::once())
            ->method('unserialize')
            ->with($input)
            ->willReturn('just a string');

        $this->jsonMock->expects(self::never())->method('serialize');

        self::assertSame($input, $this->subject->clean($input));
    }

    /**
     * An invalid JSON string is returned unchanged.
     */
    public function testCleanInvalidJsonReturnsOriginal(): void
    {
        $input = 'not-valid-json';

        $this->jsonMock->expects(self::once())
            ->method('unserialize')
            ->with($input)
            ->willThrowException(new InvalidArgumentException('Syntax error'));

        $this->jsonMock->expects(self::never())->method('serialize');

        self::assertSame($input, $this->subject->clean($input));
    }

    /**
     * When project.workspace.details is absent the array is serialized unchanged.
     */
    public function testCleanArrayWithoutDetailsPassesThrough(): void
    {
        $input = '{"project":{"name":"My Project"}}';
        $decoded = ['project' => ['name' => 'My Project']];

        $this->jsonMock->expects(self::once())
            ->method('unserialize')
            ->with($input)
            ->willReturn($decoded);

        $this->jsonMock->expects(self::once())
            ->method('serialize')
            ->with($decoded)
            ->willReturn($input);

        self::assertSame($input, $this->subject->clean($input));
    }
}
