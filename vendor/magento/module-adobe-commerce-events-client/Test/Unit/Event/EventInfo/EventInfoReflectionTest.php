<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Event\EventInfo;

use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceEventsClient\Event\EventInfo\EventInfoExtenderInterface;
use Magento\AdobeCommerceEventsClient\Event\EventInfo\EventInfoReflection;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Converter\ClassToArrayConverterInterface;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Converter\EventCodeConverter;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util\CaseConverter;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util\ReflectionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class EventInfoReflectionTest extends TestCase
{
    /**
     * @var Event|MockObject
     */
    private $eventMock;

    /**
     * @var ReflectionHelper|MockObject
     */
    private $reflectionHelperMock;

    /**
     * @var ClassToArrayConverterInterface|MockObject
     */
    private $classToArrayConverterMock;

    /**
     * @var EventInfoExtenderInterface|MockObject
     */
    private EventInfoExtenderInterface|MockObject $eventInfoExtenderMock;

    /**
     * @var EventInfoReflection
     */
    private EventInfoReflection $eventInfoReflection;

    protected function setUp(): void
    {
        $this->eventMock = $this->createMock(Event::class);
        $this->reflectionHelperMock = $this->createMock(ReflectionHelper::class);
        $this->classToArrayConverterMock = $this->createMock(ClassToArrayConverterInterface::class);
        $this->eventInfoExtenderMock = $this->createMock(EventInfoExtenderInterface::class);

        $this->eventInfoReflection = new EventInfoReflection(
            $this->reflectionHelperMock,
            $this->classToArrayConverterMock,
            new EventCodeConverter(new CaseConverter()),
            $this->eventInfoExtenderMock
        );
    }

    public function testObserverEventInfo(): void
    {
        $eventClassEmitter = 'Path\To\Some\Class';
        $this->classToArrayConverterMock->expects(self::once())
            ->method('convert')
            ->with($eventClassEmitter)
            ->willReturn(['id' => 1]);
        $this->eventInfoExtenderMock->expects(self::once())
            ->method('extend')
            ->with($eventClassEmitter, ['id' => 1])
            ->willReturn(['id' => 1, 'extended' => true]);

        self::assertEquals(
            ['id' => 1, 'extended' => true],
            $this->eventInfoReflection->getInfoForObserverEvent($eventClassEmitter)
        );
    }

    public function testPluginEventInfoNotFound(): void
    {
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('magento.catalog.model.resource_model.categor.save');
        $this->eventInfoExtenderMock->expects(self::never())
            ->method('extend');
        $this->expectException(ReflectionException::class);

        $this->eventInfoReflection->getPayloadInfo($this->eventMock);
    }

    public function testPayloadInfo(): void
    {
        $eventInfoData = [
            'id' => '1',
            'event_data' => 'test',
            'event_code' => 'test',
        ];
        $this->eventMock->expects(self::any())
            ->method('getName')
            ->willReturn('plugin.magento.adobe_commerce_events_client.api.event_repository.save');
        $returnType = 'Magento\AdobeCommerceEventsClient\Api\Data\EventInterface';
        $this->reflectionHelperMock->expects(self::once())
            ->method('getReturnType')
            ->willReturn($returnType);
        $this->classToArrayConverterMock->expects(self::once())
            ->method('convert')
            ->with($returnType, 3)
            ->willReturn($eventInfoData);
        $this->eventInfoExtenderMock->expects(self::once())
            ->method('extend')
            ->with($returnType, $eventInfoData)
            ->willReturn($eventInfoData);

        $info = $this->eventInfoReflection->getPayloadInfo($this->eventMock, 3);

        self::assertArrayHasKey('id', $info);
        self::assertArrayHasKey('event_data', $info);
        self::assertArrayHasKey('event_code', $info);
    }
}
