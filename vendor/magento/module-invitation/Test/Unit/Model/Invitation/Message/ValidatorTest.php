<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Test\Unit\Model\Invitation\Message;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Invitation\Model\Invitation\Message\Validator;

/**
 * Validator class for invitation message
 */
class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->validator = $this->objectManager->getObject(
            Validator::class
        );
    }

    /**
     * Validate invitation message
     * @return void
     */
    public function testIsValid()
    {
        $this->assertTrue($this->validator->isValid("https://example.com"));
        $this->assertFalse($this->validator->isValid("Test"));
    }
}
