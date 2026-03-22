<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Unit\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shyim\Mjml\Helper\CssParser;

final class CssParserTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function shorthandProvider(): iterable
    {
        // 1 value: all directions get the same value
        yield '1 value - top' => ['1px', 'top', 1];
        yield '1 value - right' => ['1px', 'right', 1];
        yield '1 value - bottom' => ['1px', 'bottom', 1];
        yield '1 value - left' => ['1px', 'left', 1];

        // 2 values: top/bottom = first, right/left = second
        yield '2 values - top' => ['1px 0', 'top', 1];
        yield '2 values - right' => ['1px 0', 'right', 0];
        yield '2 values - bottom' => ['1px 0', 'bottom', 1];
        yield '2 values - left' => ['1px 0', 'left', 0];

        // 3 values: top = first, right/left = second, bottom = third
        yield '3 values - top' => ['1px 2px 3px', 'top', 1];
        yield '3 values - right' => ['1px 2px 3px', 'right', 2];
        yield '3 values - bottom' => ['1px 2px 3px', 'bottom', 3];
        yield '3 values - left' => ['1px 2px 3px', 'left', 2];

        // 4 values: top, right, bottom, left
        yield '4 values - top' => ['1px 2px 3px 4px', 'top', 1];
        yield '4 values - right' => ['1px 2px 3px 4px', 'right', 2];
        yield '4 values - bottom' => ['1px 2px 3px 4px', 'bottom', 3];
        yield '4 values - left' => ['1px 2px 3px 4px', 'left', 4];

        // Extra whitespace should be handled
        yield 'extra whitespace - top' => [' 1px 2px  3px 4px ', 'top', 1];
        yield 'extra whitespace - right' => [' 1px 2px  3px 4px ', 'right', 2];
        yield 'extra whitespace - bottom' => [' 1px 2px  3px 4px ', 'bottom', 3];
        yield 'extra whitespace - left' => [' 1px 2px  3px 4px ', 'left', 4];
    }

    #[DataProvider('shorthandProvider')]
    public function testParseShorthand(string $input, string $direction, int $expected): void
    {
        $result = CssParser::parseShorthand($input, $direction);

        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function borderWidthProvider(): iterable
    {
        yield 'simple border width' => ['1px solid #000', 1];
        yield 'larger border width' => ['5px dashed red', 5];
        yield 'zero border' => ['0', 0];
        yield 'empty string' => ['', 0];
        yield 'width only' => ['3px', 3];
    }

    #[DataProvider('borderWidthProvider')]
    public function testParseBorderWidth(string $input, int $expected): void
    {
        $result = CssParser::parseBorderWidth($input);

        self::assertSame($expected, $result);
    }
}
