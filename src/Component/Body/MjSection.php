<?php

declare(strict_types=1);

namespace Mjml\Component\Body;

use Mjml\Component\BodyComponent;
use Mjml\Context\RenderContext;
use Mjml\Helper\BackgroundParser;

class MjSection extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-section';
    }

    public static function allowedAttributes(): array
    {
        return [
            'background-color' => 'color',
            'background-url' => 'string',
            'background-repeat' => 'enum(repeat,no-repeat)',
            'background-size' => 'string',
            'background-position' => 'string',
            'background-position-x' => 'string',
            'background-position-y' => 'string',
            'border' => 'string',
            'border-bottom' => 'string',
            'border-left' => 'string',
            'border-radius' => 'string',
            'border-right' => 'string',
            'border-top' => 'string',
            'direction' => 'enum(ltr,rtl)',
            'full-width' => 'enum(full-width,false,)',
            'padding' => 'unit(px,%){1,4}',
            'padding-top' => 'unit(px,%)',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'text-align' => 'enum(left,center,right)',
            'text-padding' => 'unit(px,%){1,4}',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'background-repeat' => 'repeat',
            'background-size' => 'auto',
            'background-position' => 'top center',
            'direction' => 'ltr',
            'padding' => '20px 0',
            'text-align' => 'center',
            'text-padding' => '4px 4px 4px 0',
        ];
    }

    public function getChildContext(): RenderContext
    {
        $boxWidths = $this->getBoxWidths();

        return new RenderContext(
            containerWidth: $boxWidths['box'] . 'px',
            sectionGap: $this->renderContext->sectionGap,
            columnGap: $this->renderContext->columnGap,
        );
    }

    protected function getStyles(): array
    {
        $containerWidth = $this->renderContext->containerWidth;
        $fullWidth = $this->isFullWidth();
        $hasBorderRadius = $this->hasBorderRadius();
        $isFirstSection = $this->renderContext->index === 0;

        if ($this->hasBackground()) {
            $background = [
                'background' => $this->getBackground(),
                'background-position' => $this->getBackgroundString(),
                'background-repeat' => $this->getAttribute('background-repeat'),
                'background-size' => $this->getAttribute('background-size'),
            ];
        } else {
            $background = [
                'background' => $this->getAttribute('background-color'),
                'background-color' => $this->getAttribute('background-color'),
            ];
        }

        $gap = $this->renderContext->sectionGap;
        $hasGap = $gap !== null && $gap !== '';
        $marginTop = (!$isFirstSection && $hasGap) ? $gap : null;

        return [
            'tableFullwidth' => array_merge(
                $fullWidth ? $background : [],
                ['width' => '100%'],
            ),
            'table' => array_merge(
                $fullWidth ? [] : $background,
                ['width' => '100%'],
                $hasBorderRadius ? ['border-collapse' => 'separate'] : [],
            ),
            'td' => [
                'border' => $this->getAttribute('border'),
                'border-bottom' => $this->getAttribute('border-bottom'),
                'border-left' => $this->getAttribute('border-left'),
                'border-right' => $this->getAttribute('border-right'),
                'border-top' => $this->getAttribute('border-top'),
                'border-radius' => $this->getAttribute('border-radius'),
                'direction' => $this->getAttribute('direction'),
                'font-size' => '0px',
                'padding' => $this->getAttribute('padding'),
                'padding-bottom' => $this->getAttribute('padding-bottom'),
                'padding-left' => $this->getAttribute('padding-left'),
                'padding-right' => $this->getAttribute('padding-right'),
                'padding-top' => $this->getAttribute('padding-top'),
                'text-align' => $this->getAttribute('text-align'),
            ],
            'div' => array_merge(
                $fullWidth ? [] : $background,
                [
                    'margin' => '0px auto',
                    'max-width' => $containerWidth,
                    'border-radius' => $this->getAttribute('border-radius'),
                ],
                $hasBorderRadius ? ['overflow' => 'hidden'] : [],
                $marginTop !== null ? ['margin-top' => $marginTop] : [],
            ),
            'innerDiv' => [
                'line-height' => '0',
                'font-size' => '0',
            ],
        ];
    }

    protected function getBackground(): string
    {
        $parts = array_filter([
            $this->getAttribute('background-color'),
            ...($this->hasBackground() ? [
                "url('" . $this->getAttribute('background-url') . "')",
                $this->getBackgroundString(),
                '/ ' . $this->getAttribute('background-size'),
                $this->getAttribute('background-repeat'),
            ] : []),
        ], static fn($v) => $v !== null && $v !== '');

        return implode(' ', $parts);
    }

    protected function getBackgroundString(): string
    {
        $pos = $this->getBackgroundPosition();

        return $pos['posX'] . ' ' . $pos['posY'];
    }

    /**
     * @return array{posX: string, posY: string}
     */
    protected function getBackgroundPosition(): array
    {
        $parsed = BackgroundParser::parsePosition(
            $this->getAttribute('background-position') ?? 'top center',
        );

        return [
            'posX' => $this->getAttribute('background-position-x') ?? $parsed['x'],
            'posY' => $this->getAttribute('background-position-y') ?? $parsed['y'],
        ];
    }

    protected function hasBackground(): bool
    {
        return $this->getAttribute('background-url') !== null;
    }

    protected function isFullWidth(): bool
    {
        return $this->getAttribute('full-width') === 'full-width';
    }

    protected function hasBorderRadius(): bool
    {
        $borderRadius = $this->getAttribute('border-radius');

        return $borderRadius !== null && $borderRadius !== '';
    }

    protected function renderBefore(): string
    {
        $containerWidth = $this->renderContext->containerWidth;
        $isFirstSection = $this->renderContext->index === 0;
        $gap = $this->renderContext->sectionGap;
        $hasGap = $gap !== null && $gap !== '';

        $bgcolorAttr = [];
        $bgColor = $this->getAttribute('background-color');
        if ($bgColor !== null) {
            $bgcolorAttr['bgcolor'] = $bgColor;
        }

        $outlookClass = self::suffixCssClasses($this->getAttribute('css-class'), 'outlook');

        $styleAttrs = ['width' => $containerWidth];
        if (!$isFirstSection && $hasGap) {
            $styleAttrs['padding-top'] = $gap;
        }

        $attrs = [
            'align' => 'center',
            'border' => '0',
            'cellpadding' => '0',
            'cellspacing' => '0',
            'class' => $outlookClass,
            'role' => 'presentation',
            'style' => $styleAttrs,
            'width' => (string) (int) ((float) $containerWidth),
        ];
        if (!$hasGap) {
            $attrs = array_merge($attrs, $bgcolorAttr);
        }

        return '<!--[if mso | IE]>'
            . '<table' . $this->htmlAttributes($attrs) . ' >'
            . '<tr>'
            . '<td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">'
            . '<![endif]-->';
    }

    protected function renderAfter(): string
    {
        return '<!--[if mso | IE]>'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '<![endif]-->';
    }

    protected function renderWrappedChildren(): string
    {
        return '<!--[if mso | IE]><tr><![endif]-->'
            . $this->renderChildren(
                renderer: function (BodyComponent $component): string {
                    if ($component::isRawElement()) {
                        return $component->render();
                    }

                    $outlookClass = self::suffixCssClasses(
                        $component->getAttribute('css-class'),
                        'outlook',
                    );

                    $tdAttrs = [
                        'align' => $component->getAttribute('align'),
                        'class' => $outlookClass,
                        'style' => 'tdOutlook',
                    ];

                    return '<!--[if mso | IE]><td' . $component->htmlAttributes($tdAttrs) . ' ><![endif]-->'
                        . $component->render()
                        . '<!--[if mso | IE]></td><![endif]-->';
                },
            )
            . '<!--[if mso | IE]></tr><![endif]-->';
    }

    protected function renderWithBackground(string $content): string
    {
        $fullWidth = $this->isFullWidth();
        $containerWidth = $this->renderContext->containerWidth;

        $bgPos = $this->getBackgroundPosition();
        $bgPosX = BackgroundParser::positionToPercentage($bgPos['posX'], true);
        $bgPosY = BackgroundParser::positionToPercentage($bgPos['posY'], false);

        $bgRepeat = $this->getAttribute('background-repeat') === 'repeat';

        $vml = BackgroundParser::calculateVmlPositions($bgPosX, $bgPosY, $bgRepeat);
        $vOriginX = $vml['originX'];
        $vPosX = $vml['posX'];
        $vOriginY = $vml['originY'];
        $vPosY = $vml['posY'];

        // Determine VML size attributes
        $vSizeAttributes = [];
        $backgroundSize = $this->getAttribute('background-size') ?? 'auto';

        if ($backgroundSize === 'cover' || $backgroundSize === 'contain') {
            $vSizeAttributes = [
                'size' => '1,1',
                'aspect' => $backgroundSize === 'cover' ? 'atleast' : 'atmost',
            ];
        } elseif ($backgroundSize !== 'auto') {
            $bgSplit = preg_split('/\s+/', $backgroundSize);
            if ($bgSplit !== false && \count($bgSplit) === 1) {
                $vSizeAttributes = [
                    'size' => $backgroundSize,
                    'aspect' => 'atmost',
                ];
            } elseif ($bgSplit !== false) {
                $vSizeAttributes = [
                    'size' => implode(',', $bgSplit),
                ];
            }
        }

        // Determine VML type
        $vmlType = ($this->getAttribute('background-repeat') === 'no-repeat') ? 'frame' : 'tile';

        if ($backgroundSize === 'auto') {
            $vmlType = 'tile';
            $vOriginX = '0.5';
            $vPosX = '0.5';
            $vOriginY = '0';
            $vPosY = '0';
        }

        $vRectStyle = $fullWidth
            ? ['mso-width-percent' => '1000']
            : ['width' => $containerWidth];

        $vRectAttrs = [
            'style' => $vRectStyle,
            'xmlns:v' => 'urn:schemas-microsoft-com:vml',
            'fill' => 'true',
            'stroke' => 'false',
        ];

        $vFillAttrs = array_merge([
            'origin' => "{$vOriginX}, {$vOriginY}",
            'position' => "{$vPosX}, {$vPosY}",
            'src' => $this->getAttribute('background-url'),
            'color' => $this->getAttribute('background-color'),
            'type' => $vmlType,
        ], $vSizeAttributes);

        return '<!--[if mso | IE]>'
            . '<v:rect' . $this->htmlAttributes($vRectAttrs) . '>'
            . '<v:fill' . $this->htmlAttributes($vFillAttrs) . ' />'
            . '<v:textbox style="mso-fit-shape-to-text:true" inset="0,0,0,0">'
            . '<![endif]-->'
            . $content
            . '<!--[if mso | IE]>'
            . '</v:textbox>'
            . '</v:rect>'
            . '<![endif]-->';
    }

    protected function renderSection(): string
    {
        $hasBackground = $this->hasBackground();

        $divAttrs = ['style' => 'div'];
        if (!$this->isFullWidth()) {
            $cssClass = $this->getAttribute('css-class');
            if ($cssClass !== null) {
                $divAttrs['class'] = $cssClass;
            }
        }

        $tableAttrs = [
            'align' => 'center',
            'background' => $this->isFullWidth() ? null : $this->getAttribute('background-url'),
            'border' => '0',
            'cellpadding' => '0',
            'cellspacing' => '0',
            'role' => 'presentation',
            'style' => 'table',
        ];

        $output = '<div' . $this->htmlAttributes($divAttrs) . '>';

        if ($hasBackground) {
            $output .= '<div' . $this->htmlAttributes(['style' => 'innerDiv']) . '>';
        }

        $output .= '<table' . $this->htmlAttributes($tableAttrs) . '>'
            . '<tbody>'
            . '<tr>'
            . '<td' . $this->htmlAttributes(['style' => 'td']) . '>'
            . '<!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><![endif]-->'
            . $this->renderWrappedChildren()
            . '<!--[if mso | IE]></table><![endif]-->'
            . '</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>';

        if ($hasBackground) {
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    protected function renderFullWidth(): string
    {
        $section = $this->renderSection();
        $before = $this->renderBefore();
        $after = $this->renderAfter();

        $innerContent = $before . $section . $after;

        if ($this->hasBackground()) {
            $innerContent = $this->renderWithBackground($innerContent);
        }

        $tableAttrs = [
            'align' => 'center',
            'border' => '0',
            'cellpadding' => '0',
            'cellspacing' => '0',
            'role' => 'presentation',
            'style' => 'tableFullwidth',
        ];

        $cssClass = $this->getAttribute('css-class');
        if ($cssClass !== null) {
            $tableAttrs['class'] = $cssClass;
        }
        $tableAttrs['background'] = $this->getAttribute('background-url');

        return '<table' . $this->htmlAttributes($tableAttrs) . '>'
            . '<tbody>'
            . '<tr>'
            . '<td>'
            . $innerContent
            . '</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>';
    }

    protected function renderSimple(): string
    {
        $section = $this->renderSection();
        $before = $this->renderBefore();
        $after = $this->renderAfter();

        if ($this->hasBackground()) {
            return $before . $this->renderWithBackground($section) . $after;
        }

        return $before . $section . $after;
    }

    public function render(): string
    {
        return $this->isFullWidth() ? $this->renderFullWidth() : $this->renderSimple();
    }

}
