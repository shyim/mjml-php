<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Unit\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shyim\Mjml\Helper\WidthParser;

final class WidthParserTest extends TestCase
{
    /**
     * @return iterable<string, array{string, float, string}>
     */
    public static function widthProvider(): iterable
    {
        yield '1px' => ['1px', 1.0, 'px'];
        yield '33.3px' => ['33.3px', 33.0, 'px']; // parseInt truncates decimals for px
        yield '33.3%' => ['33.3%', 33.3, '%']; // parseFloat preserves decimals for %
        yield '100%' => ['100%', 100.0, '%'];
        yield '600px' => ['600px', 600.0, 'px'];
    }

    #[DataProvider('widthProvider')]
    public function testParse(string $input, float $expectedValue, string $expectedUnit): void
    {
        $result = WidthParser::parse($input);

        self::assertSame($expectedValue, $result['value']);
        self::assertSame($expectedUnit, $result['unit']);
    }

    public function testParseFloatToIntDisabled(): void
    {
        $result = WidthParser::parse('33.3px', parseFloatToInt: false);
        self::assertSame(33.3, $result['value']);
        self::assertSame('px', $result['unit']);
    }
}
