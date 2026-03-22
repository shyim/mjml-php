<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Unit\Renderer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shyim\Mjml\Context\GlobalContext;
use Shyim\Mjml\MjmlOptions;
use Shyim\Mjml\Renderer\Skeleton;

final class SkeletonTest extends TestCase
{
    /**
     * @return iterable<string, array{GlobalContext, int}>
     */
    public static function styleCountProvider(): iterable
    {
        // Base case: default context produces 1 style tag (the base reset styles)
        yield 'default context' => [new GlobalContext(), 1];

        // componentsHeadStyle adds 1 extra style tag
        $ctx = new GlobalContext();
        $ctx->componentsHeadStyle[] = static fn(string $breakpoint): string => '.custom-component-1 .custom-child { background: red; }';
        yield 'with componentsHeadStyle' => [$ctx, 2];

        // headStyle adds 1 extra style tag
        $ctx = new GlobalContext();
        $ctx->headStyle['custom-component'] = static fn(string $breakpoint): string => '.custom-component .custom-child { background: orange; }';
        yield 'with headStyle' => [$ctx, 2];

        // componentsHeadStyle + headStyle are combined into 1 style tag
        $ctx = new GlobalContext();
        $ctx->componentsHeadStyle[] = static fn(string $breakpoint): string => '.custom-component-1 .custom-child { background: yellow; }';
        $ctx->headStyle['custom-component'] = static fn(string $breakpoint): string => '.custom-component .custom-child { background: green; }';
        yield 'with componentsHeadStyle and headStyle' => [$ctx, 2];

        // style array adds 1 extra style tag
        $ctx = new GlobalContext();
        $ctx->styles[] = '#title { background: blue; }';
        yield 'with styles' => [$ctx, 2];

        // All three together: componentsHeadStyle+headStyle combined + styles = 2 extra
        $ctx = new GlobalContext();
        $ctx->componentsHeadStyle[] = static fn(string $breakpoint): string => '.custom-component-1 .custom-child { background: purple; }';
        $ctx->headStyle['custom-component'] = static fn(string $breakpoint): string => '.custom-component .custom-child { background: black; }';
        $ctx->styles[] = '#title { background: white; }';
        yield 'with all style sources' => [$ctx, 3];
    }

    #[DataProvider('styleCountProvider')]
    public function testCorrectNumberOfStyleTags(GlobalContext $context, int $expectedStyleCount): void
    {
        $skeleton = new Skeleton();
        $html = $skeleton->build('', $context, new MjmlOptions());

        $dom = new \DOMDocument();
        // Suppress warnings from HTML5 parsing and conditional comments
        @$dom->loadHTML($html, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);

        $styleTags = $dom->getElementsByTagName('style');

        // The conditional comment style tags (<!--[if mso]> and <!--[if lte mso 11]>)
        // are not parsed by DOMDocument, matching the JS test behavior with cheerio
        self::assertSame($expectedStyleCount, $styleTags->length);
    }
}
