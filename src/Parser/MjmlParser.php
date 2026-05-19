<?php

declare(strict_types=1);

namespace Mjml\Parser;

use Mjml\Component\ComponentRegistry;
use Mjml\MjmlOptions;

final class MjmlParser
{
    /** @var list<string> Stack for circular include detection */
    private array $includeStack = [];

    /** @var list<Node> Accumulated CSS includes to be added to mj-head */
    private array $cssIncludes = [];

    private LibXmlErrorCollector $libxmlErrors;

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
        $this->libxmlErrors = new LibXmlErrorCollector();

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
        $this->libxmlErrors = new LibXmlErrorCollector();

        // Empty / whitespace-only input: return an empty <mjml/> tree rather
        // than triggering DOMDocument::loadXML's ValueError on empty source.
        if (trim($mjml) === '') {
            return new Node(tagName: 'mjml', file: $filePath);
        }

        // Pre-process: extract ending tag content before XML parsing
        $mjml = $this->extractEndingTagContent($mjml);

        // Suppress XML errors and handle them ourselves.
        //
        // Safety notes for libxml usage in this method and parseIncludeContent():
        // - LIBXML_NONET disables network access for external DTDs / entities.
        // - PHP 8 (with libxml >= 2.9) does not resolve external entities by
        //   default, so XXE is not exploitable through this parser. We do not
        //   call libxml_disable_entity_loader() (deprecated since PHP 8).
        $collector = $this->libxmlErrors;
        $collector->start();

        $doc = new \DOMDocument();
        $doc->loadXML($mjml, \LIBXML_NONET | \LIBXML_NOWARNING);

        $collector->collect(restorePrevious: true);

        $root = $doc->documentElement;

        if ($root === null) {
            return new Node(tagName: 'mjml', file: $filePath);
        }

        $node = $this->domToNode($root, $filePath);

        // Merge accumulated CSS includes into mj-head
        if ($this->cssIncludes !== []) {
            $this->mergeCssIncludes($node);
        }

        return $node;
    }

    /**
     * Convert a DOMElement to our Node tree.
     *
     * Note: mutates $this->cssIncludes when nested mj-include tags
     * contribute CSS/MJML head children.
     *
     * @phpstan-impure
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
            throw new ParseException("Circular inclusion detected on file : {$absolutePath}");
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

        $collector = $this->libxmlErrors;
        $collector->start();

        $doc = new \DOMDocument();
        $doc->loadXML($mjml, \LIBXML_NONET | \LIBXML_NOWARNING);

        $collector->collect();

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
     * This is needed because ending tag content (like mj-text, mj-button) can
     * contain arbitrary HTML that would otherwise break XML parsing.
     *
     * Implementation: a single forward pass over the input that tracks the
     * tag-name stack so nested same-name ending tags ({@code <mj-text>…
     * <mj-text>…</mj-text>…</mj-text>}) are matched correctly. This avoids
     * the pitfalls of a greedy/lazy regex on (.*?) which mis-pairs nested
     * tags and consumes content past the first matching close tag.
     */
    private function extractEndingTagContent(string $mjml): string
    {
        $endingTags = [];
        foreach ($this->registry->getTagNames() as $tagName) {
            if ($this->registry->isEndingTag($tagName)) {
                $endingTags[$tagName] = true;
            }
        }

        if ($endingTags === []) {
            return $mjml;
        }

        $length = strlen($mjml);
        $out = '';
        $i = 0;

        while ($i < $length) {
            $lt = strpos($mjml, '<', $i);
            if ($lt === false) {
                $out .= substr($mjml, $i);
                break;
            }

            // Emit text up to the '<'
            $out .= substr($mjml, $i, $lt - $i);
            $i = $lt;

            // Skip XML comments verbatim
            if (substr_compare($mjml, '<!--', $i, 4) === 0) {
                $end = strpos($mjml, '-->', $i + 4);
                if ($end === false) {
                    $out .= substr($mjml, $i);
                    break;
                }
                $out .= substr($mjml, $i, $end - $i + 3);
                $i = $end + 3;
                continue;
            }

            // CDATA sections and processing instructions / doctypes pass through
            if (substr_compare($mjml, '<![CDATA[', $i, 9) === 0) {
                $end = strpos($mjml, ']]>', $i + 9);
                if ($end === false) {
                    $out .= substr($mjml, $i);
                    break;
                }
                $out .= substr($mjml, $i, $end - $i + 3);
                $i = $end + 3;
                continue;
            }

            // Try to parse an opening tag for an ending-tag component
            $match = self::matchOpeningTag($mjml, $i, $endingTags);
            if ($match === null) {
                // Not a recognized ending-tag open — emit the '<' and continue
                $out .= '<';
                $i++;
                continue;
            }

            [$tagName, $openTag, $isSelfClosing, $afterOpen] = $match;

            if ($isSelfClosing) {
                $out .= $openTag;
                $i = $afterOpen;
                continue;
            }

            // Scan forward, depth-aware, until the matching closing tag
            $contentStart = $afterOpen;
            $contentEnd = self::findMatchingClose($mjml, $contentStart, $tagName);

            if ($contentEnd === null) {
                // Unbalanced — emit the open and let the XML parser fail loudly
                $out .= $openTag;
                $i = $afterOpen;
                continue;
            }

            $content = substr($mjml, $contentStart, $contentEnd - $contentStart);
            $closeLen = strlen('</' . $tagName . '>');

            if (trim($content) === '') {
                $out .= $openTag . '</' . $tagName . '>';
            } else {
                $encoded = base64_encode($content);
                $openTagWithAttr = substr($openTag, 0, -1) . " __mjml_content__=\"{$encoded}\">";
                $out .= $openTagWithAttr . '</' . $tagName . '>';
            }

            $i = $contentEnd + $closeLen;
        }

        return $out;
    }

    /**
     * Try to parse an opening tag at $pos. Returns null if it's not an
     * opening tag for a registered ending-tag component.
     *
     * @param array<string, true> $endingTags
     * @return array{0: string, 1: string, 2: bool, 3: int}|null
     *         [tagName, fullOpenTag (incl. '>'), isSelfClosing, posAfterOpenTag]
     */
    private static function matchOpeningTag(string $mjml, int $pos, array $endingTags): ?array
    {
        if (!isset($mjml[$pos]) || $mjml[$pos] !== '<') {
            return null;
        }

        // Closing tag start (handled by caller)
        if (isset($mjml[$pos + 1]) && $mjml[$pos + 1] === '/') {
            return null;
        }

        // Extract the tag name
        $nameStart = $pos + 1;
        $nameEnd = $nameStart;
        $len = strlen($mjml);
        while ($nameEnd < $len) {
            $c = $mjml[$nameEnd];
            if ($c === '>' || $c === '/' || $c === ' ' || $c === "\t" || $c === "\n" || $c === "\r") {
                break;
            }
            $nameEnd++;
        }

        if ($nameEnd === $nameStart) {
            return null;
        }

        $tagName = substr($mjml, $nameStart, $nameEnd - $nameStart);
        if (!isset($endingTags[$tagName])) {
            return null;
        }

        // Walk through attributes to find the end of the opening tag.
        // Attribute values may contain '>' so we must respect quoted strings.
        $i = $nameEnd;
        $inQuote = null;
        while ($i < $len) {
            $c = $mjml[$i];

            if ($inQuote !== null) {
                if ($c === $inQuote) {
                    $inQuote = null;
                }
                $i++;
                continue;
            }

            if ($c === '"' || $c === '\'') {
                $inQuote = $c;
                $i++;
                continue;
            }

            if ($c === '>') {
                $isSelfClosing = $i > 0 && $mjml[$i - 1] === '/';
                $openTag = substr($mjml, $pos, $i - $pos + 1);
                return [$tagName, $openTag, $isSelfClosing, $i + 1];
            }

            $i++;
        }

        return null;
    }

    /**
     * Find the position of the matching '</tagName>' starting at $pos,
     * tracking nested opens of the same tag. Comments, CDATA, and quoted
     * attribute values are skipped so the depth counter is not fooled.
     */
    private static function findMatchingClose(string $mjml, int $pos, string $tagName): ?int
    {
        $depth = 1;
        $len = strlen($mjml);
        $openMarker = '<' . $tagName;
        $closeMarker = '</' . $tagName;
        $openMarkerLen = strlen($openMarker);
        $closeMarkerLen = strlen($closeMarker);

        while ($pos < $len) {
            $next = strpos($mjml, '<', $pos);
            if ($next === false) {
                return null;
            }

            // Comments
            if (substr_compare($mjml, '<!--', $next, 4) === 0) {
                $end = strpos($mjml, '-->', $next + 4);
                if ($end === false) {
                    return null;
                }
                $pos = $end + 3;
                continue;
            }

            // CDATA
            if (substr_compare($mjml, '<![CDATA[', $next, 9) === 0) {
                $end = strpos($mjml, ']]>', $next + 9);
                if ($end === false) {
                    return null;
                }
                $pos = $end + 3;
                continue;
            }

            // Closing tag for this name?
            if (substr_compare($mjml, $closeMarker, $next, $closeMarkerLen) === 0) {
                $after = $next + $closeMarkerLen;
                if ($after < $len) {
                    $c = $mjml[$after];
                    // Permit </tag> and </tag > but not </tagFoo>
                    if ($c === '>' || $c === ' ' || $c === "\t" || $c === "\n" || $c === "\r") {
                        // Find the closing '>'
                        $gt = strpos($mjml, '>', $after);
                        if ($gt === false) {
                            return null;
                        }
                        $depth--;
                        if ($depth === 0) {
                            return $next; // position of the '<' of the closing tag
                        }
                        $pos = $gt + 1;
                        continue;
                    }
                }
            }

            // Nested opening tag of the same name?
            if (substr_compare($mjml, $openMarker, $next, $openMarkerLen) === 0) {
                $after = $next + $openMarkerLen;
                if ($after < $len) {
                    $c = $mjml[$after];
                    if ($c === '>' || $c === '/' || $c === ' ' || $c === "\t" || $c === "\n" || $c === "\r") {
                        // Skip over this opening tag, accounting for quoted attrs
                        $tagEnd = self::skipToTagEnd($mjml, $after);
                        if ($tagEnd === null) {
                            return null;
                        }
                        // If self-closing, depth is unchanged
                        if ($mjml[$tagEnd - 1] !== '/') {
                            $depth++;
                        }
                        $pos = $tagEnd + 1;
                        continue;
                    }
                }
            }

            $pos = $next + 1;
        }

        return null;
    }

    /**
     * Given a position inside an opening tag (after the tag name), return the
     * index of the closing '>' respecting quoted attribute values.
     */
    private static function skipToTagEnd(string $mjml, int $pos): ?int
    {
        $len = strlen($mjml);
        $inQuote = null;

        while ($pos < $len) {
            $c = $mjml[$pos];

            if ($inQuote !== null) {
                if ($c === $inQuote) {
                    $inQuote = null;
                }
                $pos++;
                continue;
            }

            if ($c === '"' || $c === '\'') {
                $inQuote = $c;
                $pos++;
                continue;
            }

            if ($c === '>') {
                return $pos;
            }

            $pos++;
        }

        return null;
    }

    public function getLibxmlErrors(): LibXmlErrorCollector
    {
        return $this->libxmlErrors;
    }
}
