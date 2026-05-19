<?php

declare(strict_types=1);

namespace Shyim\Mjml\Renderer;

use Shyim\Mjml\Component\BodyComponent;
use Shyim\Mjml\Component\ComponentRegistry;
use Shyim\Mjml\Component\HeadComponent;
use Shyim\Mjml\Context\GlobalContext;
use Shyim\Mjml\Context\RenderContext;
use Shyim\Mjml\MjmlOptions;
use Shyim\Mjml\MjmlResult;
use Shyim\Mjml\Parser\MjmlParser;
use Shyim\Mjml\Parser\Node;
use Shyim\Mjml\Renderer\PostProcessor\CssInliner;
use Shyim\Mjml\Renderer\PostProcessor\HtmlAttributeApplier;
use Shyim\Mjml\Renderer\PostProcessor\OutlookConditionalMerger;
use Shyim\Mjml\Validation\ValidationException;
use Shyim\Mjml\Validation\ValidationLevel;
use Shyim\Mjml\Validation\Validator;

final class RenderingPipeline
{
    public function __construct(
        private readonly ComponentRegistry $registry,
        private readonly MjmlOptions $options,
    ) {}

    public function execute(string $mjml): MjmlResult
    {
        // 1. Parse MJML string to Node tree
        $parser = new MjmlParser($this->registry, $this->options);
        $root = $parser->parse($mjml, $this->options->filePath);

        // 2. Validate (if not Skip)
        if ($this->options->validationLevel !== ValidationLevel::Skip) {
            $validator = new Validator($this->registry);
            $errors = $validator->validate($root);

            if ($errors !== []) {
                throw new ValidationException($errors);
            }
        }

        // 3. Initialize global context
        $globalContext = new GlobalContext();
        $globalContext->breakpoint = $this->options->language !== 'und'
            ? $globalContext->breakpoint
            : $globalContext->breakpoint;
        $globalContext->language = $this->options->language;
        $globalContext->dir = $this->options->dir;
        $globalContext->fonts = $this->options->fonts;

        // 4. Find mj-head and mj-body nodes
        $headNode = $root->findFirstByTag('mj-head');
        $bodyNode = $root->findFirstByTag('mj-body');

        // 5. Process mj-head (populates globalContext)
        if ($headNode !== null) {
            $this->processHead($headNode, $globalContext);
        }

        // 6. Apply attribute defaults to body tree
        if ($bodyNode !== null) {
            $this->applyAttributes($bodyNode, $globalContext);
        }

        // 7. Render mj-body
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

        $globalContext->beforeDoctype = $rawBeforeDoctype;

        // Get background color from body
        if ($bodyNode !== null) {
            $globalContext->backgroundColor = $bodyNode->attributes['background-color'] ?? '';
        }

        // 8. Apply HTML attributes via post-processing
        if ($globalContext->htmlAttributes !== []) {
            $bodyContent = HtmlAttributeApplier::apply($bodyContent, $globalContext->htmlAttributes);
        }

        // 9. Wrap in HTML skeleton
        $skeleton = new Skeleton();
        $html = $skeleton->build($bodyContent, $globalContext, $this->options);

        // 10. Inline CSS (only explicit inline styles, not <style> tag contents)
        if ($globalContext->inlineStyles !== []) {
            $html = CssInliner::inline($html, $globalContext->inlineStyles);
        }

        // 11. Merge Outlook conditionals
        $html = OutlookConditionalMerger::merge($html);

        // 12. Clean up extra whitespace in output
        $html = $this->cleanOutput($html);

        if ($this->options->minify) {
            $html = $this->minifyOutput($html);
        } elseif ($this->options->beautify) {
            $html = $this->beautifyOutput($html);
        }

        return new MjmlResult(html: $html, json: $root);
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

    /**
     * Recursively apply default attributes from globalContext to the node tree.
     */
    private function applyAttributes(Node $node, GlobalContext $globalContext): void
    {
        foreach ($node->children as $child) {
            $this->applyAttributes($child, $globalContext);
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
        // Remove empty lines that accumulate from template concatenation
        $html = preg_replace('/^\s*\n/m', '', $html) ?? $html;

        return $html;
    }

    private function minifyOutput(string $html): string
    {
        $html = $this->stripHtmlminIgnoreMarkers($html);

        // Dependency-free conservative minification: remove whitespace between tags
        // and trim leading/trailing whitespace, while preserving text and style content.
        $html = preg_replace('/>\s+</', '><', $html) ?? $html;

        return trim($html);
    }

    private function beautifyOutput(string $html): string
    {
        $html = $this->stripHtmlminIgnoreMarkers($html);

        // Dependency-free lightweight beautification: make tag boundaries readable
        // without attempting to reindent or parse non-standard conditional markup.
        $html = preg_replace('/>(?=<)/', ">\n", $html) ?? $html;

        return trim($html) . "\n";
    }

    private function stripHtmlminIgnoreMarkers(string $html): string
    {
        return preg_replace('/\s*<!--\s*htmlmin:ignore\s*-->\s*/i', '', $html) ?? $html;
    }
}
