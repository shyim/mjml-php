<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;
use Shyim\Mjml\Helper\WidthParser;

final class MjImage extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-image';
    }

    public static function allowedAttributes(): array
    {
        return [
            'alt' => 'string',
            'href' => 'string',
            'name' => 'string',
            'src' => 'string',
            'srcset' => 'string',
            'sizes' => 'string',
            'title' => 'string',
            'rel' => 'string',
            'align' => 'enum(left,center,right)',
            'border' => 'string',
            'border-bottom' => 'string',
            'border-left' => 'string',
            'border-right' => 'string',
            'border-top' => 'string',
            'border-radius' => 'unit(px,%){1,4}',
            'container-background-color' => 'color',
            'fluid-on-mobile' => 'boolean',
            'padding' => 'unit(px,%){1,4}',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'target' => 'string',
            'width' => 'unit(px)',
            'height' => 'unit(px,auto)',
            'max-height' => 'unit(px,%)',
            'font-size' => 'unit(px)',
            'usemap' => 'string',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'alt' => '',
            'align' => 'center',
            'border' => '0',
            'height' => 'auto',
            'padding' => '10px 25px',
            'target' => '_blank',
            'font-size' => '13px',
        ];
    }

    protected function getStyles(): array
    {
        $contentWidth = $this->getContentWidth();
        $fullWidth = $this->getAttribute('full-width') === 'full-width';
        $parsed = WidthParser::parse((string) $contentWidth);

        return [
            'img' => [
                'border' => $this->getAttribute('border'),
                'border-left' => $this->getAttribute('border-left'),
                'border-right' => $this->getAttribute('border-right'),
                'border-top' => $this->getAttribute('border-top'),
                'border-bottom' => $this->getAttribute('border-bottom'),
                'border-radius' => $this->getAttribute('border-radius'),
                'display' => 'block',
                'outline' => 'none',
                'text-decoration' => 'none',
                'height' => $this->getAttribute('height'),
                'max-height' => $this->getAttribute('max-height'),
                'min-width' => $fullWidth ? '100%' : null,
                'width' => '100%',
                'max-width' => $fullWidth ? '100%' : null,
                'font-size' => $this->getAttribute('font-size'),
            ],
            'td' => [
                'width' => $fullWidth ? null : $parsed['value'] . $parsed['unit'],
            ],
            'table' => [
                'min-width' => $fullWidth ? '100%' : null,
                'max-width' => $fullWidth ? '100%' : null,
                'width' => $fullWidth ? $parsed['value'] . $parsed['unit'] : null,
                'border-collapse' => 'collapse',
                'border-spacing' => '0px',
            ],
        ];
    }

    private function getContentWidth(): int
    {
        $widthAttr = $this->getAttribute('width');
        $width = $widthAttr !== null ? (int) $widthAttr : PHP_INT_MAX;

        $box = $this->getBoxWidths();

        return min($box['box'], $width);
    }

    private function renderImage(): string
    {
        $height = $this->getAttribute('height');

        $imgAttrs = [
            'alt' => $this->getAttribute('alt'),
            'src' => $this->getAttribute('src'),
            'srcset' => $this->getAttribute('srcset'),
            'sizes' => $this->getAttribute('sizes'),
            'style' => 'img',
            'title' => $this->getAttribute('title'),
            'width' => (string) $this->getContentWidth(),
            'usemap' => $this->getAttribute('usemap'),
        ];

        if ($height !== null) {
            $imgAttrs['height'] = $height === 'auto' ? $height : (string) (int) $height;
        }

        $img = '<img' . $this->htmlAttributes($imgAttrs) . ' />';

        if ($this->getAttribute('href') !== null) {
            return '<a' . $this->htmlAttributes([
                'href' => $this->getAttribute('href'),
                'target' => $this->getAttribute('target'),
                'rel' => $this->getAttribute('rel'),
                'name' => $this->getAttribute('name'),
                'title' => $this->getAttribute('title'),
            ]) . '>' . $img . '</a>';
        }

        return $img;
    }

    public function getHeadStyle(): string
    {
        $breakpoint = $this->globalContext->breakpoint;

        // makeLowerBreakpoint: subtract 1 from breakpoint pixels
        $lowerBreakpoint = $breakpoint;
        if (preg_match('/(\d+)/', $breakpoint, $m)) {
            $lowerBreakpoint = ((int) $m[1] - 1) . 'px';
        }

        return '@media only screen and (max-width:' . $lowerBreakpoint . ') {' . "\n"
            . '  table.mj-full-width-mobile { width: 100% !important; }' . "\n"
            . '  td.mj-full-width-mobile { width: auto !important; }' . "\n"
            . '}';
    }

    public function render(): string
    {
        // Register head style for fluid-on-mobile support
        $this->globalContext->addHeadStyle('mj-image', fn(string $breakpoint) => $this->getHeadStyle());

        $fluidOnMobile = $this->getAttribute('fluid-on-mobile') === 'true';

        return '<table' . $this->htmlAttributes([
            'border' => '0',
            'cellpadding' => '0',
            'cellspacing' => '0',
            'role' => 'presentation',
            'style' => 'table',
            'class' => $fluidOnMobile ? 'mj-full-width-mobile' : null,
        ]) . '><tbody><tr><td' . $this->htmlAttributes([
            'style' => 'td',
            'class' => $fluidOnMobile ? 'mj-full-width-mobile' : null,
        ]) . '>' . $this->renderImage() . '</td></tr></tbody></table>';
    }
}
