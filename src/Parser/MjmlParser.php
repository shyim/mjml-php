<?php

declare(strict_types=1);

namespace Shyim\Mjml\Parser;

use Shyim\Mjml\Component\ComponentRegistry;
use Shyim\Mjml\MjmlOptions;

final class MjmlParser
{
    /** @var list<string> Stack for circular include detection */
    private array $includeStack = [];

    /** @var list<Node> Accumulated CSS includes to be added to mj-head */
    private array $cssIncludes = [];

    private readonly bool $ignoreIncludes;

    private readonly bool $keepComments;

    /** @var list<string> */
    private readonly array $includePath;

    private readonly string $cwd;

    public function __construct(
        private readonly ComponentRegistry $registry,
        ?MjmlOptions $options = null,
    ) {
        $this->ignoreIncludes = ($options !== null) ? $options->ignoreIncludes : true;
        $this->keepComments = ($options !== null) ? $options->keepComments : true;
        $this->includePath = ($options !== null) ? ($options->includePath ?? []) : [];

        // Determine working directory for path resolution
        $filePath = $options?->filePath;
        if ($filePath !== null && $filePath !== '') {
            if (is_dir($filePath)) {
                $this->cwd = realpath($filePath) ?: $filePath;
            } elseif (is_file($filePath)) {
                $this->cwd = dirname(realpath($filePath) ?: $filePath);
            } else {
                $this->cwd = getcwd() ?: '.';
            }
        } else {
            $this->cwd = getcwd() ?: '.';
        }
    }

    /**
     * Parse an MJML string into a Node tree.
     */
    public function parse(string $mjml, ?string $filePath = null): Node
    {
        $this->includeStack = [];
        $this->cssIncludes = [];

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

        $node = $this->domToNode($root, $filePath);

        // Merge accumulated CSS includes into mj-head
        if (count($this->cssIncludes) > 0) {
            $this->mergeCssIncludes($node);
        }

        return $node;
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
                    $includedChildren = $this->handleInclude($child, $filePath);
                    if ($includedChildren !== null) {
                        foreach ($includedChildren as $includedChild) {
                            $children[] = $includedChild;
                        }
                    }
                    continue;
                }

                $children[] = $this->domToNode($child, $filePath);
            } elseif ($child instanceof \DOMText && trim($child->textContent) !== '') {
                // Text content outside of ending tags
                if ($content === '') {
                    $content = $child->textContent;
                }
            } elseif ($child instanceof \DOMComment && $this->keepComments) {
                $children[] = new Node(
                    tagName: 'mj-raw',
                    content: '<!--' . $child->textContent . '-->',
                    line: $child->getLineNo(),
                    file: $filePath,
                );
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
     *
     * Returns an array of child Nodes to be inserted in place of the include,
     * or null if the include should be ignored.
     *
     * @return list<Node>|null
     */
    private function handleInclude(\DOMElement $element, ?string $currentFile): ?array
    {
        // If includes are ignored, skip entirely (no denial comment either)
        if ($this->ignoreIncludes) {
            return null;
        }

        $path = $element->getAttribute('path');

        if ($path === '') {
            return null;
        }

        $type = $element->getAttribute('type');

        // CSS and HTML includes are handled differently
        if ($type === 'css' || $type === 'html') {
            return $this->handleCssHtmlInclude(
                $path,
                $type,
                $element->getAttribute('css-inline') === 'inline',
                $currentFile,
            );
        }

        // MJML include (default type)
        return $this->handleMjmlInclude($path, $currentFile);
    }

    /**
     * Handle CSS or HTML file includes.
     *
     * @return list<Node>
     */
    private function handleCssHtmlInclude(string $path, string $type, bool $cssInline, ?string $currentFile): array
    {
        // Security: decode URL-encoded paths and check for dangerous patterns
        $decoded = $this->fullyDecode($path);

        if (!$this->isPathSafe($decoded)) {
            return [new Node(tagName: 'mj-raw', content: '<!-- mj-include denied -->', file: $currentFile)];
        }

        $absolutePath = $this->resolvePath($decoded, $currentFile);

        if (!$this->isPathAllowed($absolutePath)) {
            return [new Node(tagName: 'mj-raw', content: '<!-- mj-include denied -->', file: $currentFile)];
        }

        $fileContent = $this->readFile($absolutePath);

        if ($fileContent === null) {
            return [new Node(tagName: 'mj-raw', content: "<!-- mj-include fails to read file : {$path} at {$absolutePath} -->", file: $currentFile)];
        }

        if ($type === 'html') {
            return [new Node(tagName: 'mj-raw', content: $fileContent, file: $absolutePath)];
        }

        // CSS includes are added to mj-head as mj-style
        $attributes = $cssInline ? ['inline' => 'inline'] : [];
        $this->cssIncludes[] = new Node(
            tagName: 'mj-style',
            attributes: $attributes,
            content: $fileContent,
            file: $absolutePath,
        );

        // CSS includes don't produce inline children
        return [];
    }

    /**
     * Handle MJML file includes.
     *
     * Reads the included file, parses it, and merges body children
     * into the current tree (matching JS behavior).
     *
     * @return list<Node>|null
     */
    private function handleMjmlInclude(string $path, ?string $currentFile): ?array
    {
        // Security: decode URL-encoded paths and check for dangerous patterns
        $decoded = $this->fullyDecode($path);

        if (!$this->isPathSafe($decoded)) {
            return [new Node(tagName: 'mj-raw', content: '<!-- mj-include denied -->', file: $currentFile)];
        }

        $absolutePath = $this->resolvePath($decoded, $currentFile);

        if (!$this->isPathAllowed($absolutePath)) {
            return [new Node(tagName: 'mj-raw', content: '<!-- mj-include denied -->', file: $currentFile)];
        }

        // Circular include detection
        if (\in_array($absolutePath, $this->includeStack, true)) {
            throw new \RuntimeException("Circular inclusion detected on file : {$absolutePath}");
        }

        $this->includeStack[] = $absolutePath;

        $fileContent = $this->readFile($absolutePath);

        if ($fileContent === null) {
            array_pop($this->includeStack);
            return [new Node(tagName: 'mj-raw', content: "<!-- mj-include fails to read file : {$path} at {$absolutePath} -->", file: $currentFile)];
        }

        // If content doesn't have <mjml> wrapper, add one
        if (stripos($fileContent, '<mjml>') === false) {
            $fileContent = "<mjml><mj-body>{$fileContent}</mj-body></mjml>";
        }

        // Parse the included MJML (recursive — handles nested includes)
        $includedRoot = $this->parseIncludeContent($fileContent, $absolutePath);

        array_pop($this->includeStack);

        if ($includedRoot->tagName !== 'mjml') {
            return null;
        }

        // Merge body children into current position
        $body = $includedRoot->findFirstByTag('mj-body');
        $resultChildren = [];

        if ($body !== null) {
            foreach ($body->children as $child) {
                $resultChildren[] = $child;
            }
        }

        // Merge head children into root mj-head (will be added later via cssIncludes)
        $head = $includedRoot->findFirstByTag('mj-head');
        if ($head !== null) {
            foreach ($head->children as $child) {
                $this->cssIncludes[] = $child;
            }
        }

        return $resultChildren !== [] ? $resultChildren : null;
    }

    /**
     * Parse included MJML content (handles recursive includes via domToNode).
     */
    private function parseIncludeContent(string $mjml, string $filePath): Node
    {
        $mjml = $this->extractEndingTagContent($mjml);

        $previousUseErrors = libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $doc->loadXML($mjml, \LIBXML_NONET | \LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);

        $root = $doc->documentElement;

        if ($root === null) {
            return new Node(tagName: 'mjml', file: $filePath);
        }

        return $this->domToNode($root, $filePath);
    }

    /**
     * Merge accumulated CSS includes into the mj-head node.
     *
     * If no mj-head exists, create one.
     */
    private function mergeCssIncludes(Node $root): void
    {
        if ($this->cssIncludes === []) {
            return;
        }

        $head = $root->findFirstByTag('mj-head');

        if ($head === null) {
            // Create mj-head and add it as a child of mjml
            $head = new Node(tagName: 'mj-head', file: $root->file);
            $head->parent = $root;
            $root->children[] = $head;
        }

        foreach ($this->cssIncludes as $cssNode) {
            $cssNode->parent = $head;
            $head->children[] = $cssNode;
        }
    }

    // ─── Path Security ────────────────────────────────────────────────

    /**
     * Fully decode URL-encoded strings (handles double/triple encodings).
     */
    private function fullyDecode(string $input): string
    {
        $result = $input;
        for ($i = 0; $i < 10; $i++) {
            $decoded = rawurldecode($result);
            if ($decoded === $result) {
                break;
            }
            $result = $decoded;
        }

        return $result;
    }

    /**
     * Check if a path is safe (no null bytes, no absolute paths, no UNC paths, no drive letters).
     */
    private function isPathSafe(string $path): bool
    {
        // Null bytes
        if (str_contains($path, "\0")) {
            return false;
        }

        // Absolute Unix paths
        if (str_starts_with($path, '/')) {
            return false;
        }

        // Windows drive letters
        if (preg_match('/^[a-zA-Z]:/', $path)) {
            return false;
        }

        // UNC paths
        if (str_starts_with($path, '\\\\') || str_starts_with($path, '//')) {
            return false;
        }

        return true;
    }

    /**
     * Resolve a relative path against the current working directory.
     */
    private function resolvePath(string $path, ?string $currentFile): string
    {
        $base = $this->cwd;

        // If we have a current file, resolve against its directory.
        // When filePath is a directory (JS-compatible usage), resolve against that directory directly.
        if ($currentFile !== null) {
            $resolved = realpath($currentFile);
            if ($resolved !== false) {
                $base = is_dir($resolved) ? $resolved : dirname($resolved);
            }
        }

        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Check if an absolute path is within the allowed roots (cwd + includePath).
     */
    private function isPathAllowed(string $absolutePath): bool
    {
        $realTarget = realpath($absolutePath);

        if ($realTarget === false) {
            // File doesn't exist yet — still check path doesn't escape roots
            $realTarget = $absolutePath;
        }

        $roots = [realpath($this->cwd) ?: $this->cwd];

        foreach ($this->includePath as $extraPath) {
            $realExtra = realpath($extraPath);
            if ($realExtra !== false) {
                $roots[] = $realExtra;
            }
        }

        foreach ($roots as $root) {
            if ($this->isSubPath($root, $realTarget)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if target is within the root directory (no path traversal).
     */
    private function isSubPath(string $root, string $target): bool
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR);
        $target = rtrim($target, DIRECTORY_SEPARATOR);

        // Target must start with root path
        if (!str_starts_with($target, $root . DIRECTORY_SEPARATOR) && $target !== $root) {
            return false;
        }

        // Ensure no path traversal after the root prefix
        if (str_starts_with($target, $root . DIRECTORY_SEPARATOR)) {
            $relative = substr($target, strlen($root) + 1);
            if (str_contains($relative, '..')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Read file contents with error handling.
     */
    private function readFile(string $path): ?string
    {
        $real = realpath($path);

        if ($real === false || !is_file($real) || !is_readable($real)) {
            return null;
        }

        $content = file_get_contents($real);

        return $content !== false ? $content : null;
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
