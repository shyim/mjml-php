<?php

declare(strict_types=1);

namespace Mjml\Cache;

use Mjml\Parser\Node;

/**
 * In-memory LRU cache for parsed MJML Node trees.
 *
 * Useful for request-scoped deduplication (e.g., rendering the same
 * template multiple times in the same process).
 */
final class ArrayCache implements NodeCacheInterface
{
    /** @var array<string, Node> */
    private array $items = [];

    /** @var array<string, true> Access-order tracking for LRU eviction */
    private array $accessOrder = [];

    private int $hits = 0;
    private int $misses = 0;

    public function __construct(
        private readonly int $maxItems = 100,
    ) {}

    public function get(string $key): ?Node
    {
        if (isset($this->items[$key])) {
            $this->hits++;
            // Move to end (most recently used)
            unset($this->accessOrder[$key]);
            $this->accessOrder[$key] = true;

            return $this->items[$key];
        }

        $this->misses++;

        return null;
    }

    public function set(string $key, Node $node): void
    {
        // Evict oldest if at capacity
        if (\count($this->items) >= $this->maxItems && !isset($this->items[$key])) {
            $oldest = array_key_first($this->accessOrder);
            if ($oldest !== null) {
                unset($this->items[$oldest], $this->accessOrder[$oldest]);
            }
        }

        $this->items[$key] = $node;
        $this->accessOrder[$key] = true;
    }

    public function getHits(): int
    {
        return $this->hits;
    }
    public function getMisses(): int
    {
        return $this->misses;
    }

    /**
     * Generate a cache key from an MJML string.
     */
    public static function hashKey(string $mjml): string
    {
        return 'mjml_' . hash('xxh3', $mjml);
    }
}
