<?php

declare(strict_types=1);

namespace Mjml\Component\Body;

use Mjml\Component\BodyComponent;

final class MjAccordionTitle extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-accordion-title';
    }

    public static function isEndingTag(): bool
    {
        return true;
    }

    public static function allowedAttributes(): array
    {
        return [
            'background-color' => 'color',
            'color' => 'color',
            'font-size' => 'unit(px)',
            'font-family' => 'string',
            'element-font-family' => 'string',
            'accordion-font-family' => 'string',
            'font-weight' => 'string',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'padding' => 'unit(px,%){1,4}',
            'css-class' => 'string',
            'border' => 'string',
            'icon-align' => 'enum(top,middle,bottom)',
            'icon-width' => 'unit(px,%)',
            'icon-height' => 'unit(px,%)',
            'icon-wrapped-url' => 'string',
            'icon-wrapped-alt' => 'string',
            'icon-unwrapped-url' => 'string',
            'icon-unwrapped-alt' => 'string',
            'icon-position' => 'enum(left,right)',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'font-size' => '13px',
            'padding' => '16px',
        ];
    }

    protected function getStyles(): array
    {
        return [
            'td' => [
                'width' => '100%',
                'background-color' => $this->getAttribute('background-color'),
                'color' => $this->getAttribute('color'),
                'font-size' => $this->getAttribute('font-size'),
                'font-family' => $this->resolveFontFamily(),
                'font-weight' => $this->getAttribute('font-weight'),
                'padding' => $this->getAttribute('padding'),
                'padding-bottom' => $this->getAttribute('padding-bottom'),
                'padding-left' => $this->getAttribute('padding-left'),
                'padding-right' => $this->getAttribute('padding-right'),
                'padding-top' => $this->getAttribute('padding-top'),
            ],
            'table' => [
                'width' => '100%',
                'border-bottom' => $this->getAttribute('border'),
            ],
            'td2' => [
                'padding' => '16px',
                'background' => $this->getAttribute('background-color'),
                'vertical-align' => $this->getAttribute('icon-align'),
            ],
            'img' => [
                'display' => 'none',
                'width' => $this->getAttribute('icon-width'),
                'height' => $this->getAttribute('icon-height'),
            ],
        ];
    }

    private function resolveFontFamily(): string
    {
        $fontFamily = $this->getAttribute('font-family');
        if ($fontFamily !== null) {
            return $fontFamily;
        }

        $elementFontFamily = $this->getAttribute('element-font-family');
        if ($elementFontFamily !== null) {
            return $elementFontFamily;
        }

        $accordionFontFamily = $this->getAttribute('accordion-font-family');
        if ($accordionFontFamily !== null) {
            return $accordionFontFamily;
        }

        return 'Ubuntu, Helvetica, Arial, sans-serif';
    }

    private function renderTitle(): string
    {
        return '<td'
            . $this->htmlAttributes([
                'class' => $this->getAttribute('css-class'),
                'style' => 'td',
            ])
            . '>'
            . ' ' . $this->getContent() . ' '
            . '</td>';
    }

    private function renderIcons(): string
    {
        return '<!--[if !mso | IE]><!-->'
            . '<td'
            . $this->htmlAttributes([
                'class' => 'mj-accordion-ico',
                'style' => 'td2',
            ])
            . '>'
            . '<img'
            . $this->htmlAttributes([
                'src' => $this->getAttribute('icon-wrapped-url'),
                'alt' => $this->getAttribute('icon-wrapped-alt'),
                'class' => 'mj-accordion-more',
                'style' => 'img',
            ])
            . ' />'
            . '<img'
            . $this->htmlAttributes([
                'src' => $this->getAttribute('icon-unwrapped-url'),
                'alt' => $this->getAttribute('icon-unwrapped-alt'),
                'class' => 'mj-accordion-less',
                'style' => 'img',
            ])
            . ' />'
            . '</td>'
            . '<!--<![endif]-->';
    }

    public function render(): string
    {
        $iconPosition = $this->getAttribute('icon-position') ?? 'right';

        if ($iconPosition === 'right') {
            $content = $this->renderTitle() . $this->renderIcons();
        } else {
            $content = $this->renderIcons() . $this->renderTitle();
        }

        return '<div' . $this->htmlAttributes(['class' => 'mj-accordion-title']) . '>'
            . '<table'
            . $this->htmlAttributes([
                'cellspacing' => '0',
                'cellpadding' => '0',
                'style' => 'table',
            ])
            . '><tbody><tr>'
            . $content
            . '</tr></tbody></table>'
            . '</div>';
    }
}
