<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component;

use Shyim\Mjml\Context\RenderContext;
use Shyim\Mjml\Helper\CssParser;
use Shyim\Mjml\Helper\WidthParser;
use Shyim\Mjml\Parser\Node;

abstract class BodyComponent extends AbstractComponent
{
    /**
     * Render this component to HTML.
     */
    abstract public function render(): string;

    /**
     * Return CSS styles organized by element name.
     *
     * Example: ['div' => ['color' => 'red', 'font-size' => '12px'], 'td' => [...]]
     *
     * @return array<string, array<string, string|null>>
     */
    protected function getStyles(): array
    {
        return [];
    }

    /**
     * Get a computed child context (e.g., adjusted container width).
     */
    public function getChildContext(): RenderContext
    {
        return $this->renderContext;
    }

    /**
     * Build an HTML attributes string from an associative array.
     *
     * The special key 'style' is treated as a style group name or inline style array resolved via styles().
     *
     * @param array<string, string|array<string, string|null>|null> $attributes
     */
    protected function htmlAttributes(array $attributes): string
    {
        $output = '';

        foreach ($attributes as $name => $value) {
            if ($value === null) {
                continue;
            }

            if ($name === 'style') {
                $value = $this->styles($value);

                // Skip empty style attributes (matching JS behavior)
                if ($value === '') {
                    continue;
                }
            }

            $output .= " {$name}=\"{$value}\"";
        }

        return $output;
    }

    /**
     * Convert a style group name or array to an inline CSS string.
     *
     * @param string|array<string, string|null> $styles
     */
    protected function styles(string|array $styles): string
    {
        if (\is_string($styles)) {
            $stylesArray = $this->getStyles()[$styles] ?? [];
        } else {
            $stylesArray = $styles;
        }

        $output = '';
        foreach ($stylesArray as $name => $value) {
            if ($value !== null && $value !== '') {
                $output .= "{$name}:{$value};";
            }
        }

        return $output;
    }

    /**
     * Parse a CSS shorthand attribute value for a specific direction.
     */
    protected function getShorthandAttrValue(string $attribute, string $direction): int
    {
        $directionValue = $this->getAttribute("{$attribute}-{$direction}");

        if ($directionValue !== null) {
            return (int) $directionValue;
        }

        $value = $this->getAttribute($attribute);

        if ($value === null) {
            return 0;
        }

        return CssParser::parseShorthand($value, $direction);
    }

    /**
     * Parse a border shorthand value for a specific direction.
     */
    protected function getShorthandBorderValue(string $direction, string $attribute = 'border'): int
    {
        $borderDirection = $direction !== '' ? $this->getAttribute("{$attribute}-{$direction}") : null;
        $border = $this->getAttribute($attribute);

        return CssParser::parseBorderWidth($borderDirection ?? $border ?? '0');
    }

    /**
     * Calculate box dimensions accounting for padding and borders.
     *
     * @return array{totalWidth: int, borders: int, paddings: int, box: int}
     */
    protected function getBoxWidths(): array
    {
        $containerWidth = (int) $this->renderContext->containerWidth;

        $paddings = $this->getShorthandAttrValue('padding', 'right')
            + $this->getShorthandAttrValue('padding', 'left');

        $borders = $this->getShorthandBorderValue('right')
            + $this->getShorthandBorderValue('left');

        return [
            'totalWidth' => $containerWidth,
            'borders' => $borders,
            'paddings' => $paddings,
            'box' => $containerWidth - $paddings - $borders,
        ];
    }

    /**
     * Render child nodes as HTML.
     *
     * @param list<Node>|null $children
     * @param array<string, string> $attributes Extra attributes to merge into children
     * @param callable|null $renderer Custom renderer function (receives component, returns string)
     */
    protected function renderChildren(
        ?array $children = null,
        array $attributes = [],
        ?callable $renderer = null,
        bool $rawXML = false,
    ): string {
        $children ??= $this->node->children;
        $renderer ??= static fn(BodyComponent $component): string => $component->render();

        if ($rawXML) {
            return $this->renderChildrenAsXml($children, $attributes);
        }

        $sibling = \count($children);

        // Count non-raw siblings
        $nonRawSiblings = 0;
        foreach ($children as $child) {
            $componentClass = $this->registry->get($child->tagName);
            if ($componentClass !== null && !$componentClass::isRawElement()) {
                $nonRawSiblings++;
            }
        }

        $output = '';
        $childContext = $this->getChildContext();

        foreach ($children as $index => $child) {
            $componentClass = $this->registry->get($child->tagName);

            if ($componentClass === null) {
                continue;
            }

            // Merge extra attributes
            $childNode = $child;
            if ($attributes !== []) {
                $childNode = new Node(
                    tagName: $child->tagName,
                    attributes: array_merge($attributes, $child->attributes),
                    children: $child->children,
                    content: $child->content,
                    line: $child->line,
                    file: $child->file,
                    parent: $child->parent,
                );
            }

            $renderCtx = new RenderContext(
                containerWidth: $childContext->containerWidth,
                first: $index === 0,
                last: $index + 1 === $sibling,
                index: $index,
                sibling: $sibling,
                nonRawSiblings: $nonRawSiblings,
                sectionGap: $childContext->sectionGap,
                columnGap: $childContext->columnGap,
            );

            /** @var BodyComponent $component */
            $component = new $componentClass(
                $childNode,
                $this->globalContext,
                $renderCtx,
                $this->registry,
            );

            $output .= $renderer($component);
        }

        return $output;
    }

    /**
     * Render children as raw XML strings.
     *
     * @param list<Node> $children
     * @param array<string, string> $attributes
     */
    private function renderChildrenAsXml(array $children, array $attributes): string
    {
        $output = '';

        foreach ($children as $child) {
            $attrs = array_merge($attributes, $child->attributes);
            $attrStr = '';
            foreach ($attrs as $name => $value) {
                $attrStr .= " {$name}=\"{$value}\"";
            }

            if ($child->children === [] && $child->content === '') {
                $output .= "<{$child->tagName}{$attrStr} />\n";
            } else {
                $output .= "<{$child->tagName}{$attrStr}>{$child->content}</{$child->tagName}>\n";
            }
        }

        return $output;
    }

    /**
     * Get width parsed into value and unit.
     *
     * @return array{value: float, unit: string}
     */
    protected function getWidth(): array
    {
        $width = $this->getAttribute('width');

        if ($width === null) {
            return ['value' => 0.0, 'unit' => 'px'];
        }

        return WidthParser::parse($width);
    }

    /**
     * Suffix each CSS class with the given suffix.
     * "foo bar" with suffix "outlook" becomes "foo-outlook bar-outlook".
     */
    protected static function suffixCssClasses(?string $classes, string $suffix): string
    {
        if ($classes === null || $classes === '') {
            return '';
        }

        $parts = explode(' ', $classes);
        $suffixed = array_map(static fn(string $c): string => "{$c}-{$suffix}", $parts);

        return implode(' ', $suffixed);
    }
}
