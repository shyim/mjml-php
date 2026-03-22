<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;

final class MjAccordionElement extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-accordion-element';
    }

    public static function allowedAttributes(): array
    {
        return [
            'background-color' => 'color',
            'border' => 'string',
            'font-family' => 'string',
            'accordion-font-family' => 'string',
            'icon-align' => 'enum(top,middle,bottom)',
            'icon-width' => 'unit(px,%)',
            'icon-height' => 'unit(px,%)',
            'icon-wrapped-url' => 'string',
            'icon-wrapped-alt' => 'string',
            'icon-unwrapped-url' => 'string',
            'icon-unwrapped-alt' => 'string',
            'icon-position' => 'enum(left,right)',
            'css-class' => 'string',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [];
    }

    protected function getStyles(): array
    {
        return [
            'td' => [
                'padding' => '0px',
                'background-color' => $this->getAttribute('background-color'),
            ],
            'label' => [
                'font-size' => '13px',
                'font-family' => $this->getAttribute('font-family'),
            ],
            'input' => [
                'display' => 'none',
            ],
        ];
    }

    private function handleMissingChildren(): string
    {
        $children = $this->node->children;
        $childrenAttr = [];
        foreach ([
            'border', 'icon-align', 'icon-width', 'icon-height',
            'icon-position', 'icon-wrapped-url', 'icon-wrapped-alt',
            'icon-unwrapped-url', 'icon-unwrapped-alt',
        ] as $attr) {
            $val = $this->getAttribute($attr);
            if ($val !== null) {
                $childrenAttr[$attr] = $val;
            }
        }

        // Pass font-family context for inheritance resolution
        $elementFontFamily = $this->getAttribute('font-family');
        if ($elementFontFamily !== null) {
            $childrenAttr['element-font-family'] = $elementFontFamily;
        }
        $accordionFontFamily = $this->getAttribute('accordion-font-family');
        if ($accordionFontFamily !== null) {
            $childrenAttr['accordion-font-family'] = $accordionFontFamily;
        }

        $result = '';

        // Check if title child exists
        $hasTitle = false;
        $hasText = false;
        foreach ($children as $child) {
            if ($child->tagName === 'mj-accordion-title') {
                $hasTitle = true;
            }
            if ($child->tagName === 'mj-accordion-text') {
                $hasText = true;
            }
        }

        if (!$hasTitle) {
            $result .= $this->createChildComponent(MjAccordionTitle::class, $childrenAttr)->render();
        }

        $result .= $this->renderChildren(attributes: $childrenAttr);

        if (!$hasText) {
            $result .= $this->createChildComponent(MjAccordionText::class, $childrenAttr)->render();
        }

        return $result;
    }

    /**
     * @param class-string<BodyComponent> $componentClass
     * @param array<string, string> $attributes
     */
    private function createChildComponent(string $componentClass, array $attributes): BodyComponent
    {
        $node = new \Shyim\Mjml\Parser\Node(
            tagName: $componentClass::getComponentName(),
            attributes: $attributes,
        );

        return new $componentClass(
            $node,
            $this->globalContext,
            $this->renderContext,
            $this->registry,
        );
    }

    public function render(): string
    {
        return '<tr'
            . $this->htmlAttributes([
                'class' => $this->getAttribute('css-class'),
            ])
            . '><td' . $this->htmlAttributes(['style' => 'td']) . '>'
            . '<label'
            . $this->htmlAttributes([
                'class' => 'mj-accordion-element',
                'style' => 'label',
            ])
            . '>'
            . '<!--[if !mso | IE]><!-->'
            . '<input'
            . $this->htmlAttributes([
                'class' => 'mj-accordion-checkbox',
                'type' => 'checkbox',
                'style' => 'input',
            ])
            . ' />'
            . '<!--<![endif]-->'
            . '<div>'
            . $this->handleMissingChildren()
            . '</div>'
            . '</label>'
            . '</td></tr>';
    }
}
