<?php

declare(strict_types=1);

namespace Mjml\Hook;

use Mjml\Context\GlobalContext;
use Mjml\Parser\Node;

/**
 * Extension hooks for the MJML rendering pipeline.
 *
 * Implement this interface and register with Mjml to intercept
 * key points in the render lifecycle.
 */
interface PipelineHooks
{
    /**
     * Called before the MJML string is parsed into a Node tree.
     *
     * Return the (possibly modified) MJML string.
     */
    public function beforeParse(string $mjml): string;

    /**
     * Called after parsing, before validation and rendering.
     *
     * Return the (possibly modified) Node tree.
     */
    public function afterParse(Node $root): Node;

    /**
     * Called after head components have been processed but before
     * body rendering. Useful for injecting additional global context
     * data from custom head components.
     */
    public function afterHeadProcessed(GlobalContext $globalContext): void;

    /**
     * Called after body rendering but before post-processing
     * (CSS inlining, Outlook conditional merging, minification).
     *
     * Return the (possibly modified) HTML string.
     */
    public function afterBodyRendered(string $html): string;

    /**
     * Called after all post-processing is complete.
     *
     * Return the final HTML string.
     */
    public function afterPostProcess(string $html): string;
}
