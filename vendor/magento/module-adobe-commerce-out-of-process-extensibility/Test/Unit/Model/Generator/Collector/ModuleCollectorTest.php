<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
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

namespace Magento\AdobeCommerceOutOfProcessExtensibility\Test\Unit\Model\Generator\Collector;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\ModuleCollector;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for ModuleCollector class.
 */
class ModuleCollectorTest extends TestCase
{
    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var ModuleCollector
     */
    private ModuleCollector $moduleCollector;

    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->moduleCollector = new ModuleCollector($this->fileMock);
    }

    /**
     * Checks that a module's information is correctly collected when composer.json and module.xml files for the module
     * containing the input class are found.
     *
     * @return void
     */
    public function testCollect()
    {
        $testDir = '/testDir';
        $composerPath = $testDir . '/composer.json';
        $moduleXmlPath = $testDir . '/etc/module.xml';
        $testClass = $testDir . '/TestClass.php';

        $reflectionClassMock = $this->createMock(ReflectionClass::class);
        $reflectionClassMock->expects(self::once())
            ->method('getFileName')
            ->willReturn($testClass);

        $this->fileMock->expects(self::once())
            ->method('getParentDirectory')
            ->with($testClass)
            ->willReturn($testDir);
        $this->fileMock->expects(self::exactly(2))
            ->method('isExists')
            ->willReturnCallback(function (string $param) use ($composerPath, $moduleXmlPath) {
                static $count = 0;
                match ($count++) {
                    0 => $this->assertEquals($composerPath, $param),
                    1 => $this->assertEquals($moduleXmlPath, $param)
                };
                return true;
            });
        $this->fileMock->expects(self::exactly(2))
            ->method('fileGetContents')
            ->willReturnCallback(function (string $path) use ($composerPath, $moduleXmlPath) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        $this->assertEquals($composerPath, $path);
                        return '{"name": "magento/module-test", "type": "magento2-module"}';
                    case 1:
                        $this->assertEquals($moduleXmlPath, $path);
                        return '<?xml version="1.0"?><config><module name="Magento_Test" /></config>';
                }
            });

        $this->moduleCollector->collect($reflectionClassMock);
        $moduleData = $this->moduleCollector->getModules();
        $this->assertEquals(
            [
                'magento/module-test' =>
                    [
                        'packageName' => 'magento/module-test',
                        'name' => 'Magento_Test'
                    ]
            ],
            $moduleData
        );
    }

    /**
     * Checks that a module's information is correctly collected when a composer.json file but not a module.xml file for
     * the module containing the input class are found.
     *
     * @return void
     */
    public function testCollectModuleXmlNotFound()
    {
        $testDir = '/testDir';
        $composerPath = $testDir . '/composer.json';
        $moduleXmlPath = $testDir . '/etc/module.xml';
        $testClass = $testDir . '/TestClass.php';

        $reflectionClassMock = $this->createMock(ReflectionClass::class);
        $reflectionClassMock->expects(self::once())
            ->method('getFileName')
            ->willReturn($testClass);

        $this->fileMock->expects(self::once())
            ->method('getParentDirectory')
            ->with($testClass)
            ->willReturn($testDir);
        $this->fileMock->expects(self::exactly(2))
            ->method('isExists')
            ->willReturnCallback(function (string $path) use ($composerPath, $moduleXmlPath) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        $this->assertEquals($composerPath, $path);
                        return true;
                    case 1:
                        $this->assertEquals($moduleXmlPath, $path);
                        return false;
                }
            });
        $this->fileMock->expects(self::once())
            ->method('fileGetContents')
            ->with($composerPath)
            ->willReturn(
                '{"name": "magento/module-test", "type": "magento2-module"}'
            );

        $this->moduleCollector->collect($reflectionClassMock);
        $moduleData = $this->moduleCollector->getModules();
        $this->assertEquals(
            [
                'magento/module-test' =>
                    [
                        'packageName' => 'magento/module-test'
                    ]
            ],
            $moduleData
        );
    }

    /**
     * Checks that an attempt to collect a module's information correctly terminates when a composer.json file is not
     * found in the directories contained in the input class' path.
     *
     * @return void
     */
    public function testCollectComposerJsonNotFound()
    {
        $testDir = '/testDir';
        $testDirComposer = $testDir . '/composer.json';
        $subDir = $testDir . '/subDir';
        $subDirComposer = $subDir . '/composer.json';
        $testClass = $subDir . '/TestClass.php';

        $reflectionClassMock = $this->createMock(ReflectionClass::class);
        $reflectionClassMock->expects(self::once())
            ->method('getFileName')
            ->willReturn($testClass);

        $this->fileMock->expects(self::exactly(3))
            ->method('getParentDirectory')
            ->willReturnCallback(function (string $path) use ($testClass, $subDir, $testDir) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        $this->assertEquals($testClass, $path);
                        return $subDir;
                    case 1:
                        $this->assertEquals($subDir, $path);
                        return $testDir;
                    case 2:
                        $this->assertEquals($testDir, $path);
                        return '/';
                }
            });
        $this->fileMock->expects(self::exactly(3))
            ->method('isExists')
            ->willReturnCallback(function (string $path) use ($subDirComposer, $testDirComposer) {
                static $count = 0;
                switch ($count++) {
                    case 0:
                        $this->assertEquals($subDirComposer, $path);
                        throw new FileSystemException(__('Some exception'));
                    case 1:
                        $this->assertEquals($subDirComposer, $path);
                        return false;
                    case 2:
                        $this->assertEquals($testDirComposer, $path);
                        return false;
                }
            });

        $this->moduleCollector->collect($reflectionClassMock);
        $this->assertEquals(
            [],
            $this->moduleCollector->getModules()
        );
    }
}
