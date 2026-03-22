<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;

final class MjAccordionText extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-accordion-text';
    }

    public static function isEndingTag(): bool
    {
        return true;
    }

    public static function allowedAttributes(): array
    {
        return [
            'background-color' => 'color',
            'font-size' => 'unit(px)',
            'font-family' => 'string',
            'element-font-family' => 'string',
            'accordion-font-family' => 'string',
            'font-weight' => 'string',
            'letter-spacing' => 'unitWithNegative(px,em)',
            'line-height' => 'unit(px,%,)',
            'color' => 'color',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'padding' => 'unit(px,%){1,4}',
            'css-class' => 'string',
            'border' => 'string',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'font-size' => '13px',
            'line-height' => '1',
            'padding' => '16px',
        ];
    }

    protected function getStyles(): array
    {
        return [
            'td' => [
                'background' => $this->getAttribute('background-color'),
                'font-size' => $this->getAttribute('font-size'),
                'font-family' => $this->resolveFontFamily(),
                'font-weight' => $this->getAttribute('font-weight'),
                'letter-spacing' => $this->getAttribute('letter-spacing'),
                'line-height' => $this->getAttribute('line-height'),
                'color' => $this->getAttribute('color'),
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

    private function renderContent(): string
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

    public function render(): string
    {
        return '<div' . $this->htmlAttributes(['class' => 'mj-accordion-content']) . '>'
            . '<table'
            . $this->htmlAttributes([
                'cellspacing' => '0',
                'cellpadding' => '0',
                'style' => 'table',
            ])
            . '><tbody><tr>'
            . $this->renderContent()
            . '</tr></tbody></table>'
            . '</div>';
    }
}
