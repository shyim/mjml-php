<?php

declare(strict_types=1);

namespace Mjml\Renderer;

use Mjml\Cache\ArrayCache;
use Mjml\Cache\NodeCacheInterface;
use Mjml\Component\BodyComponent;
use Mjml\Component\ComponentRegistry;
use Mjml\Component\HeadComponent;
use Mjml\Context\GlobalContext;
use Mjml\Context\RenderContext;
use Mjml\MjmlOptions;
use Mjml\MjmlResult;
use Mjml\Parser\MjmlParser;
use Mjml\Parser\Node;
use Mjml\Renderer\PostProcessor\CssInliner;
use Mjml\Renderer\PostProcessor\HtmlAttributeApplier;
use Mjml\Renderer\PostProcessor\OutlookConditionalMerger;
use Mjml\Validation\ValidationException;
use Mjml\Validation\ValidationLevel;
use Mjml\Validation\Validator;
use Mjml\Hook\PipelineHooks;

final class RenderingPipeline
{
    public function __construct(
        private readonly ComponentRegistry $registry,
        private readonly MjmlOptions $options,
        private readonly ?NodeCacheInterface $cache = null,
        /** @var list<PipelineHooks> */
        private readonly array $hooks = [],
    ) {}

    /**
     * @throws \Mjml\Parser\ParseException
     * @throws \Mjml\Validation\ValidationException
     */
    public function execute(string $mjml): MjmlResult
    {
        // 0. Before-parse hooks
        foreach ($this->hooks as $hook) {
            $mjml = $hook->beforeParse($mjml);
        }

        // 1. Parse MJML string to Node tree (with optional caching)
        $cacheKey = $this->cache !== null ? ArrayCache::hashKey($mjml) : null;
        $root = null;

        if ($cacheKey !== null) {
            $root = $this->cache->get($cacheKey);
        }

        if ($root === null) {
            $parser = new MjmlParser($this->registry, $this->options);
            $root = $parser->parse($mjml, $this->options->filePath);

            if ($cacheKey !== null) {
                $this->cache->set($cacheKey, $root);
            }
        }

        // After-parse hooks
        foreach ($this->hooks as $hook) {
            $root = $hook->afterParse($root);
        }

        // 2. Validate (if not Skip)
        $validationErrors = [];
        if ($this->options->validationLevel !== ValidationLevel::Skip) {
            $validator = new Validator($this->registry);
            $validationErrors = $validator->validate($root);

            if ($validationErrors !== [] && $this->options->validationLevel === ValidationLevel::Strict) {
                throw new ValidationException($validationErrors);
            }
        }

        // 3. Initialize global context
        $globalContext = new GlobalContext();
        $globalContext->setLanguage($this->options->language);
        $globalContext->setDir($this->options->dir);
        $globalContext->setFonts($this->options->fonts);

        // 4. Find mj-head and mj-body nodes
        $headNode = $root->findFirstByTag('mj-head');
        $bodyNode = $root->findFirstByTag('mj-body');

        // 5. Process mj-head (populates globalContext)
        if ($headNode !== null) {
            $this->processHead($headNode, $globalContext);
        }

        // After-head-processed hooks
        foreach ($this->hooks as $hook) {
            $hook->afterHeadProcessed($globalContext);
        }

        // 6. Render mj-body
        $bodyContent = '';
        $rawBeforeDoctype = '';

        foreach ($root->children as $child) {
            if ($child->tagName === 'mj-raw' && ($child->attributes['position'] ?? '') === 'file-start') {
                $rawBeforeDoctype .= trim($child->content);
            }
        }

        if ($bodyNode !== null) {
            // Handle raw elements at file-start position
            foreach ($bodyNode->children as $child) {
                if ($child->tagName === 'mj-raw' && ($child->attributes['position'] ?? '') === 'file-start') {
                    $rawBeforeDoctype .= trim($child->content);
                }
            }

            $bodyContent = $this->renderBody($bodyNode, $globalContext);
        }

        $globalContext->setBeforeDoctype($rawBeforeDoctype);

        // Get background color from body
        if ($bodyNode !== null) {
            $globalContext->setBackgroundColor($bodyNode->attributes['background-color'] ?? '');
        }

        // 8. Apply HTML attributes via post-processing
        $htmlAttributes = $globalContext->getHtmlAttributes();
        if ($htmlAttributes !== []) {
            $bodyContent = HtmlAttributeApplier::apply($bodyContent, $htmlAttributes);
        }

        // 9. Wrap in HTML skeleton
        $skeleton = new Skeleton();
        $html = $skeleton->build($bodyContent, $globalContext, $this->options);

        // After-body-rendered hooks
        foreach ($this->hooks as $hook) {
            $html = $hook->afterBodyRendered($html);
        }

        // 10. Inline CSS (only explicit inline styles, not <style> tag contents)
        $inlineStyles = $globalContext->getInlineStyles();
        if ($inlineStyles !== []) {
            $html = CssInliner::inline($html, $inlineStyles);
        }

        // 11. Merge Outlook conditionals (skip when the output contains none)
        if (str_contains($html, '<!--[if')) {
            $html = OutlookConditionalMerger::merge($html);
        }

        // 12. Clean up extra whitespace in output
        $html = $this->cleanOutput($html);

        if ($this->options->minify) {
            $html = $this->minifyOutput($html);
        } elseif ($this->options->beautify) {
            $html = $this->beautifyOutput($html);
        }

        // After-post-process hooks
        foreach ($this->hooks as $hook) {
            $html = $hook->afterPostProcess($html);
        }

        return new MjmlResult(html: $html, ast: $root, errors: $validationErrors);
    }

    private function processHead(Node $headNode, GlobalContext $globalContext): void
    {
        $renderContext = new RenderContext();

        foreach ($headNode->children as $child) {
            $componentClass = $this->registry->get($child->tagName);

            if ($componentClass === null) {
                continue;
            }

            $component = new $componentClass(
                $child,
                $globalContext,
                $renderContext,
                $this->registry,
            );

            if ($component instanceof HeadComponent) {
                $component->handler();
            }
        }
    }

    private function renderBody(Node $bodyNode, GlobalContext $globalContext): string
    {
        $componentClass = $this->registry->get('mj-body');

        if ($componentClass === null) {
            return '';
        }

        $renderContext = new RenderContext(
            containerWidth: $bodyNode->attributes['width'] ?? '600px',
        );

        /** @var BodyComponent $component */
        $component = new $componentClass(
            $bodyNode,
            $globalContext,
            $renderContext,
            $this->registry,
        );

        return $component->render();
    }

    private function cleanOutput(string $html): string
    {
        // Remove empty lines that accumulate from template concatenation, but
        // protect content inside <style>, <pre>, <textarea>, and Outlook
        // conditional comments from getting their intentional whitespace
        // collapsed.
        return self::transformOutsideProtectedRegions(
            $html,
            static fn(string $chunk): string => preg_replace('/^\s*\n/m', '', $chunk) ?? $chunk,
        );
    }

    private function minifyOutput(string $html): string
    {
        // Honor <!-- htmlmin:ignore --> as fence markers. Content between two
        // markers passes through unminified; markers themselves are stripped.
        return self::applyAcrossHtmlminFences(
            $html,
            fn(string $chunk): string => $this->minifyChunk($chunk),
        );
    }

    private function minifyChunk(string $html): string
    {
        // Remove whitespace between tags outside protected regions
        $html = self::transformOutsideProtectedRegions(
            $html,
            static fn(string $chunk): string => preg_replace('/>\s+</', '><', $chunk) ?? $chunk,
        );

        return trim($html);
    }

    private function beautifyOutput(string $html): string
    {
        return self::applyAcrossHtmlminFences(
            $html,
            static function (string $chunk): string {
                $result = self::transformOutsideProtectedRegions(
                    $chunk,
                    static fn(string $inner): string => preg_replace('/>(?=<)/', ">\n", $inner) ?? $inner,
                );

                return trim($result) . "\n";
            },
        );
    }

    /**
     * Apply $transform to regions of $html that are not inside <style>, <pre>,
     * <textarea>, or Outlook conditional comments. Protected regions are
     * passed through unchanged.
     *
     * @param callable(string): string $transform
     */
    private static function transformOutsideProtectedRegions(string $html, callable $transform): string
    {
        $pattern = '/(<style\b[^>]*>[\s\S]*?<\/style>|<pre\b[^>]*>[\s\S]*?<\/pre>|<textarea\b[^>]*>[\s\S]*?<\/textarea>|<!--\[if[\s\S]*?<!\[endif\]-->)/i';

        $parts = preg_split($pattern, $html, -1, \PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $transform($html);
        }

        $out = '';
        foreach ($parts as $index => $segment) {
            // Even indices are outside protected regions; odd indices are the
            // protected regions themselves and must be preserved verbatim.
            $out .= $index % 2 === 0 ? $transform($segment) : $segment;
        }

        return $out;
    }

    /**
     * Honor htmlmin:ignore markers as do-not-minify fences. Apply $transform
     * to the regions outside fences; leave fenced regions untouched. The
     * fence markers themselves are stripped from the output.
     *
     * @param callable(string): string $transform
     */
    private static function applyAcrossHtmlminFences(string $html, callable $transform): string
    {
        $marker = '/\s*<!--\s*htmlmin:ignore\s*-->\s*/i';
        $segments = preg_split($marker, $html);

        if ($segments === false || \count($segments) === 1) {
            return $transform(self::stripHtmlminIgnoreMarkers($html));
        }

        $out = '';
        foreach ($segments as $index => $segment) {
            // Alternating segments: even = active (transform), odd = fenced (preserve)
            $out .= $index % 2 === 0 ? $transform($segment) : $segment;
        }

        return $out;
    }

    private static function stripHtmlminIgnoreMarkers(string $html): string
    {
        return preg_replace('/\s*<!--\s*htmlmin:ignore\s*-->\s*/i', '', $html) ?? $html;
    }
}
