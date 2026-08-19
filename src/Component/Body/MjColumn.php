<?php

declare(strict_types=1);

namespace MjmlPHP\Component\Body;

use MjmlPHP\Component\BodyComponent;
use MjmlPHP\Context\RenderContext;
use MjmlPHP\Helper\WidthParser;

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
            'div' => array_merge([
                'font-size' => '0px',
                'text-align' => 'left',
                'direction' => $this->getAttribute('direction'),
                'display' => 'inline-block',
                'vertical-align' => $this->getAttribute('vertical-align'),
                'width' => $this->getMobileWidth(),
            ], $this->getMobileGutterStyles()),
            'table' => $tableInnerStyle,
            'tdOutlook' => array_merge([
                'vertical-align' => $this->getAttribute('vertical-align'),
                'width' => $this->getWidthAsPixel(),
            ], $this->getOutlookGutterStyles()),
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

        if ($this->renderContext->isInGroup && $this->hasColumnGutter()) {
            $desktop = $this->getDesktopWidth();
            if ($desktop['unit'] === '%') {
                return self::formatUnitValue($desktop['parsedWidth']) . '%';
            }

            $percent = $desktop['parsedWidth'] / max((int) ((float) $containerWidth), 1) * 100;

            return self::formatUnitValue($percent) . '%';
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
            return self::normalizePxValue((float) $containerWidth * $parsed['value'] / 100) . 'px';
        }

        return self::normalizePxValue($parsed['value']) . 'px';
    }

    /** Round to the nearest CSS pixel, matching MJML 5.4.0. */
    private static function normalizePxValue(float $value): int
    {
        return (int) round($value);
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
        $parsed = $this->hasColumnGutter()
            ? $this->getDesktopWidth()
            : $this->getParsedWidth();

        $parsedWidth = $parsed['unit'] === 'px'
            ? self::normalizePxValue($parsed['parsedWidth'])
            : $parsed['parsedWidth'];
        $widthToken = $parsed['unit'] === 'px'
            ? (string) $parsedWidth
            : self::formatUnitValue((float) $parsedWidth);
        $formattedClassNb = str_replace('.', '-', $widthToken);

        $className = match ($parsed['unit']) {
            '%' => "mj-column-per-{$formattedClassNb}",
            default => "mj-column-px-{$formattedClassNb}",
        };

        $this->addWidthMediaQuery($className, $widthToken, $parsed['unit']);

        if ($this->hasColumnGutter() && !$this->renderContext->isInGroup) {
            $this->globalContext->addMediaQuery(
                $this->getDesktopGutterClassName(),
                '{ padding: ' . $this->getDesktopPadding() . ' !important; }',
            );
        }

        return $className;
    }

    private function addWidthMediaQuery(string $className, string $parsedWidth, string $unit): void
    {
        $this->globalContext->addMediaQuery(
            $className,
            "{ width: {$parsedWidth}{$unit} !important; max-width: {$parsedWidth}{$unit}; }",
        );
    }

    /**
     * @return array{unit: string, parsedWidth: float}
     */
    private function getDesktopWidth(): array
    {
        $parsed = $this->getParsedWidth();
        $unit = $parsed['unit'];
        $parsedWidth = $parsed['parsedWidth'];

        if (!$this->hasColumnGutter()) {
            return [
                'parsedWidth' => $unit === 'px' ? (float) self::normalizePxValue($parsedWidth) : $parsedWidth,
                'unit' => $unit,
            ];
        }

        $sibling = max($this->renderContext->sibling, 1);
        $gutter = $this->getNormalizedGutterValue($unit);
        $reduction = ($gutter * ($sibling - 1)) / $sibling;
        $reducedWidth = max(0.0, self::normalizeUnitValue($parsedWidth - $reduction));

        if ($unit === 'px') {
            $floorWidth = (int) floor($reducedWidth);
            $fractional = $reducedWidth - $floorWidth;
            $extraPixels = max(0, min($sibling, (int) round($sibling * $fractional)));

            return [
                'parsedWidth' => (float) ($floorWidth + ($this->renderContext->index < $extraPixels ? 1 : 0)),
                'unit' => $unit,
            ];
        }

        return [
            'parsedWidth' => $reducedWidth,
            'unit' => $unit,
        ];
    }

    private function getDesktopGutterClassName(): string
    {
        $gutterUnit = $this->getParsedWidth()['unit'];
        $gutter = $this->getNormalizedGutterValue($gutterUnit);
        $gutterUnitToken = $gutterUnit === '%' ? 'per' : $gutterUnit;
        $directionToken = $this->renderContext->direction === 'rtl' ? '-rtl' : '';
        $normalizedGutter = $gutterUnit === 'px'
            ? self::normalizePxValue($gutter)
            : $gutter;
        $gutterToken = str_replace('.', '-', self::formatUnitValue((float) $normalizedGutter));
        $sibling = max($this->renderContext->sibling, 1);
        $position = $this->renderContext->index + 1;

        return "mj-column-gutter-{$sibling}-{$position}-{$gutterUnitToken}-{$gutterToken}{$directionToken}";
    }

    private function getNormalizedGutterValue(string $targetUnit): float
    {
        $gutter = $this->renderContext->gutter;
        if ($gutter === null || $gutter === '') {
            return 0.0;
        }

        $parsed = WidthParser::parse($gutter, parseFloatToInt: false);
        if ($parsed['unit'] === $targetUnit) {
            return $parsed['value'];
        }

        $containerWidth = (float) $this->renderContext->containerWidth;

        if ($targetUnit === '%' && $parsed['unit'] === 'px') {
            return ($parsed['value'] / max($containerWidth, 1.0)) * 100;
        }

        if ($targetUnit === 'px' && $parsed['unit'] === '%') {
            return $containerWidth * $parsed['value'] / 100;
        }

        return $parsed['value'];
    }

    /**
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    private function getDesktopPaddingValues(string $unit): array
    {
        $zeros = ['top' => 0.0, 'right' => 0.0, 'bottom' => 0.0, 'left' => 0.0];
        $sibling = $this->renderContext->sibling;
        if ($sibling <= 1) {
            return $zeros;
        }

        $gutter = $this->getNormalizedGutterValue($unit);
        $normalizedGutter = $unit === 'px' ? (float) self::normalizePxValue($gutter) : $gutter;
        $halfLeading = $unit === 'px' ? ceil($normalizedGutter / 2) : $normalizedGutter / 2;
        $halfTrailing = $unit === 'px' ? floor($normalizedGutter / 2) : $normalizedGutter / 2;
        $isRtl = $this->renderContext->direction === 'rtl';
        $first = $this->renderContext->first;
        $last = $this->renderContext->last;

        if ($isRtl) {
            return [
                'top' => 0.0,
                'right' => $first ? 0.0 : $halfTrailing,
                'bottom' => 0.0,
                'left' => $last ? 0.0 : $halfLeading,
            ];
        }

        return [
            'top' => 0.0,
            'right' => $last ? 0.0 : $halfLeading,
            'bottom' => 0.0,
            'left' => $first ? 0.0 : $halfTrailing,
        ];
    }

    /**
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    private function getMobilePaddingValues(): array
    {
        $gutter = $this->getNormalizedGutterValue('%');
        $half = $gutter / 2;

        return [
            'top' => $this->renderContext->first ? 0.0 : $half,
            'right' => 0.0,
            'bottom' => $this->renderContext->last ? 0.0 : $half,
            'left' => 0.0,
        ];
    }

    private static function formatPadding(float $top, float $right, float $bottom, float $left, string $unit): string
    {
        if ($unit === 'px') {
            return self::normalizePxValue($top) . 'px '
                . self::normalizePxValue($right) . 'px '
                . self::normalizePxValue($bottom) . 'px '
                . self::normalizePxValue($left) . 'px';
        }

        return self::formatUnitValue($top) . $unit . ' '
            . self::formatUnitValue($right) . $unit . ' '
            . self::formatUnitValue($bottom) . $unit . ' '
            . self::formatUnitValue($left) . $unit;
    }

    private function getDesktopPadding(): string
    {
        $unit = $this->getParsedWidth()['unit'];
        $values = $this->getDesktopPaddingValues($unit);

        return self::formatPadding($values['top'], $values['right'], $values['bottom'], $values['left'], $unit);
    }

    private function getMobilePadding(): string
    {
        $values = $this->getMobilePaddingValues();

        return self::formatPadding($values['top'], $values['right'], $values['bottom'], $values['left'], '%');
    }

    /**
     * @return array<string, string>
     */
    private function getMobileGutterStyles(): array
    {
        if (!$this->hasColumnGutter()) {
            return [];
        }

        if ($this->renderContext->isInGroup) {
            return ['padding' => $this->getDesktopPadding()];
        }

        return ['padding' => $this->getMobilePadding()];
    }

    /**
     * @return array<string, string>
     */
    private function getOutlookGutterStyles(): array
    {
        if (!$this->hasColumnGutter()) {
            return [];
        }

        $values = $this->getDesktopPaddingValues('px');

        return [
            'padding' => self::formatPadding($values['top'], $values['right'], $values['bottom'], $values['left'], 'px'),
        ];
    }

    private function hasColumnGutter(): bool
    {
        $gutter = $this->renderContext->gutter;

        return $gutter !== null && $gutter !== '';
    }

    /** Match MJML 5.4.0 `Number(parseFloat(value).toFixed(6))`. */
    private static function normalizeUnitValue(float $value): float
    {
        return (float) number_format($value, 6, '.', '');
    }

    private static function formatUnitValue(float $value): string
    {
        $formatted = number_format($value, 6, '.', '');
        if (str_contains($formatted, '.')) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted === '' ? '0' : $formatted;
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
        $classesName = $this->getColumnClass();

        if ($this->hasColumnGutter()) {
            $classesName .= ' ' . $this->getDesktopGutterClassName();
        }

        $classesName .= ' mj-outlook-group-fix';

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
