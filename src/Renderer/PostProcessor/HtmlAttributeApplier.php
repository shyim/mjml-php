<?php

declare(strict_types=1);

namespace Shyim\Mjml\Renderer\PostProcessor;

final class HtmlAttributeApplier
{
    /**
     * Apply HTML attributes to elements matching CSS selectors.
     *
     * @param array<string, array<string, string>> $htmlAttributes Selector => [attr => value]
     */
    public static function apply(string $html, array $htmlAttributes): string
    {
        if ($htmlAttributes === []) {
            return $html;
        }

        // Protect template syntax and other non-HTML content that contains < or >
        // from being mangled by DOMDocument (e.g. "{ if item < 5 }")
        $placeholders = [];
        $counter = 0;
        $protected = preg_replace_callback(
            '/\{[^}]*[<>][^}]*\}/',
            static function (array $match) use (&$placeholders, &$counter): string {
                $key = "___MJML_PLACEHOLDER_{$counter}___";
                $placeholders[$key] = $match[0];
                $counter++;
                return $key;
            },
            $html,
        ) ?? $html;

        $doc = new \DOMDocument();

        // Suppress warnings for HTML5 tags and preserve encoding
        $wrappedHtml = '<?xml encoding="UTF-8">' . $protected;
        @$doc->loadHTML($wrappedHtml, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD | \LIBXML_NOERROR);

        $xpath = new \DOMXPath($doc);

        foreach ($htmlAttributes as $selector => $attributes) {
            $xpathQuery = self::cssToXPath($selector);

            if ($xpathQuery === null) {
                continue;
            }

            $elements = @$xpath->query($xpathQuery);

            if ($elements === false) {
                continue;
            }

            foreach ($elements as $element) {
                if (!$element instanceof \DOMElement) {
                    continue;
                }

                foreach ($attributes as $attrName => $attrValue) {
                    $element->setAttribute($attrName, $attrValue);
                }
            }
        }

        // Extract the body content back
        $result = $doc->saveHTML();

        if ($result === false) {
            return $html;
        }

        // Remove the XML encoding declaration we added
        $result = str_replace('<?xml encoding="UTF-8">', '', $result);

        // DOMDocument wraps content in html/body tags when using loadHTML,
        // so we need to extract just the inner content
        if (preg_match('/<body>([\s\S]*)<\/body>/i', $result, $matches)) {
            $result = $matches[1];
        }

        // Restore protected template syntax
        if ($placeholders !== []) {
            $result = str_replace(array_keys($placeholders), array_values($placeholders), $result);
        }

        return $result;
    }

    /**
     * Convert a basic CSS selector to an XPath expression.
     *
     * Supports: tag selectors, .class selectors, #id selectors, descendant combinators, and combinations.
     */
    private static function cssToXPath(string $selector): ?string
    {
        $selector = trim($selector);

        if ($selector === '') {
            return null;
        }

        // Split by whitespace for descendant combinator support
        $tokens = preg_split('/\s+/', $selector);
        if ($tokens === false || $tokens === []) {
            return null;
        }

        $xpath = '';
        foreach ($tokens as $token) {
            $xpath .= '//' . self::tokenToXPath($token);
        }

        return $xpath;
    }

    private static function tokenToXPath(string $token): string
    {
        $tag = '*';
        $conditions = [];

        // Extract tag name (must be at the start)
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9-]*)/', $token, $m)) {
            $tag = $m[1];
            $token = substr($token, strlen($m[1]));
        }

        // Extract all class, id, and attribute selectors
        while ($token !== '') {
            if ($token[0] === '.') {
                if (preg_match('/^\.([a-zA-Z0-9_-]+)/', $token, $m)) {
                    $conditions[] = 'contains(concat(" ", normalize-space(@class), " "), " ' . $m[1] . ' ")';
                    $token = substr($token, strlen($m[0]));
                } else {
                    break;
                }
            } elseif ($token[0] === '#') {
                if (preg_match('/^#([a-zA-Z0-9_-]+)/', $token, $m)) {
                    $conditions[] = '@id="' . $m[1] . '"';
                    $token = substr($token, strlen($m[0]));
                } else {
                    break;
                }
            } elseif ($token[0] === '[') {
                if (preg_match('/^\[([a-zA-Z0-9_-]+)(?:="([^"]*)")?\]/', $token, $m)) {
                    if (isset($m[2])) {
                        $conditions[] = '@' . $m[1] . '="' . $m[2] . '"';
                    } else {
                        $conditions[] = '@' . $m[1];
                    }
                    $token = substr($token, strlen($m[0]));
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        if ($conditions !== []) {
            return $tag . '[' . implode(' and ', $conditions) . ']';
        }

        return $tag;
    }
}
