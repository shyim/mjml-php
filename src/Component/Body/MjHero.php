<?php

declare(strict_types=1);

namespace Mjml\Component\Body;

use Mjml\Component\BodyComponent;
use Mjml\Context\RenderContext;

final class MjHero extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-hero';
    }

    public static function allowedAttributes(): array
    {
        return [
            'mode' => 'string',
            'height' => 'unit(px,%)',
            'background-url' => 'string',
            'background-width' => 'unit(px,%)',
            'background-height' => 'unit(px,%)',
            'background-position' => 'string',
            'border-radius' => 'string',
            'container-background-color' => 'color',
            'inner-background-color' => 'color',
            'inner-padding' => 'unit(px,%){1,4}',
            'inner-padding-top' => 'unit(px,%)',
            'inner-padding-left' => 'unit(px,%)',
            'inner-padding-right' => 'unit(px,%)',
            'inner-padding-bottom' => 'unit(px,%)',
            'padding' => 'unit(px,%){1,4}',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'background-color' => 'color',
            'vertical-align' => 'enum(top,bottom,middle)',
            'width' => 'unit(px,%)',
            'css-class' => 'string',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public static function defaultAttributes(): array
    {
        return [
            'mode' => 'fixed-height',
            'height' => '0px',
            'background-url' => null,
            'background-position' => 'center center',
            'padding' => '0px',
            'padding-top' => null,
            'padding-bottom' => null,
            'padding-left' => null,
            'padding-right' => null,
            'background-color' => '#ffffff',
            'vertical-align' => 'top',
        ];
    }

    public function getChildContext(): RenderContext
    {
        $containerWidth = $this->renderContext->containerWidth;
        $paddingSize = $this->getShorthandAttrValue('padding', 'left')
            + $this->getShorthandAttrValue('padding', 'right');

        $parsedWidth = (float) $containerWidth;
        $currentContainerWidth = ($parsedWidth - $paddingSize) . 'px';

        return $this->renderContext->withContainerWidth($currentContainerWidth);
    }

    protected function getStyles(): array
    {
        $containerWidth = $this->renderContext->containerWidth;
        $childContext = $this->getChildContext();
        $currentContainerWidth = $childContext->containerWidth;
        $bgHeight = (int) ($this->getAttribute('background-height') ?? '0');
        $bgWidth = (int) ($this->getAttribute('background-width') ?? '1');
        $backgroundRatio = $bgWidth > 0 ? (int) round(($bgHeight / $bgWidth) * 100) : 0;
        $width = $this->getAttribute('background-width') ?: $containerWidth;

        return [
            'div' => [
                'margin' => '0 auto',
                'max-width' => $containerWidth,
            ],
            'table' => [
                'width' => '100%',
            ],
            'tr' => [
                'vertical-align' => 'top',
            ],
            'td-fluid' => [
                'width' => '0.01%',
                'padding-bottom' => $backgroundRatio . '%',
                'mso-padding-bottom-alt' => '0',
            ],
            'outlook-table' => [
                'width' => $containerWidth,
            ],
            'outlook-td' => [
                'line-height' => '0',
                'font-size' => '0',
                'mso-line-height-rule' => 'exactly',
            ],
            'outlook-inner-table' => [
                'width' => $currentContainerWidth,
            ],
            'outlook-image' => [
                'border' => '0',
                'height' => $this->getAttribute('background-height'),
                'mso-position-horizontal' => 'center',
                'position' => 'absolute',
                'top' => '0',
                'width' => $width,
                'z-index' => '-3',
            ],
            'outlook-inner-td' => [
                'background-color' => $this->getAttribute('inner-background-color'),
                'padding' => $this->getAttribute('inner-padding'),
                'padding-top' => $this->getAttribute('inner-padding-top'),
                'padding-left' => $this->getAttribute('inner-padding-left'),
                'padding-right' => $this->getAttribute('inner-padding-right'),
                'padding-bottom' => $this->getAttribute('inner-padding-bottom'),
            ],
            'inner-table' => [
                'width' => '100%',
                'margin' => '0px',
            ],
            'inner-div' => [
                'background-color' => $this->getAttribute('inner-background-color'),
                'float' => $this->getAttribute('align'),
                'margin' => '0px auto',
                'width' => $this->getAttribute('width'),
                'padding' => $this->getAttribute('inner-padding'),
                'padding-top' => $this->getAttribute('inner-padding-top'),
                'padding-left' => $this->getAttribute('inner-padding-left'),
                'padding-right' => $this->getAttribute('inner-padding-right'),
                'padding-bottom' => $this->getAttribute('inner-padding-bottom'),
            ],
        ];
    }

    private function getBackground(): string
    {
        $parts = array_filter([
            $this->getAttribute('background-color'),
            ...($this->getAttribute('background-url')
                ? [
                    "url('" . $this->getAttribute('background-url') . "')",
                    'no-repeat',
                    $this->getAttribute('background-position') . ' / cover',
                ]
                : []),
        ]);

        return implode(' ', $parts);
    }

    private function renderContent(): string
    {
        $containerWidth = $this->renderContext->containerWidth;
        $childContext = $this->getChildContext();
        $currentContainerWidth = $childContext->containerWidth;
        $currentContainerWidthNum = (int) $currentContainerWidth;

        $childrenHtml = $this->renderChildren(
            renderer: function (BodyComponent $component): string {
                if ($component::isRawElement()) {
                    return $component->render();
                }

                return '<tr><td'
                    . $component->htmlAttributes([
                        'align' => $component->getAttribute('align'),
                        'background' => $component->getAttribute('container-background-color'),
                        'class' => $component->getAttribute('css-class'),
                        'style' => [
                            'background' => $component->getAttribute('container-background-color'),
                            'font-size' => '0px',
                            'padding' => $component->getAttribute('padding'),
                            'padding-top' => $component->getAttribute('padding-top'),
                            'padding-right' => $component->getAttribute('padding-right'),
                            'padding-bottom' => $component->getAttribute('padding-bottom'),
                            'padding-left' => $component->getAttribute('padding-left'),
                            'word-break' => 'break-word',
                        ],
                    ])
                    . '>'
                    . $component->render()
                    . '</td></tr>';
            },
        );

        return '<!--[if mso | IE]>'
            . '<table'
            . $this->htmlAttributes([
                'align' => $this->getAttribute('align'),
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'style' => 'outlook-inner-table',
                'width' => (string) $currentContainerWidthNum,
            ])
            . '><tr><td' . $this->htmlAttributes(['style' => 'outlook-inner-td']) . '>'
            . '<![endif]-->'
            . '<div' . $this->htmlAttributes([
                'align' => $this->getAttribute('align'),
                'class' => 'mj-hero-content',
                'style' => 'inner-div',
            ]) . '>'
            . '<table' . $this->htmlAttributes([
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'role' => 'presentation',
                'style' => 'inner-table',
            ]) . '><tbody><tr><td' . $this->htmlAttributes(['style' => 'inner-td']) . '>'
            . '<table' . $this->htmlAttributes([
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'role' => 'presentation',
                'style' => 'inner-table',
            ]) . '><tbody>'
            . $childrenHtml
            . '</tbody></table>'
            . '</td></tr></tbody></table>'
            . '</div>'
            . '<!--[if mso | IE]>'
            . '</td></tr></table>'
            . '<![endif]-->';
    }

    private function renderMode(): string
    {
        $commonStyle = [
            'background' => $this->getBackground(),
            'background-position' => $this->getAttribute('background-position'),
            'background-repeat' => 'no-repeat',
            'border-radius' => $this->getAttribute('border-radius'),
            'padding' => $this->getAttribute('padding'),
            'padding-top' => $this->getAttribute('padding-top'),
            'padding-left' => $this->getAttribute('padding-left'),
            'padding-right' => $this->getAttribute('padding-right'),
            'padding-bottom' => $this->getAttribute('padding-bottom'),
            'vertical-align' => $this->getAttribute('vertical-align'),
        ];

        if ($this->getAttribute('mode') === 'fluid-height') {
            $magicTd = $this->htmlAttributes(['style' => 'td-fluid']);

            return '<td' . $magicTd . ' />'
                . '<td' . $this->htmlAttributes([
                    'background' => $this->getAttribute('background-url'),
                    'style' => $commonStyle,
                ]) . '>'
                . $this->renderContent()
                . '</td>'
                . '<td' . $magicTd . ' />';
        }

        // fixed-height (default)
        $height = (int) ($this->getAttribute('height') ?? '0')
            - $this->getShorthandAttrValue('padding', 'top')
            - $this->getShorthandAttrValue('padding', 'bottom');

        $fixedStyle = array_merge($commonStyle, ['height' => $height . 'px']);

        return '<td'
            . $this->htmlAttributes([
                'background' => $this->getAttribute('background-url'),
                'style' => $fixedStyle,
                'height' => (string) $height,
            ])
            . '>'
            . $this->renderContent()
            . '</td>';
    }

    public function render(): string
    {
        $containerWidth = $this->renderContext->containerWidth;
        $containerWidthNum = (int) $containerWidth;

        return '<!--[if mso | IE]>'
            . '<table'
            . $this->htmlAttributes([
                'align' => 'center',
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'role' => 'presentation',
                'style' => 'outlook-table',
                'width' => (string) $containerWidthNum,
            ])
            . '><tr><td' . $this->htmlAttributes(['style' => 'outlook-td']) . '>'
            . '<v:image'
            . $this->htmlAttributes([
                'style' => 'outlook-image',
                'src' => $this->getAttribute('background-url'),
                'xmlns:v' => 'urn:schemas-microsoft-com:vml',
            ])
            . ' />'
            . '<![endif]-->'
            . '<div'
            . $this->htmlAttributes([
                'align' => $this->getAttribute('align'),
                'class' => $this->getAttribute('css-class'),
                'style' => 'div',
            ])
            . '>'
            . '<table'
            . $this->htmlAttributes([
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'role' => 'presentation',
                'style' => 'table',
            ])
            . '><tbody><tr'
            . $this->htmlAttributes(['style' => 'tr'])
            . '>'
            . $this->renderMode()
            . '</tr></tbody></table>'
            . '</div>'
            . '<!--[if mso | IE]>'
            . '</td></tr></table>'
            . '<![endif]-->';
    }
}
