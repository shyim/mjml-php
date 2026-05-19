<?php

declare(strict_types=1);

namespace Mjml\Helper;

final class Fonts
{
    /**
     * Build font import tags for the HTML head.
     *
     * Only includes fonts that are actually referenced in the content or inline styles.
     *
     * @param array<string, string> $fonts Font name => URL map
     * @param list<string> $inlineStyles Inline style blocks to scan
     */
    public static function buildFontsTags(string $content, array $inlineStyles, array $fonts): string
    {
        if ($fonts === []) {
            return '';
        }

        $toImport = [];

        foreach ($fonts as $name => $url) {
            // Drop fonts loaded over disallowed URL schemes; emitting them
            // into <link href> would let a malicious mj-font tag land
            // javascript:/file:/etc. content in the document head.
            if (!self::isAllowedFontUrl($url)) {
                continue;
            }

            // Check if font is used in the content or inline styles
            $needle = $name;

            if (str_contains($content, $needle)) {
                $toImport[$name] = $url;
                continue;
            }

            foreach ($inlineStyles as $style) {
                if (str_contains($style, $needle)) {
                    $toImport[$name] = $url;
                    break;
                }
            }
        }

        if ($toImport === []) {
            return '';
        }

        $links = '';
        foreach ($toImport as $url) {
            $links .= "      <link href=\"{$url}\" rel=\"stylesheet\" type=\"text/css\">\n";
        }

        $imports = '';
        foreach ($toImport as $url) {
            $imports .= "        @import url({$url});\n";
        }

        return <<<HTML
    <!--[if !mso]><!-->
{$links}      <style type="text/css">
{$imports}
      </style>
    <!--<![endif]-->
HTML;
    }

    /**
     * Font stylesheets are only loaded from http(s) or protocol-relative URLs.
     * Anything else (javascript:, data:, file:, …) is silently dropped.
     */
    private static function isAllowedFontUrl(string $url): bool
    {
        $trimmed = ltrim($url);

        if ($trimmed === '') {
            return false;
        }

        if (str_starts_with($trimmed, '//')) {
            return true;
        }

        if (!preg_match('/^([a-zA-Z][a-zA-Z0-9+.\-]*):/', $trimmed, $m)) {
            return false; // Relative URL — almost never what a font tag wants
        }

        $scheme = strtolower($m[1]);

        return $scheme === 'http' || $scheme === 'https';
    }
}
