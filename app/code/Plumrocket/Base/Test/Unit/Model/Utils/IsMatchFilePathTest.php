<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2022 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Test\Unit\Model\Utils;

use PHPUnit\Framework\TestCase;
use Plumrocket\Base\Model\Utils\IsMatchFilePath;

/**
 * @since 2.9.3
 */
class IsMatchFilePathTest extends TestCase
{
    /**
     * @var \Plumrocket\Base\Model\Utils\IsMatchFilePath
     */
    private $isMatchFilePath;

    protected function setUp(): void
    {
        $this->isMatchFilePath = new IsMatchFilePath();
    }

    public function testExactPath(): void
    {
        self::assertTrue($this->isMatchFilePath->execute('/test/logo.png', '/test/logo.png'));
    }

    public function testExactRelativePath(): void
    {
        self::assertTrue($this->isMatchFilePath->execute('test/logo.png', 'test/logo.png'));
    }

    /**
     * @dataProvider regexPatternProvider
     *
     * @param string $filePath
     * @param string $pattern
     * @param bool   $result
     */
    public function testRegexPattern(
        string $filePath,
        string $pattern,
        bool $result
    ): void {
        self::assertSame(
            $result,
            $this->isMatchFilePath->execute($filePath, $pattern),
            "Url: '$filePath', pattern: '$pattern'"
        );
    }

    /**
     * @return \Generator
     */
    public function regexPatternProvider(): \Generator
    {
        yield [
            'filePath' => 'test/logo',
            'pattern'  => 'test/*',
            'result'   => true,
        ];
        yield [
            'filePath' => '/test/logo',
            'pattern'  => 'test/*',
            'result'   => false,
        ];
        yield [
            'filePath' => 'test/logo',
            'pattern'  => '/test/*',
            'result'   => false,
        ];
        yield [
            'filePath' => 'a/test/logo',
            'pattern'  => '*/test/*',
            'result'   => true,
        ];
    }
}
