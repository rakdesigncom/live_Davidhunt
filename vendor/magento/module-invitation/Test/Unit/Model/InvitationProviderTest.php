<?php
/**
 * Copyright 2024 Adobe. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Test\Unit\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Url\DecoderInterface;
use Magento\Invitation\Model\Invitation;
use Magento\Invitation\Model\InvitationProvider;
use PHPUnit\Framework\TestCase;
use Magento\Invitation\Model\InvitationFactory;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class for providing invitation by request.
 */
class InvitationProviderTest extends TestCase
{
    /**
     * Testable Object
     *
     * @var InvitationProvider
     */
    private $invitationProvider;

    /**
     * @var Registry|MockObject
     */
    private Registry|MockObject $registry;

    /**
     * @var InvitationFactory|MockObject
     */
    private InvitationFactory|MockObject $invitationFactory;

    /**
     * @var DecoderInterface|MockObject
     */
    private DecoderInterface|MockObject $urlDecoder;

    /**
     * @var RequestInterface|MockObject
     */
    private RequestInterface|MockObject $request;

    /**
     * @var Invitation|MockObject
     */
    private Invitation|MockObject $invitationModel;

    /**
     * @var ObjectManager
     */
    private ObjectManager $objectManager;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->invitationFactory = $this
            ->getMockBuilder(InvitationFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["create"])
            ->getMock();

        $this->urlDecoder = $this->getMockBuilder(DecoderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["getParam"])
            ->getMockForAbstractClass();

        $this->invitationModel = $this
            ->getMockBuilder(Invitation::class)
            ->addMethods(['getEmail'])
            ->onlyMethods(['loadByInvitationCode','makeSureCanBeAccepted'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->invitationProvider = $this->objectManager->getObject(
            InvitationProvider::class,
            [
                "registry" => $this->registry,
                "invitationFactory" => $this->invitationFactory,
                "urlDecoder" => $this->urlDecoder
            ]
        );
    }

    /**
     * Get invitation instance
     *
     * @return void
     */
    public function testGetInvitation(): void
    {
        $this->registry
            ->expects($this->exactly(2))
            ->method('registry')
            ->willReturnOnConsecutiveCalls(false, $this->invitationModel);

        $this->invitationFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->invitationModel);

        $this->invitationModel->expects($this->once())->method('loadByInvitationCode')
            ->willReturnSelf();

        $this->invitationModel->expects($this->once())->method('makeSureCanBeAccepted')
            ->willReturnSelf();

        $this->invitationProvider->get($this->request);
    }

    /**
     * Invitation exception
     *
     * @return void
     * @throws InputException|LocalizedException
     */
    public function testGetInvitationException(): void
    {
        $this->registry
            ->expects($this->exactly(2))
            ->method('registry')
            ->willReturn($this->invitationModel);

        $this->invitationModel->expects($this->once())->method('getEmail')->willReturn('abc@xyz.com');
        $this->request->expects($this->once())->method('getParam')->with('email', '')->willReturn('xyz@abc.com');
        $this->expectException(InputException::class);
        $this->invitationProvider->get($this->request);
    }
}
