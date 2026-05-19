<?php

declare(strict_types=1);

namespace Mjml\Cache;

use Mjml\Parser\Node;

/**
 * Optional cache interface for parsed MJML Node trees.
 *
 * Implementations can wrap PSR-6, PSR-16, or any other cache backend.
 * The bundled ArrayCache is sufficient for in-process deduplication.
 */
interface NodeCacheInterface
{
    /**
     * Retrieve a cached Node tree by key.
     */
    public function get(string $key): ?Node;

    /**
     * Store a Node tree under the given key.
     */
    public function set(string $key, Node $node): void;
}
