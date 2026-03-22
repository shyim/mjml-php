<?php

declare(strict_types=1);

namespace Shyim\Mjml\Parser;

use Shyim\Mjml\Component\ComponentRegistry;

final class MjmlParser
{
    /** @var list<string> Stack for circular include detection */
    private array $includeStack = [];

    public function __construct(
        private readonly ComponentRegistry $registry,
    ) {}

    /**
     * Parse an MJML string into a Node tree.
     */
    public function parse(string $mjml, ?string $filePath = null): Node
    {
        // Pre-process: extract ending tag content before XML parsing
        $mjml = $this->extractEndingTagContent($mjml);

        // Suppress XML errors and handle them ourselves
        $previousUseErrors = libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $doc->loadXML($mjml, \LIBXML_NONET | \LIBXML_NOWARNING);

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);

        $root = $doc->documentElement;

        if ($root === null) {
            return new Node(tagName: 'mjml', file: $filePath);
        }

        return $this->domToNode($root, $filePath);
    }

    /**
     * Convert a DOMElement to our Node tree.
     */
    private function domToNode(\DOMElement $element, ?string $filePath): Node
    {
        $attributes = [];
        foreach ($element->attributes ?? [] as $attr) {
            /** @var \DOMAttr $attr */
            $attributes[$attr->name] = $attr->value;
        }

        // Check for ending tag content stored as special attribute
        $content = '';
        if (isset($attributes['__mjml_content__'])) {
            $content = base64_decode($attributes['__mjml_content__']);
            unset($attributes['__mjml_content__']);
        }

        $children = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                // Handle mj-include
                if ($child->tagName === 'mj-include') {
                    $includedNode = $this->handleInclude($child, $filePath);
                    if ($includedNode !== null) {
                        $children[] = $includedNode;
                    }
                    continue;
                }

                $children[] = $this->domToNode($child, $filePath);
            } elseif ($child instanceof \DOMText && trim($child->textContent) !== '') {
                // Text content outside of ending tags
                if ($content === '') {
                    $content = $child->textContent;
                }
            }
        }

        $node = new Node(
            tagName: $element->tagName,
            attributes: $attributes,
            children: $children,
            content: $content,
            line: $element->getLineNo(),
            file: $filePath,
        );

        // Set parent references
        foreach ($children as $child) {
            $child->parent = $node;
        }

        return $node;
    }

    /**
     * Handle mj-include elements by loading and parsing the referenced file.
     */
    private function handleInclude(\DOMElement $element, ?string $currentFile): ?Node
    {
        $path = $element->getAttribute('path');

        if ($path === '') {
            return null;
        }

        // Resolve relative path
        if ($currentFile !== null && !str_starts_with($path, '/')) {
            $path = \dirname($currentFile) . '/' . $path;
        }

        $realPath = realpath($path);

        if ($realPath === false || !is_file($realPath)) {
            return null;
        }

        // Circular include detection
        if (\in_array($realPath, $this->includeStack, true)) {
            return null;
        }

        $this->includeStack[] = $realPath;

        $type = $element->getAttribute('type');
        $fileContent = file_get_contents($realPath);

        if ($fileContent === false) {
            array_pop($this->includeStack);
            return null;
        }

        $result = match ($type) {
            'css' => $this->handleCssInclude($fileContent, $element->getAttribute('css-inline') === 'inline'),
            'html' => new Node(tagName: 'mj-raw', content: $fileContent, file: $realPath),
            default => $this->parse($fileContent, $realPath),
        };

        array_pop($this->includeStack);

        return $result;
    }

    /**
     * Handle CSS file includes.
     */
    private function handleCssInclude(string $css, bool $inline): Node
    {
        return new Node(
            tagName: 'mj-style',
            attributes: $inline ? ['inline' => 'inline'] : [],
            content: $css,
        );
    }

    /**
     * Extract content from ending tags and store as base64-encoded attribute.
     *
     * This is needed because ending tag content (like mj-text, mj-button) can contain
     * arbitrary HTML that would break XML parsing.
     */
    private function extractEndingTagContent(string $mjml): string
    {
        // Get all ending tag names from the registry
        $endingTags = [];
        foreach ($this->registry->getTagNames() as $tagName) {
            if ($this->registry->isEndingTag($tagName)) {
                $endingTags[] = preg_quote($tagName, '/');
            }
        }

        if ($endingTags === []) {
            return $mjml;
        }

        $pattern = implode('|', $endingTags);

        // Match opening tag (NOT self-closing) with attributes, capture everything until closing tag
        // Use a non-greedy match to handle nested same-name tags
        // The negative lookbehind (?<!\/) ensures we don't match self-closing tags like <mj-text ... />
        return preg_replace_callback(
            '/(<(' . $pattern . ')(\s[^>]*?)?(?<!\/)>)(.*?)(<\/\2>)/s',
            static function (array $matches): string {
                $openTag = $matches[1];
                $tagName = $matches[2];
                $content = $matches[4];
                $closeTag = $matches[5];

                if (trim($content) === '') {
                    return $openTag . $closeTag;
                }

                // Store content as base64 in a special attribute
                $encoded = base64_encode($content);

                // Insert the special attribute into the opening tag
                if (str_ends_with($openTag, '/>')) {
                    // Self-closing, shouldn't happen for ending tags but handle it
                    return str_replace('/>', " __mjml_content__=\"{$encoded}\" />", $openTag);
                }

                $pos = strpos($openTag, '>');
                if ($pos !== false) {
                    $openTag = substr($openTag, 0, $pos) . " __mjml_content__=\"{$encoded}\">";
                }

                return $openTag . $closeTag;
            },
            $mjml,
        ) ?? $mjml;
    }
}
