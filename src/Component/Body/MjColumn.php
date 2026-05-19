<?php

declare(strict_types=1);

namespace Mjml\Component\Body;

use Mjml\Component\BodyComponent;
use Mjml\Context\RenderContext;
use Mjml\Helper\WidthParser;

final class MjColumn extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-column';
    }

    public static function allowedAttributes(): array
    {
        return [
            'background-color' => 'color',
            'border' => 'string',
            'border-bottom' => 'string',
            'border-left' => 'string',
            'border-radius' => 'string',
            'border-right' => 'string',
            'border-top' => 'string',
            'direction' => 'enum(ltr,rtl)',
            'inner-background-color' => 'color',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'inner-border' => 'string',
            'inner-border-bottom' => 'string',
            'inner-border-left' => 'string',
            'inner-border-radius' => 'string',
            'inner-border-right' => 'string',
            'inner-border-top' => 'string',
            'padding' => 'unit(px,%){1,4}',
            'vertical-align' => 'enum(top,bottom,middle)',
            'width' => 'unit(px,%)',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'direction' => 'ltr',
            'vertical-align' => 'top',
        ];
    }

    public function getChildContext(): RenderContext
    {
        $parentWidth = $this->renderContext->containerWidth;
        $nonRawSiblings = $this->renderContext->nonRawSiblings;
        $boxWidths = $this->getBoxWidths();
        $paddings = $boxWidths['paddings'];
        $borders = $boxWidths['borders'];

        $innerBorders = $this->getShorthandBorderValue('left', 'inner-border')
            + $this->getShorthandBorderValue('right', 'inner-border');

        $allPaddings = $paddings + $borders + $innerBorders;

        $width = $this->getAttribute('width');
        if ($width === null) {
            $containerWidth = ((float) $parentWidth / max($nonRawSiblings, 1)) . 'px';
        } else {
            $containerWidth = $width;
        }

        $parsed = WidthParser::parse($containerWidth);

        if ($parsed['unit'] === '%') {
            $containerWidth = ((float) $parentWidth * $parsed['value'] / 100 - $allPaddings) . 'px';
        } else {
            $containerWidth = ($parsed['value'] - $allPaddings) . 'px';
        }

        return $this->renderContext->withContainerWidth($containerWidth);
    }

    protected function getStyles(): array
    {
        $hasBorderRadius = $this->hasBorderRadius();
        $hasInnerBorderRadius = $this->hasInnerBorderRadius();

        $tableStyle = [
            'background-color' => $this->getAttribute('background-color'),
            'border' => $this->getAttribute('border'),
            'border-bottom' => $this->getAttribute('border-bottom'),
            'border-left' => $this->getAttribute('border-left'),
            'border-radius' => $this->getAttribute('border-radius'),
            'border-right' => $this->getAttribute('border-right'),
            'border-top' => $this->getAttribute('border-top'),
            'vertical-align' => $this->getAttribute('vertical-align'),
        ];
        if ($hasBorderRadius) {
            $tableStyle['border-collapse'] = 'separate';
        }

        $tableInnerStyle = $this->hasGutter()
            ? [
                'background-color' => $this->getAttribute('inner-background-color'),
                'border' => $this->getAttribute('inner-border'),
                'border-bottom' => $this->getAttribute('inner-border-bottom'),
                'border-left' => $this->getAttribute('inner-border-left'),
                'border-radius' => $this->getAttribute('inner-border-radius'),
                'border-right' => $this->getAttribute('inner-border-right'),
                'border-top' => $this->getAttribute('inner-border-top'),
            ]
            : $tableStyle;

        if ($hasInnerBorderRadius) {
            $tableInnerStyle['border-collapse'] = 'separate';
        }

        return [
            'div' => [
                'font-size' => '0px',
                'text-align' => 'left',
                'direction' => $this->getAttribute('direction'),
                'display' => 'inline-block',
                'vertical-align' => $this->getAttribute('vertical-align'),
                'width' => $this->getMobileWidth(),
            ],
            'table' => $tableInnerStyle,
            'tdOutlook' => [
                'vertical-align' => $this->getAttribute('vertical-align'),
                'width' => $this->getWidthAsPixel(),
            ],
            'gutter' => array_merge($tableStyle, [
                'padding' => $this->getAttribute('padding'),
                'padding-top' => $this->getAttribute('padding-top'),
                'padding-right' => $this->getAttribute('padding-right'),
                'padding-bottom' => $this->getAttribute('padding-bottom'),
                'padding-left' => $this->getAttribute('padding-left'),
            ]),
        ];
    }

    private function getMobileWidth(): string
    {
        $containerWidth = $this->renderContext->containerWidth;
        $nonRawSiblings = $this->renderContext->nonRawSiblings;
        $width = $this->getAttribute('width');
        $mobileWidth = $this->getAttribute('mobileWidth');

        // MJML attribute convention: presence-as-value. The `mobileWidth`
        // attribute opts in by literally having the value "mobileWidth".
        if ($mobileWidth !== 'mobileWidth') {
            return '100%';
        }

        if ($width === null) {
            $val = 100 / max($nonRawSiblings, 1);
            return rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.') . '%';
        }

        $parsed = WidthParser::parse($width);

        if ($parsed['unit'] === '%') {
            return $width;
        }

        $val = $parsed['value'] / max((int) ((float) $containerWidth), 1) * 100;
        return rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.') . '%';
    }

    public function getWidthAsPixel(): string
    {
        $containerWidth = $this->renderContext->containerWidth;
        $parsed = WidthParser::parse($this->getParsedWidthString(), parseFloatToInt: false);

        if ($parsed['unit'] === '%') {
            return ((float) $containerWidth * $parsed['value'] / 100) . 'px';
        }

        return $parsed['value'] . 'px';
    }

    private function getParsedWidthString(): string
    {
        $nonRawSiblings = $this->renderContext->nonRawSiblings;
        $width = $this->getAttribute('width');

        if ($width === null) {
            $width = (100 / max($nonRawSiblings, 1)) . '%';
        }

        $parsed = WidthParser::parse($width);

        return $parsed['value'] . $parsed['unit'];
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

        $parsed = WidthParser::parse($width, parseFloatToInt: false);

        return [
            'unit' => $parsed['unit'],
            'parsedWidth' => $parsed['value'],
        ];
    }

    private function getColumnClass(): string
    {
        $parsed = $this->getParsedWidth();
        $formattedClassNb = str_replace('.', '-', (string) $parsed['parsedWidth']);

        $className = match ($parsed['unit']) {
            '%' => "mj-column-per-{$formattedClassNb}",
            default => "mj-column-px-{$formattedClassNb}",
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

    private function hasBorderRadius(): bool
    {
        $borderRadius = $this->getAttribute('border-radius');

        return $borderRadius !== null && $borderRadius !== '';
    }

    private function hasInnerBorderRadius(): bool
    {
        $innerBorderRadius = $this->getAttribute('inner-border-radius');

        return $innerBorderRadius !== null && $innerBorderRadius !== '';
    }

    private function hasGutter(): bool
    {
        foreach (['padding', 'padding-bottom', 'padding-left', 'padding-right', 'padding-top'] as $attr) {
            if ($this->getAttribute($attr) !== null) {
                return true;
            }
        }

        return false;
    }

    private function renderGutter(): string
    {
        $hasBorderRadius = $this->hasBorderRadius();

        $tableAttrs = [
            'border' => '0',
            'cellpadding' => '0',
            'cellspacing' => '0',
            'role' => 'presentation',
            'width' => '100%',
        ];

        if ($hasBorderRadius) {
            $tableAttrs['style'] = ['border-collapse' => 'separate'];
        }

        return '<table' . $this->htmlAttributes($tableAttrs) . '>'
            . '<tbody>'
            . '<tr>'
            . '<td' . $this->htmlAttributes(['style' => 'gutter']) . '>'
            . $this->renderColumn()
            . '</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>';
    }

    private function renderColumn(): string
    {
        return '<table'
            . $this->htmlAttributes([
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'role' => 'presentation',
                'style' => 'table',
                'width' => '100%',
            ])
            . '>'
            . '<tbody>'
            . $this->renderChildren(
                renderer: function (BodyComponent $component): string {
                    if ($component::isRawElement()) {
                        return $component->render();
                    }

                    $tdStyle = [
                        'background' => $component->getAttribute('container-background-color'),
                        'font-size' => '0px',
                        'padding' => $component->getAttribute('padding'),
                        'padding-top' => $component->getAttribute('padding-top'),
                        'padding-right' => $component->getAttribute('padding-right'),
                        'padding-bottom' => $component->getAttribute('padding-bottom'),
                        'padding-left' => $component->getAttribute('padding-left'),
                        'word-break' => 'break-word',
                    ];

                    $tdAttrs = [
                        'align' => $component->getAttribute('align'),
                        'style' => $tdStyle,
                    ];

                    $cssClass = $component->getAttribute('css-class');
                    if ($cssClass !== null) {
                        $tdAttrs['class'] = $cssClass;
                    }

                    return '<tr>'
                        . '<td' . $component->htmlAttributes($tdAttrs) . '>'
                        . $component->render()
                        . '</td>'
                        . '</tr>';
                },
            )
            . '</tbody>'
            . '</table>';
    }

    public function render(): string
    {
        $classesName = $this->getColumnClass() . ' mj-outlook-group-fix';

        $cssClass = $this->getAttribute('css-class');
        if ($cssClass !== null) {
            $classesName .= ' ' . $cssClass;
        }

        return '<div'
            . $this->htmlAttributes([
                'class' => $classesName,
                'style' => 'div',
            ])
            . '>'
            . ($this->hasGutter() ? $this->renderGutter() : $this->renderColumn())
            . '</div>';
    }
}
