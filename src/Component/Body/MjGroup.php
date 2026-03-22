<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;
use Shyim\Mjml\Context\RenderContext;
use Shyim\Mjml\Helper\WidthParser;

final class MjGroup extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-group';
    }

    public static function allowedAttributes(): array
    {
        return [
            'background-color' => 'color',
            'direction' => 'enum(ltr,rtl)',
            'vertical-align' => 'enum(top,bottom,middle)',
            'width' => 'unit(px,%)',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'direction' => 'ltr',
        ];
    }

    public function getChildContext(): RenderContext
    {
        $parentWidth = $this->renderContext->containerWidth;
        $nonRawSiblings = $this->renderContext->nonRawSiblings;

        $paddingSize = $this->getShorthandAttrValue('padding', 'left')
            + $this->getShorthandAttrValue('padding', 'right');

        $width = $this->getAttribute('width');
        if ($width === null) {
            $containerWidth = ((float) $parentWidth / max($nonRawSiblings, 1)) . 'px';
        } else {
            $containerWidth = $width;
        }

        $parsed = WidthParser::parse($containerWidth);

        if ($parsed['unit'] === '%') {
            $containerWidth = ((float) $parentWidth * $parsed['value'] / 100 - $paddingSize) . 'px';
        } else {
            $containerWidth = ($parsed['value'] - $paddingSize) . 'px';
        }

        // Group propagates the number of its own children as nonRawSiblings
        $childCount = \count($this->node->children);

        return new RenderContext(
            containerWidth: $containerWidth,
            nonRawSiblings: $childCount,
            sectionGap: $this->renderContext->sectionGap,
            columnGap: $this->renderContext->columnGap,
        );
    }

    protected function getStyles(): array
    {
        return [
            'div' => [
                'font-size' => '0',
                'line-height' => '0',
                'text-align' => 'left',
                'display' => 'inline-block',
                'width' => '100%',
                'direction' => $this->getAttribute('direction'),
                'vertical-align' => $this->getAttribute('vertical-align'),
                'background-color' => $this->getAttribute('background-color'),
            ],
            'tdOutlook' => [
                'vertical-align' => $this->getAttribute('vertical-align'),
                'width' => $this->getWidthAsPixel(),
            ],
        ];
    }

    /**
     * @return array{unit: string, parsedWidth: float}
     */
    private function getParsedWidth(): array
    {
        $nonRawSiblings = $this->renderContext->nonRawSiblings;
        $width = $this->getAttribute('width');

        if ($width === null) {
            $width = (100 / max($nonRawSiblings, 1)) . '%';
        }

        $parsed = WidthParser::parse($width);

        return [
            'unit' => $parsed['unit'],
            'parsedWidth' => $parsed['value'],
        ];
    }

    private function getParsedWidthString(): string
    {
        $parsed = $this->getParsedWidth();

        return $parsed['parsedWidth'] . $parsed['unit'];
    }

    public function getWidthAsPixel(): string
    {
        $containerWidth = $this->renderContext->containerWidth;
        $parsed = WidthParser::parse($this->getParsedWidthString());

        if ($parsed['unit'] === '%') {
            return ((float) $containerWidth * $parsed['value'] / 100) . 'px';
        }

        return $parsed['value'] . 'px';
    }

    private function getColumnClass(): string
    {
        $parsed = $this->getParsedWidth();

        $className = match ($parsed['unit']) {
            '%' => 'mj-column-per-' . (int) $parsed['parsedWidth'],
            default => 'mj-column-px-' . (int) $parsed['parsedWidth'],
        };

        // Add className to media queries
        if ($parsed['unit'] === '%') {
            $this->globalContext->addMediaQuery(
                $className,
                "{ width: {$parsed['parsedWidth']}% !important; max-width: {$parsed['parsedWidth']}%; }",
            );
        } else {
            $this->globalContext->addMediaQuery(
                $className,
                "{ width: {$parsed['parsedWidth']}px !important; max-width: {$parsed['parsedWidth']}px; }",
            );
        }

        return $className;
    }

    public function render(): string
    {
        $nonRawSiblings = $this->renderContext->nonRawSiblings;
        $childContext = $this->getChildContext();
        $groupWidth = (float) $childContext->containerWidth;
        $containerWidth = $this->renderContext->containerWidth;

        $getElementWidth = function (?string $width) use ($containerWidth, $nonRawSiblings, $groupWidth): string {
            if ($width === null) {
                return ((int) ((float) $containerWidth) / max((int) $nonRawSiblings, 1)) . 'px';
            }

            $parsed = WidthParser::parse($width);

            if ($parsed['unit'] === '%') {
                return (100 * $parsed['value'] / max($groupWidth, 1)) . 'px';
            }

            return $parsed['value'] . $parsed['unit'];
        };

        $classesName = $this->getColumnClass() . ' mj-outlook-group-fix';

        $cssClass = $this->getAttribute('css-class');
        if ($cssClass !== null) {
            $classesName .= ' ' . $cssClass;
        }

        $bgColor = $this->getAttribute('background-color');
        $bgcolorAttr = ($bgColor !== null && $bgColor !== 'none') ? $bgColor : null;

        $output = '<div' . $this->htmlAttributes(['class' => $classesName, 'style' => 'div']) . '>';
        $output .= '<!--[if mso | IE]>'
            . '<table' . $this->htmlAttributes([
                'bgcolor' => $bgcolorAttr,
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'role' => 'presentation',
            ]) . '>'
            . '<tr>'
            . '<![endif]-->';

        $output .= $this->renderChildren(
            attributes: ['mobileWidth' => 'mobileWidth'],
            renderer: function (BodyComponent $component) use ($getElementWidth): string {
                if ($component::isRawElement()) {
                    return $component->render();
                }

                $widthValue = null;
                if ($component instanceof MjColumn) {
                    $widthValue = $component->getWidthAsPixel();
                } else {
                    $widthValue = $component->getAttribute('width');
                }

                $tdStyle = [
                    'align' => $component->getAttribute('align'),
                    'vertical-align' => $component->getAttribute('vertical-align'),
                    'width' => $getElementWidth($widthValue),
                ];

                return '<!--[if mso | IE]><td' . $component->htmlAttributes(['style' => $tdStyle]) . '><![endif]-->'
                    . $component->render()
                    . '<!--[if mso | IE]></td><![endif]-->';
            },
        );

        $output .= '<!--[if mso | IE]></tr></table><![endif]-->';
        $output .= '</div>';

        return $output;
    }
}
