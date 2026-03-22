<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shyim\Mjml\Mjml;
use Shyim\Mjml\MjmlOptions;
use Shyim\Mjml\Validation\ValidationLevel;

abstract class AbstractIntegrationTest extends TestCase
{
    protected function renderMjml(string $mjml): string
    {
        return Mjml::render($mjml, new MjmlOptions(
            validationLevel: ValidationLevel::Skip,
        ))->html;
    }

    /**
     * Extract a CSS style value from an element matching a CSS-like selector in the HTML.
     * Uses DOMXPath under the hood with cssToXPath conversion.
     */
    protected function extractStyleValue(string $html, string $selector, string $property): ?string
    {
        $nodes = $this->querySelectorAll($html, $selector);

        if ($nodes->length === 0) {
            return null;
        }

        $style = $nodes->item(0)?->attributes?->getNamedItem('style')?->nodeValue;

        if ($style === null) {
            return null;
        }

        return $this->extractCssProperty($style, $property);
    }

    /**
     * Extract an HTML attribute from the first element matching a CSS-like selector.
     */
    protected function extractAttribute(string $html, string $selector, string $attribute): ?string
    {
        $nodes = $this->querySelectorAll($html, $selector);

        if ($nodes->length === 0) {
            return null;
        }

        return $nodes->item(0)?->attributes?->getNamedItem($attribute)?->nodeValue;
    }

    /**
     * Basic CSS selector to XPath conversion and query.
     */
    protected function querySelectorAll(string $html, string $selector): \DOMNodeList
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD | \LIBXML_NOERROR);
        $xpath = new \DOMXPath($doc);

        $xpathQuery = $this->cssToXPath($selector);

        $result = $xpath->query($xpathQuery);

        return $result ?: new \DOMNodeList();
    }

    /**
     * Extract a CSS property value from an inline style string.
     */
    protected function extractCssProperty(string $style, string $property): ?string
    {
        $needle = $property . ':';
        $pos = strpos($style, $needle);

        if ($pos === false) {
            return null;
        }

        $start = $pos + strlen($needle);
        $end = strpos($style, ';', $start);

        if ($end === false) {
            return trim(substr($style, $start));
        }

        return trim(substr($style, $start, $end - $start));
    }

    /**
     * Convert a basic CSS selector to XPath.
     * Supports: tag, .class, #id, >, combinators, :first-child, attribute selectors, commas.
     */
    protected function cssToXPath(string $css): string
    {
        // Handle comma-separated selectors
        if (str_contains($css, ',')) {
            $parts = array_map(fn(string $part) => $this->cssToXPath(trim($part)), explode(',', $css));
            return implode(' | ', $parts);
        }

        $css = trim($css);

        // Split by child combinator and descendant
        $tokens = preg_split('/\s+/', $css);
        if ($tokens === false) {
            return '//' . $css;
        }

        $xpath = '';
        $separator = '//';

        foreach ($tokens as $token) {
            if ($token === '>') {
                $separator = '/';
                continue;
            }

            $xpath .= $separator . $this->tokenToXPath($token);
            $separator = '//';
        }

        return $xpath;
    }

    private function tokenToXPath(string $token): string
    {
        // Handle pseudo-selectors
        $pseudo = '';
        if (preg_match('/:first-child$/', $token, $m)) {
            $pseudo = '[1]';
            $token = substr($token, 0, -strlen(':first-child'));
        }

        // Handle attribute selectors like [attr=value]
        $attrCondition = '';
        if (preg_match('/\[([^\]]+)\]/', $token, $m)) {
            $token = preg_replace('/\[([^\]]+)\]/', '', $token) ?? $token;
            $attrParts = explode('=', $m[1], 2);
            if (count($attrParts) === 2) {
                $attrCondition = '[@' . $attrParts[0] . '=' . $attrParts[1] . ']';
            } else {
                $attrCondition = '[@' . $attrParts[0] . ']';
            }
        }

        // Parse tag.class#id combinations
        $tag = '*';
        $conditions = [];

        // Extract tag name
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)/', $token, $m)) {
            $tag = $m[1];
            $token = substr($token, strlen($m[1]));
        }

        // Extract classes
        if (preg_match_all('/\.([a-zA-Z0-9_-]+)/', $token, $m)) {
            foreach ($m[1] as $class) {
                $conditions[] = 'contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")';
            }
        }

        // Extract ID
        if (preg_match('/#([a-zA-Z0-9_-]+)/', $token, $m)) {
            $conditions[] = '@id="' . $m[1] . '"';
        }

        $result = $tag;
        if ($conditions || $attrCondition) {
            $allConditions = [];
            if ($conditions) {
                $allConditions = $conditions;
            }
            if ($attrCondition) {
                $result .= $attrCondition;
            }
            if ($allConditions) {
                $result .= '[' . implode(' and ', $allConditions) . ']';
            }
        }

        return $result . $pseudo;
    }

    /**
     * Collect style values from all matched elements for a given CSS property.
     *
     * @return list<string|null>
     */
    protected function collectStyleValues(string $html, string $selector, string $property): array
    {
        $nodes = $this->querySelectorAll($html, $selector);
        $values = [];

        for ($i = 0; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            $style = $node?->attributes?->getNamedItem('style')?->nodeValue;

            if ($style === null) {
                $values[] = null;
                continue;
            }

            $values[] = $this->extractCssProperty($style, $property);
        }

        return $values;
    }

    /**
     * Collect attribute values from all matched elements.
     *
     * @return list<string|null>
     */
    protected function collectAttributeValues(string $html, string $selector, string $attribute): array
    {
        $nodes = $this->querySelectorAll($html, $selector);
        $values = [];

        for ($i = 0; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            $values[] = $node?->attributes?->getNamedItem($attribute)?->nodeValue;
        }

        return $values;
    }
}
