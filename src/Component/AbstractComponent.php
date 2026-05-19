<?php

declare(strict_types=1);

namespace Mjml\Component;

use Mjml\Attribute\AttributeMerger;
use Mjml\Context\GlobalContext;
use Mjml\Context\RenderContext;
use Mjml\Parser\MjmlParser;
use Mjml\Parser\Node;

abstract class AbstractComponent implements ComponentInterface
{
    /** @var array<string, string|null> */
    protected array $attributes;

    public function __construct(
        protected readonly Node $node,
        protected readonly GlobalContext $globalContext,
        protected readonly RenderContext $renderContext,
        protected readonly ComponentRegistry $registry,
    ) {
        $this->attributes = $this->buildAttributes();
    }

    public static function isEndingTag(): bool
    {
        return false;
    }

    public static function isRawElement(): bool
    {
        return false;
    }

    public function getAttribute(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    public function getContent(): string
    {
        return trim($this->node->content);
    }

    /**
     * Render an MJML string fragment within this component's context.
     */
    protected function renderMjml(string $mjml): string
    {
        $parser = new MjmlParser($this->registry);
        $node = $parser->parse("<fragment>{$mjml}</fragment>");

        $output = '';
        foreach ($node->children as $child) {
            $output .= $this->processNode($child);
        }

        return $output;
    }

    /**
     * Process a single Node into rendered HTML.
     */
    protected function processNode(Node $node): string
    {
        $componentClass = $this->registry->get($node->tagName);

        if ($componentClass === null) {
            return '';
        }

        $component = new $componentClass(
            $node,
            $this->globalContext,
            $this->renderContext,
            $this->registry,
        );

        if ($component instanceof BodyComponent) {
            return $component->render();
        }

        if ($component instanceof HeadComponent) {
            $component->handler();
            return '';
        }

        return '';
    }

    /**
     * Build the final attributes by merging defaults, global defaults, class defaults, and explicit values.
     *
     * @return array<string, string|null>
     */
    private function buildAttributes(): array
    {
        $componentName = static::getComponentName();

        // Start with component defaults
        $attrs = static::defaultAttributes();

        // Apply global defaults from mj-attributes (for mj-all)
        $defaultAttributes = $this->globalContext->getDefaultAttributes();
        if (isset($defaultAttributes['mj-all'])) {
            $attrs = array_merge($attrs, $defaultAttributes['mj-all']);
        }

        // Apply component-specific defaults from mj-attributes
        if (isset($defaultAttributes[$componentName])) {
            $attrs = array_merge($attrs, $defaultAttributes[$componentName]);
        }

        // Apply mj-class attributes
        $mjClass = $this->node->attributes['mj-class'] ?? null;
        if ($mjClass !== null) {
            foreach (explode(' ', $mjClass) as $className) {
                $className = trim($className);
                if ($className === '') {
                    continue;
                }

                // Direct class attributes (from mj-class tag's own attributes)
                $classes = $this->globalContext->getClasses();
                if (isset($classes[$className])) {
                    $classValues = $classes[$className];

                    // Merge css-class values instead of overwriting
                    if (isset($attrs['css-class'], $classValues['css-class'])) {
                        $classValues['css-class'] = $attrs['css-class'] . ' ' . $classValues['css-class'];
                    }

                    $attrs = array_merge($attrs, $classValues);
                }

                // Class-level defaults for all components
                $classesDefault = $this->globalContext->getClassesDefault();
                if (isset($classesDefault[$className]['mj-all'])) {
                    $attrs = array_merge($attrs, $classesDefault[$className]['mj-all']);
                }

                // Class-level defaults for this component
                if (isset($classesDefault[$className][$componentName])) {
                    $attrs = array_merge($attrs, $classesDefault[$className][$componentName]);
                }
            }
        }

        // Apply explicit attributes (highest priority)
        $explicit = $this->node->attributes;
        unset($explicit['mj-class']); // Don't include mj-class as a regular attribute

        $attrs = array_merge($attrs, $explicit);

        return AttributeMerger::merge($attrs, static::allowedAttributes());
    }
}
