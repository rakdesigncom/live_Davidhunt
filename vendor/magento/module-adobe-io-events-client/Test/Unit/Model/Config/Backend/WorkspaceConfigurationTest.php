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

namespace Magento\AdobeIoEventsClient\Test\Unit\Model\Config\Backend;

use Magento\AdobeIoEventsClient\Model\WorkspaceConfigurationJsonCleanerInterface;
use Magento\AdobeIoEventsClient\Model\Config\Backend\WorkspaceConfiguration;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see WorkspaceConfiguration
 */
class WorkspaceConfigurationTest extends TestCase
{
    /**
     * @var WorkspaceConfiguration
     */
    private WorkspaceConfiguration $model;

    /**
     * @var WorkspaceConfigurationJsonCleanerInterface|MockObject
     */
    private WorkspaceConfigurationJsonCleanerInterface|MockObject $jsonCleanerMock;

    /**
     * @var EncryptorInterface|MockObject
     */
    private EncryptorInterface|MockObject $encryptorMock;

    protected function setUp(): void
    {
        $this->jsonCleanerMock = $this->createMock(WorkspaceConfigurationJsonCleanerInterface::class);
        $this->encryptorMock = $this->createMock(EncryptorInterface::class);

        $this->model = new WorkspaceConfiguration(
            $this->createMock(Context::class),
            $this->createMock(Registry::class),
            $this->createMock(ScopeConfigInterface::class),
            $this->createMock(TypeListInterface::class),
            $this->encryptorMock,
            $this->jsonCleanerMock
        );
    }

    /**
     * A non-obscured, non-empty value is passed through the JSON cleaner before encryption.
     */
    public function testBeforeSaveCleansDirtyJson(): void
    {
        $raw = '{"project":{"workspace":{"details":{"credentials":[],"services":[]}}}}';
        $cleaned = '{"project":{"workspace":{"details":{"credentials":[]}}}}';
        $encrypted = 'encrypted-value';

        $this->model->setValue($raw);

        $this->jsonCleanerMock->expects(self::once())
            ->method('clean')
            ->with($raw)
            ->willReturn($cleaned);
        $this->encryptorMock->expects(self::once())
            ->method('encrypt')
            ->with($cleaned)
            ->willReturn($encrypted);

        $this->model->beforeSave();

        self::assertSame($encrypted, $this->model->getValue());
    }

    /**
     * An obscured value (all asterisks) skips both the cleaner and encryption entirely.
     * The parent Encrypted::beforeSave() treats asterisk-only values as "unchanged" and does not re-encrypt.
     */
    public function testBeforeSaveSkipsCleanerForObscuredValue(): void
    {
        $this->model->setValue('*****');

        $this->jsonCleanerMock->expects(self::never())->method('clean');
        $this->encryptorMock->expects(self::never())->method('encrypt');

        $this->model->beforeSave();
    }

    /**
     * An empty string skips the cleaner and encryption entirely.
     */
    public function testBeforeSaveSkipsCleanerForEmptyValue(): void
    {
        $this->model->setValue('');

        $this->jsonCleanerMock->expects(self::never())->method('clean');
        $this->encryptorMock->expects(self::never())->method('encrypt');

        $this->model->beforeSave();
    }

    /**
     * If the JSON cleaner returns the string unchanged (e.g. invalid JSON), the original
     * value is still forwarded to the encryptor.
     */
    public function testBeforeSaveEncryptsCleanedValue(): void
    {
        $value = '{"key":"value"}';
        $this->model->setValue($value);

        $this->jsonCleanerMock->expects(self::once())
            ->method('clean')
            ->with($value)
            ->willReturn($value);
        $this->encryptorMock->expects(self::once())
            ->method('encrypt')
            ->with($value)
            ->willReturn('encrypted');

        $this->model->beforeSave();

        self::assertSame('encrypted', $this->model->getValue());
    }
}
