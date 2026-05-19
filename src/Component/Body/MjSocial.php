<?php

declare(strict_types=1);

namespace Mjml\Component\Body;

use Mjml\Component\BodyComponent;

final class MjSocial extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-social';
    }

    public static function allowedAttributes(): array
    {
        return [
            'align' => 'enum(left,right,center)',
            'border-radius' => 'string',
            'container-background-color' => 'color',
            'color' => 'color',
            'font-family' => 'string',
            'font-size' => 'unit(px)',
            'font-style' => 'string',
            'font-weight' => 'string',
            'icon-size' => 'unit(px,%)',
            'icon-height' => 'unit(px,%)',
            'icon-padding' => 'unit(px,%){1,4}',
            'inner-padding' => 'unit(px,%){1,4}',
            'line-height' => 'unit(px,%,)',
            'mode' => 'enum(horizontal,vertical)',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'padding' => 'unit(px,%){1,4}',
            'table-layout' => 'enum(auto,fixed)',
            'text-padding' => 'unit(px,%){1,4}',
            'text-decoration' => 'string',
            'vertical-align' => 'enum(top,bottom,middle)',
            'css-class' => 'string',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'align' => 'center',
            'border-radius' => '3px',
            'color' => '#333333',
            'font-family' => 'Ubuntu, Helvetica, Arial, sans-serif',
            'font-size' => '13px',
            'icon-size' => '20px',
            'line-height' => '22px',
            'mode' => 'horizontal',
            'padding' => '10px 25px',
            'text-decoration' => 'none',
        ];
    }

    protected function getStyles(): array
    {
        return [
            'tableVertical' => [
                'margin' => '0px',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getSocialElementAttributes(): array
    {
        $base = [];
        $innerPadding = $this->getAttribute('inner-padding');
        if ($innerPadding !== null) {
            $base['padding'] = $innerPadding;
        }

        foreach ([
            'border-radius', 'color', 'font-family', 'font-size',
            'font-weight', 'font-style', 'icon-size', 'icon-height',
            'icon-padding', 'text-padding', 'line-height', 'text-decoration',
        ] as $attr) {
            $val = $this->getAttribute($attr);
            if ($val !== null) {
                $base[$attr] = $val;
            }
        }

        return $base;
    }

    private function renderHorizontal(): string
    {
        $align = $this->getAttribute('align') ?? 'center';
        $elementAttrs = $this->getSocialElementAttributes();

        $childrenHtml = $this->renderChildren(
            attributes: $elementAttrs,
            renderer: function (BodyComponent $component) use ($align): string {
                if ($component::isRawElement()) {
                    return $component->render();
                }

                return '<!--[if mso | IE]><td><![endif]-->'
                    . '<table'
                    . $component->htmlAttributes([
                        'align' => $align,
                        'border' => '0',
                        'cellpadding' => '0',
                        'cellspacing' => '0',
                        'role' => 'presentation',
                        'style' => [
                            'float' => 'none',
                            'display' => 'inline-table',
                        ],
                    ])
                    . '><tbody>'
                    . $component->render()
                    . '</tbody></table>'
                    . '<!--[if mso | IE]></td><![endif]-->';
            },
        );

        return '<!--[if mso | IE]>'
            . '<table'
            . $this->htmlAttributes([
                'align' => $align,
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'role' => 'presentation',
            ])
            . ' ><tr>'
            . '<![endif]-->'
            . $childrenHtml
            . '<!--[if mso | IE]>'
            . '</tr></table>'
            . '<![endif]-->';
    }

    private function renderVertical(): string
    {
        $elementAttrs = $this->getSocialElementAttributes();

        $childrenHtml = $this->renderChildren(attributes: $elementAttrs);

        return '<table'
            . $this->htmlAttributes([
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'role' => 'presentation',
                'style' => 'tableVertical',
            ])
            . '><tbody>'
            . $childrenHtml
            . '</tbody></table>';
    }

    public function render(): string
    {
        if ($this->getAttribute('mode') === 'horizontal') {
            return $this->renderHorizontal();
        }

        return $this->renderVertical();
    }
}
