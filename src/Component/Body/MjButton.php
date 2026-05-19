<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;
use Shyim\Mjml\Helper\WidthParser;

final class MjButton extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-button';
    }

    public static function isEndingTag(): bool
    {
        return true;
    }

    public static function allowedAttributes(): array
    {
        return [
            'align' => 'enum(left,center,right)',
            'background-color' => 'color',
            'border-bottom' => 'string',
            'border-left' => 'string',
            'border-radius' => 'string',
            'border-right' => 'string',
            'border-top' => 'string',
            'border' => 'string',
            'color' => 'color',
            'container-background-color' => 'color',
            'font-family' => 'string',
            'font-size' => 'unit(px)',
            'font-style' => 'string',
            'font-weight' => 'string',
            'height' => 'unit(px,%)',
            'href' => 'string',
            'name' => 'string',
            'title' => 'string',
            'inner-padding' => 'unit(px,%){1,4}',
            'letter-spacing' => 'unitWithNegative(px,em)',
            'line-height' => 'unit(px,%,)',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'padding' => 'unit(px,%){1,4}',
            'rel' => 'string',
            'target' => 'string',
            'text-decoration' => 'string',
            'text-transform' => 'string',
            'vertical-align' => 'enum(top,bottom,middle)',
            'text-align' => 'enum(left,right,center)',
            'width' => 'unit(px,%)',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'align' => 'center',
            'background-color' => '#414141',
            'border' => 'none',
            'border-radius' => '3px',
            'color' => '#ffffff',
            'font-family' => 'Ubuntu, Helvetica, Arial, sans-serif',
            'font-size' => '13px',
            'font-weight' => 'normal',
            'inner-padding' => '10px 25px',
            'line-height' => '120%',
            'padding' => '10px 25px',
            'target' => '_blank',
            'text-decoration' => 'none',
            'text-transform' => 'none',
            'vertical-align' => 'middle',
        ];
    }

    protected function getStyles(): array
    {
        return [
            'table' => [
                'border-collapse' => 'separate',
                'width' => $this->getAttribute('width'),
                'line-height' => '100%',
            ],
            'td' => [
                'border' => $this->getAttribute('border'),
                'border-bottom' => $this->getAttribute('border-bottom'),
                'border-left' => $this->getAttribute('border-left'),
                'border-radius' => $this->getAttribute('border-radius'),
                'border-right' => $this->getAttribute('border-right'),
                'border-top' => $this->getAttribute('border-top'),
                'cursor' => 'auto',
                'font-style' => $this->getAttribute('font-style'),
                'height' => $this->getAttribute('height'),
                'mso-padding-alt' => $this->getAttribute('inner-padding'),
                'text-align' => $this->getAttribute('text-align'),
                'background' => $this->getAttribute('background-color'),
            ],
            'content' => [
                'display' => 'inline-block',
                'width' => $this->calculateAWidth($this->getAttribute('width')),
                'background' => $this->getAttribute('background-color'),
                'color' => $this->getAttribute('color'),
                'font-family' => $this->getAttribute('font-family'),
                'font-size' => $this->getAttribute('font-size'),
                'font-style' => $this->getAttribute('font-style'),
                'font-weight' => $this->getAttribute('font-weight'),
                'line-height' => $this->getAttribute('line-height'),
                'letter-spacing' => $this->getAttribute('letter-spacing'),
                'margin' => '0',
                'text-decoration' => $this->getAttribute('text-decoration'),
                'text-transform' => $this->getAttribute('text-transform'),
                'padding' => $this->getAttribute('inner-padding'),
                'mso-padding-alt' => '0px',
                'border-radius' => $this->getAttribute('border-radius'),
            ],
        ];
    }

    private function calculateAWidth(?string $width): ?string
    {
        if ($width === null) {
            return null;
        }

        $parsed = WidthParser::parse($width);

        // impossible to handle percents because it depends on padding and text width
        if ($parsed['unit'] !== 'px') {
            return null;
        }

        $box = $this->getBoxWidths();
        $borders = $box['borders'];

        $innerPaddings = $this->getShorthandAttrValue('inner-padding', 'left')
            + $this->getShorthandAttrValue('inner-padding', 'right');

        return ((int) $parsed['value'] - $innerPaddings - $borders) . 'px';
    }

    public function render(): string
    {
        $tag = $this->getAttribute('href') ? 'a' : 'p';

        $bgColor = $this->getAttribute('background-color');

        $tagAttrs = [
            'href' => $this->getAttribute('href'),
            'name' => $this->getAttribute('name'),
            'rel' => $this->getAttribute('rel'),
            'title' => $this->getAttribute('title'),
            'style' => 'content',
        ];

        if ($tag === 'a') {
            $tagAttrs['target'] = $this->getAttribute('target');
        }

        return '<table' . $this->htmlAttributes([
            'border' => '0',
            'cellpadding' => '0',
            'cellspacing' => '0',
            'role' => 'presentation',
            'style' => 'table',
        ]) . '><tbody><tr><td' . $this->htmlAttributes([
            'align' => 'center',
            'bgcolor' => $bgColor === 'none' ? null : $bgColor,
            'role' => 'presentation',
            'style' => 'td',
            'valign' => $this->getAttribute('vertical-align'),
        ]) . '><' . $tag . $this->htmlAttributes($tagAttrs) . '>'
            . ' ' . $this->getContent() . ' '
            . '</' . $tag . '></td></tr></tbody></table>';
    }
}
