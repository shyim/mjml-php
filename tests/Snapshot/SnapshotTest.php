<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Snapshot;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shyim\Mjml\Mjml;
use Shyim\Mjml\MjmlOptions;
use Shyim\Mjml\Validation\ValidationLevel;

/**
 * Snapshot tests that compare PHP rendering output against the original JS MJML CLI output.
 *
 * Reference HTML files are generated with: npx mjml <fixture>.mjml -o <fixture>.html
 * using mjml-core 4.18.0 / mjml-cli 4.18.0
 *
 * To regenerate reference files:
 *   for f in tests/Snapshot/Fixtures/*.mjml; do npx mjml "$f" -o "${f%.mjml}.html"; done
 */
final class SnapshotTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/Fixtures';

    #[DataProvider('fixtureProvider')]
    public function testSnapshotMatchesJsOutput(string $name, string $mjmlFile, string $htmlFile): void
    {
        $mjml = file_get_contents($mjmlFile);
        self::assertNotFalse($mjml, "Could not read MJML fixture: {$mjmlFile}");

        $expectedHtml = file_get_contents($htmlFile);
        self::assertNotFalse($expectedHtml, "Could not read reference HTML: {$htmlFile}. Run: npx mjml {$mjmlFile} -o {$htmlFile}");

        $result = Mjml::render($mjml, new MjmlOptions(
            validationLevel: ValidationLevel::Skip,
        ));

        $expected = self::normalizeHtml($expectedHtml);
        $actual = self::normalizeHtml($result->html);

        self::assertSame(
            $expected,
            $actual,
            sprintf(
                "Snapshot mismatch for '%s'.\n\nTo debug, compare:\n  - Expected (JS): %s\n  - Actual (PHP):  run Mjml::render() on %s\n",
                $name,
                $htmlFile,
                $mjmlFile,
            ),
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function fixtureProvider(): iterable
    {
        $dir = self::FIXTURES_DIR;

        foreach (glob("{$dir}/*.mjml") as $mjmlFile) {
            $name = basename($mjmlFile, '.mjml');
            $htmlFile = "{$dir}/{$name}.html";

            if (!file_exists($htmlFile)) {
                continue;
            }

            yield $name => [$name, $mjmlFile, $htmlFile];
        }
    }

    /**
     * Normalize HTML for comparison.
     *
     * This handles insignificant whitespace differences between
     * the JS and PHP implementations while preserving meaningful structure.
     */
    private static function normalizeHtml(string $html): string
    {
        // Normalize line endings
        $html = str_replace("\r\n", "\n", $html);

        // Remove trailing whitespace on each line
        $html = preg_replace('/[ \t]+$/m', '', $html) ?? $html;

        // Collapse multiple blank lines into one
        $html = preg_replace('/\n{3,}/', "\n\n", $html) ?? $html;

        // Normalize CSS inside <style> blocks (beautifier adds spaces around colons/semicolons)
        $html = preg_replace_callback(
            '/<style([^>]*)>(.*?)<\/style>/s',
            static function (array $m): string {
                $css = $m[2];
                // Normalize spaces around colons and semicolons in CSS
                $css = preg_replace('/\s*:\s*/', ':', $css) ?? $css;
                $css = preg_replace('/\s*;\s*/', ';', $css) ?? $css;
                // Normalize spaces around braces
                $css = preg_replace('/\s*\{\s*/', ' { ', $css) ?? $css;
                $css = preg_replace('/\s*\}\s*/', ' } ', $css) ?? $css;
                // Normalize spaces around CSS combinators (+, >, ~)
                $css = preg_replace('/\s*\+\s*/', '+', $css) ?? $css;
                $css = preg_replace('/\s*~\s*/', '~', $css) ?? $css;
                $css = preg_replace('/\s*>\s*/', '>', $css) ?? $css;
                // Collapse multiple spaces
                $css = preg_replace('/\s+/', ' ', $css) ?? $css;
                $css = trim($css);

                return '<style' . $m[1] . '>' . $css . '</style>';
            },
            $html,
        ) ?? $html;

        // Normalize whitespace inside style attributes (remove extra spaces around semicolons/colons)
        $html = preg_replace_callback(
            '/style="([^"]*)"/',
            static function (array $m): string {
                $style = $m[1];
                // Normalize spaces around colons and semicolons in inline styles
                $style = preg_replace('/\s*;\s*/', ';', $style) ?? $style;
                $style = preg_replace('/\s*:\s*/', ':', $style) ?? $style;
                // Remove trailing semicolon
                $style = rtrim($style, ';');
                // Sort properties for stable comparison
                $props = array_filter(explode(';', $style));
                sort($props);
                $style = implode(';', $props);

                return 'style="' . $style . '"';
            },
            $html,
        ) ?? $html;

        // Remove empty class attributes (beautifier artifact from JS)
        $html = preg_replace('/ class=""/', '', $html) ?? $html;

        // Remove trailing space before > (beautifier artifact)
        $html = preg_replace('/\s+>/', '>', $html) ?? $html;

        // Normalize space before !important in CSS (inside <style> blocks already handled above)
        $html = str_replace('!important', ' !important', $html);
        $html = str_replace('  !important', ' !important', $html);

        // Normalize whitespace around text content (beautifier adds spaces)
        $html = preg_replace('/>\s+/', '>', $html) ?? $html;
        $html = preg_replace('/\s+</', '<', $html) ?? $html;

        // Normalize spaces in tag attributes
        $html = preg_replace('/\s+/', ' ', $html) ?? $html;

        return trim($html);
    }
}
