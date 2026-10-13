<?php
/**
 * @package     Plumrocket_Base
 * @copyright   Copyright (c) 2023 Plumrocket Inc. (https://plumrocket.com)
 * @license     https://plumrocket.com/license   End-user License Agreement
 */

declare(strict_types=1);

namespace Plumrocket\Base\Test\Unit\Model\Utils;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Plumrocket\Base\Model\Utils\HtmlTag;
use Plumrocket\Base\Test\Unit\HtmlAsserts;

/**
 * @since 2.11.0
 */
class HtmlTagTest extends TestCase
{

    use HtmlAsserts;

    /**
     * @var \Plumrocket\Base\Model\Utils\HtmlTag
     */
    private $model;

    protected function setUp(): void
    {
        $this->model = new HtmlTag();
    }

    /**
     * @covers       \Plumrocket\Base\Model\Utils\HtmlTag::getAll
     * @dataProvider perhapsValidHtml()
     * @dataProvider validHtml()
     * @param string $case
     * @param mixed  $html
     * @param array  $images
     */
    public function testGetImage(string $case, string $html, array $images): void
    {
        self::assertSame($images, $this->model->getAll('img', $html), $case);
    }

    /**
     * @covers       \Plumrocket\Base\Model\Utils\HtmlTag::isExistElement
     * @dataProvider isExistsElementProvider()
     * @param string $case
     * @param mixed  $html
     * @param bool   $result
     */
    public function testIsExistElement(string $case, string $html, bool $result): void
    {
        self::assertSame($result, $this->model->isExistElement('head', $html), $case);
    }

    /**
     * @covers \Plumrocket\Base\Model\Utils\HtmlTag::get
     * @covers \Plumrocket\Base\Model\Utils\HtmlTag::getTagContent
     * @covers \Plumrocket\Base\Model\Utils\HtmlTag::getTagContents
     * @covers \Plumrocket\Base\Model\Utils\HtmlTag::getAllWithContent
     */
    public function testSearch(): void
    {
        $html = <<<HTML
<html>
<head></head>
<body>
<header>
    <div class="menu">
        <nav>
            <li>Home</li>
            <li class="nav-item">Shop</li>
            <li class="nav-item">About Us</li>
        </nav>
    </div>
    <div class="logo"></div>
</header>
<img src="test.png"/>
</body>
</html>
HTML;
        self::assertSame('<li>Home</li>', $this->model->get('li', $html));
        self::assertSame('<img src="test.png"/>', $this->model->get('img', $html));
        self::assertSame(
            ['<li class="nav-item">Shop</li>', '<li class="nav-item">About Us</li>'],
            $this->model->getAllWithContent('li', $html, 'class="nav-item"')
        );
        self::assertSame('Home', $this->model->getTagContent('li', $html));
        self::assertSame(['Home', 'Shop', 'About Us'], $this->model->getTagContents('li', $html));
    }

    /**
     * @covers \Plumrocket\Base\Model\Utils\HtmlTag::cut
     * @covers \Plumrocket\Base\Model\Utils\HtmlTag::cutAll
     */
    public function testCutting(): void
    {
        self::assertSame(
            ['<li>Home</li>', '<nav><li>Shop</li></nav>'],
            $this->model->cut('li', '<nav><li>Home</li><li>Shop</li></nav>')
        );
        self::assertSame(
            [['<li>Home</li>', '<li>Shop</li>'], '<nav></nav>'],
            $this->model->cutAll('li', '<nav><li>Home</li><li>Shop</li></nav>')
        );
        self::assertSame(
            ['<li>Shop</li>', '<nav><li>Home</li></nav>'],
            $this->model->cutWithContent('li', '<nav><li>Home</li><li>Shop</li></nav>', 'Shop')
        );
    }

    /**
     * @return void
     */
    public function testAppend(): void
    {
        self::assertSameHtml(
            '<head><title>Home Page</title></head>',
            $this->model->append('<head></head>', 'head', '<title>Home Page</title>')
        );
        self::assertSameHtml(
            '<head><meta charset="utf-8"/><title>Home Page</title></head>',
            $this->model->append('<head><meta charset="utf-8"/></head>', 'head', '<title>Home Page</title>')
        );
    }

    /**
     * @covers \Plumrocket\Base\Model\Utils\HtmlTag::append
     * @return void
     */
    public function testAppendToNotExistingElement(): void
    {
        $this->expectException(LocalizedException::class);
        $this->model->append('<head><meta charset="utf-8"/></head>', 'body', '<title>Home Page</title>');
    }

    /**
     * @return void
     */
    public function testPrepend(): void
    {
        self::assertSameHtml(
            '<head><title>Home Page</title></head>',
            $this->model->prepend('<head></head>', 'head', '<title>Home Page</title>')
        );
        self::assertSameHtml(
            '<head><title>Home Page</title><meta charset="utf-8"/></head>',
            $this->model->prepend('<head><meta charset="utf-8"/></head>', 'head', '<title>Home Page</title>')
        );
    }

    /**
     * @covers \Plumrocket\Base\Model\Utils\HtmlTag::prepend
     * @return void
     */
    public function testPrependToNotExistingElement(): void
    {
        $this->expectException(LocalizedException::class);
        $this->model->prepend('<head><meta charset="utf-8"/></head>', 'body', '<title>Home Page</title>');
    }

    /**
     * @dataProvider getAttributeProvider
     * @param string $case
     * @param string $tagHtml
     * @param string $attribute
     * @param string $expectedValue
     * @return void
     */
    public function testGetAttribute(string $case, string $tagHtml, string $attribute, string $expectedValue): void
    {
        self::assertSame($expectedValue, $this->model->getAttribute($tagHtml, $attribute), $case);
    }

    /**
     * @dataProvider getAllAttributesProvider
     * @param string $case
     * @param string $tagHtml
     * @param array  $attributes
     * @param array  $expectedValue
     * @return void
     */
    public function testGetAllAttributes(string $case, string $tagHtml, array $attributes, array $expectedValue): void
    {
        self::assertSame($expectedValue, $this->model->getAttributes($tagHtml, $attributes), $case);
    }

    /**
     * @dataProvider setAttributeProvider
     * @param string $case
     * @param string $tagHtml
     * @param string $attribute
     * @param string $value
     * @param string $expectedTagHtml
     * @return void
     */
    public function testSetAttribute(
        string $case,
        string $tagHtml,
        string $attribute,
        string $value,
        string $expectedTagHtml
    ): void {
        self::assertSame($expectedTagHtml, $this->model->setAttribute($tagHtml, $attribute, $value), $case);
    }

    /**
     * @dataProvider removeAttributeProvider
     * @param string $case
     * @param string $attributeName
     * @param string $html
     * @param string $expectedHtml
     * @return void
     */
    public function testRemoveAttribute(
        string $case,
        string $attributeName,
        string $html,
        string $expectedHtml
    ): void {
        self::assertSame($expectedHtml, $this->model->removeAttribute($html, $attributeName), $case);
    }

    public function perhapsValidHtml(): \Generator
    {
        yield [
            'case' => 'Empty html',
            'html' => '',
            'images' => [],
        ];
        yield [
            'case' => 'Only head',
            'html' => '<head></head>',
            'images' => [],
        ];
        yield [
            'case' => 'Head and body',
            'html' => '<head></head><body></body>',
            'images' => [],
        ];
        yield [
            'case' => 'Empty image',
            'html' => '<head></head><body><img></body>',
            'images' => ['<img>'],
        ];
    }

    public function validHtml(): \Generator
    {
        yield [
            'case' => 'No hero images',
            'html' => <<<HTML
<html>
<head></head>
<body>
<img src="test.png"/>
</body>
</html>
HTML
            ,
            'images' => ['<img src="test.png"/>'],
        ];
        yield [
            'case' => 'One hero image',
            'html' => <<<HTML
<html>
<head></head>
<body>
<img src="test.png" data-hero/>
</body>
</html>
HTML
            ,
            'images' => ['<img src="test.png" data-hero/>'],
        ];

        yield [
            'case' => 'Single quote',
            'html' => <<<HTML
<html>
<head></head>
<body>
<img src='test.png' data-hero/>
</body>
</html>
HTML
            ,
            'images' => ['<img src=\'test.png\' data-hero/>'],
        ];

        yield [
            'case' => 'Duplicate of hero images',
            'html' => <<<HTML
<html>
<head></head>
<body>
<img src="test.png" data-hero/>
<img src="test.png" data-hero/>
</body>
</html>
HTML
            ,

            'images' => ['<img src="test.png" data-hero/>', '<img src="test.png" data-hero/>'],
        ];
        yield [
            'case' => 'Test data-media attribute',
            'html' => <<<HTML
<html>
<head></head>
<body>
<img src="test.png" data-hero data-media="(max-width: 599px)"/>
</body>
</html>
HTML
            ,
            'images' => ['<img src="test.png" data-hero data-media="(max-width: 599px)"/>'],
        ];
    }

    public function isExistsElementProvider(): \Generator
    {
        yield [
            'case' => 'Empty HTML',
            'html' => '',
            'result' => false,
        ];

        yield [
            'case' => 'No Element',
            'html' => <<<HTML
<html>
<body>
<img src="test.png"/>
</body>
</html>
HTML
            ,
            'result' => false,
        ];

        yield [
            'case' => 'With element',
            'html' => <<<HTML
<html>
<head></head>
<body>
<img src="test.png"/>
</body>
</html>
HTML
            ,
            'result' => true,
        ];

        yield [
            'case' => 'With similar element',
            'html' => <<<HTML
<html>
<body>
<header></header>
<img src="test.png"/>
</body>
</html>
HTML
            ,
            'result' => false,
        ];
    }

    public function getAttributeProvider(): \Generator
    {
        yield [
            'case' => 'Empty tag',
            'tagHtml' => '',
            'attribute' => 'class',
            'expectedValue' => '',
        ];
        yield [
            'case' => 'Self-closing tag',
            'tagHtml' => '<img src="test.png">',
            'attribute' => 'src',
            'expectedValue' => 'test.png',
        ];
        yield [
            'case' => 'Empty tag',
            'tagHtml' => '<div class="active"></div>',
            'attribute' => 'class',
            'expectedValue' => 'active',
        ];
        yield [
            'case' => 'Single quotes',
            'tagHtml' => '<div class=\'active\'></div>',
            'attribute' => 'class',
            'expectedValue' => 'active',
        ];
        yield [
            'case' => 'No attribute',
            'tagHtml' => '<div data-class=\'active\'></div>',
            'attribute' => 'class',
            'expectedValue' => '',
        ];
        yield [
            'case' => 'Real URL',
            'tagHtml' => '<img height="1" width="1" src="https://example.com/tr?id=1&ev=PageView&noscript=1"/>',
            'attribute' => 'src',
            'expectedValue' => 'https://example.com/tr?id=1&ev=PageView&noscript=1',
        ];
    }

    public function getAllAttributesProvider(): \Generator
    {
        yield [
            'case' => 'Empty tag',
            'tagHtml' => '',
            'attributes' => ['class'],
            'expectedValue' => ['class' => ''],
        ];
        yield [
            'case' => 'Self-closing tag',
            'tagHtml' => '<img src="test.png">',
            'attributes' => ['src'],
            'expectedValue' => ['src' => 'test.png'],
        ];
        yield [
            'case' => 'Empty tag',
            'tagHtml' => '<div class="active"></div>',
            'attributes' => ['class'],
            'expectedValue' => ['class' => 'active'],
        ];
        yield [
            'case' => 'Single quotes',
            'tagHtml' => '<div class=\'active\'></div>',
            'attributes' => ['class'],
            'expectedValue' => ['class' => 'active'],
        ];
        yield [
            'case' => 'No attribute',
            'tagHtml' => '<div data-class=\'active\'></div>',
            'attributes' => ['src'],
            'expectedValue' => ['src' => ''],
        ];
        yield [
            'case' => 'Real URL',
            'tagHtml' => '<img height="1" width="1" src="https://example.com/tr?id=1&ev=PageView&noscript=1"/>',
            'attributes' => ['height', 'width', 'src', 'loading'],
            'expectedValue' => [
                'height' => '1',
                'width' => '1',
                'src' => 'https://example.com/tr?id=1&ev=PageView&noscript=1',
                'loading' => '',
            ],
        ];
    }

    public function setAttributeProvider(): \Generator
    {
        yield [
            'case' => 'Replace value',
            'tagHtml' => '<img src="test.png">',
            'attribute' => 'src',
            'value' => 'test.webp',
            'expectedTagHtml' => '<img src="test.webp">',
        ];
        yield [
            'case' => 'Create attribute',
            'tagHtml' => '<img src="test.png">',
            'attribute' => 'class',
            'value' => 'active image',
            'expectedTagHtml' => '<img class="active image" src="test.png">',
        ];
        yield [
            'case' => 'Create attribute',
            'tagHtml' => '<div></div>',
            'attribute' => 'class',
            'value' => 'active image',
            'expectedTagHtml' => '<div class="active image"></div>',
        ];
        yield [
            'case' => 'Multiline',
            'tagHtml' => '<img class="product-image-photo"
                src="https://example.com/media/catalog/product/cache/dce8fe532741e80a8cf2c4e49431c89d/w/j/blue.jpg"
                loading="lazy"
                width="240"
                height="300"
                alt="Olivia&#x20;1&#x2F;4&#x20;Zip&#x20;Light&#x20;Jacket"/>',
            'attribute' => 'src',
            'value' => 'data:image/png;base64,' .
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
            'expectedTagHtml' => '<img class="product-image-photo"
                src="data:image/png;base64,' .
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII="
                loading="lazy"
                width="240"
                height="300"
                alt="Olivia&#x20;1&#x2F;4&#x20;Zip&#x20;Light&#x20;Jacket"/>',
        ];
    }

    public function removeAttributeProvider(): \Generator
    {
        yield [
            'case' => 'Remove existing attribute',
            'attributeName' => 'class',
            'html' => '<img src="https://example.com/banner.png" class="image">',
            'expectedHtml' => '<img src="https://example.com/banner.png">',
        ];
        yield [
            'case' => 'Remove not-existing attribute',
            'attributeName' => 'test',
            'html' => '<img src="https://example.com/banner.png" alt="test">',
            'expectedHtml' => '<img src="https://example.com/banner.png" alt="test">',
        ];
        yield [
            'case' => 'Remove attribute without a value',
            'attributeName' => 'checked',
            'html' => '<input type="checkbox" checked>',
            'expectedHtml' => '<input type="checkbox">',
        ];
        yield [
            'case' => 'Remove empty attribute',
            'attributeName' => 'alt',
            'html' => '<input type="checkbox" alt="">',
            'expectedHtml' => '<input type="checkbox">',
        ];
        yield [
            'case' => 'Multiline tag',
            'attributeName' => 'class',
            'html' => <<<HTML
<img src="https://example.com/banner.png"
     data-hero
     class="image"
     width="456"
     height="456">
HTML
            ,
            'expectedHtml' => <<<HTML
<img src="https://example.com/banner.png"
     data-hero
     width="456"
     height="456">
HTML
            ,
        ];
    }
}
