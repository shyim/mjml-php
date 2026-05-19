<?php

declare(strict_types=1);

namespace Mjml\Parser;

/**
 * Internal AST node used by the parser and renderer.
 *
 * @internal Exposed through MjmlResult::$json for debugging only; the shape
 *           is not part of the public API and may change without notice.
 */
final class Node
{
    /**
     * @param array<string, string> $attributes
     * @param list<Node> $children
     */
    public function __construct(
        public readonly string $tagName,
        public array $attributes = [],
        public array $children = [],
        public string $content = '',
        public readonly int $line = 0,
        public readonly ?string $file = null,
        public ?Node $parent = null,
    ) {}

    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function getAttribute(string $name, ?string $default = null): ?string
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Find the first child node with the given tag name.
     */
    public function findFirstByTag(string $tagName): ?self
    {
        foreach ($this->children as $child) {
            if ($child->tagName === $tagName) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Find all children with the given tag name (non-recursive).
     *
     * @return list<Node>
     */
    public function findByTag(string $tagName): array
    {
        $results = [];

        foreach ($this->children as $child) {
            if ($child->tagName === $tagName) {
                $results[] = $child;
            }
        }

        return $results;
    }
}
