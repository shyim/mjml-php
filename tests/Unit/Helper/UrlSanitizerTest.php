<?php

declare(strict_types=1);

namespace Mjml\Tests\Unit\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Mjml\Helper\UrlSanitizer;

final class UrlSanitizerTest extends TestCase
{
    /**
     * @return list<array{string, string}>
     */
    public static function safeUrlsProvider(): array
    {
        return [
            ['https://example.com/', 'https://example.com/'],
            ['http://example.com', 'http://example.com'],
            ['HTTPS://Example.Com/path?q=1', 'HTTPS://Example.Com/path?q=1'],
            ['mailto:foo@bar.example', 'mailto:foo@bar.example'],
            ['tel:+15551234567', 'tel:+15551234567'],
            ['sms:+15551234567', 'sms:+15551234567'],
            ['ftp://example.com/file', 'ftp://example.com/file'],
            ['cid:image001@example', 'cid:image001@example'],
            ['#anchor', '#anchor'],
            ['//cdn.example.com/img.png', '//cdn.example.com/img.png'],
            ['/relative/path', '/relative/path'],
            ['relative/path.html', 'relative/path.html'],
            ['?query=only', '?query=only'],
            ['data:image/png;base64,AAAA', 'data:image/png;base64,AAAA'],
            ['data:image/svg+xml;utf8,<svg/>', 'data:image/svg+xml;utf8,<svg/>'],
            ['', ''],
        ];
    }

    /**
     * @return list<array{string}>
     */
    public static function dangerousUrlsProvider(): array
    {
        return [
            ['javascript:alert(1)'],
            ['JavaScript:alert(1)'],
            ['  javascript:alert(1)'],
            ['vbscript:msgbox(1)'],
            ['file:///etc/passwd'],
            ['data:text/html,<script>alert(1)</script>'],
            ['data:application/javascript,alert(1)'],
            ["javascript\t:alert(1)"],
            ['chrome://settings'],
            ['about:blank'],
        ];
    }

    #[DataProvider('safeUrlsProvider')]
    public function testSafeUrlsPassThrough(string $input, string $expected): void
    {
        self::assertSame($expected, UrlSanitizer::sanitize($input));
    }

    #[DataProvider('dangerousUrlsProvider')]
    public function testDangerousUrlsAreNeutralized(string $input): void
    {
        self::assertSame('#', UrlSanitizer::sanitize($input));
    }

    public function testIsUrlAttribute(): void
    {
        self::assertTrue(UrlSanitizer::isUrlAttribute('href'));
        self::assertTrue(UrlSanitizer::isUrlAttribute('src'));
        self::assertTrue(UrlSanitizer::isUrlAttribute('background'));
        self::assertFalse(UrlSanitizer::isUrlAttribute('style'));
        self::assertFalse(UrlSanitizer::isUrlAttribute('class'));
    }
}
