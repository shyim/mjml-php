<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;
use Shyim\Mjml\Helper\WidthParser;

final class MjDivider extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-divider';
    }

    public static function allowedAttributes(): array
    {
        return [
            'border-color' => 'color',
            'border-style' => 'string',
            'border-width' => 'unit(px)',
            'container-background-color' => 'color',
            'padding' => 'unit(px,%){1,4}',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'width' => 'unit(px,%)',
            'align' => 'enum(left,center,right)',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'border-color' => '#000000',
            'border-style' => 'solid',
            'border-width' => '4px',
            'padding' => '10px 25px',
            'width' => '100%',
            'align' => 'center',
        ];
    }

    protected function getStyles(): array
    {
        $align = $this->getAttribute('align');
        $computeAlign = '0px auto';
        if ($align === 'left') {
            $computeAlign = '0px';
        } elseif ($align === 'right') {
            $computeAlign = '0px 0px 0px auto';
        }

        $borderTop = $this->getAttribute('border-style') . ' '
            . $this->getAttribute('border-width') . ' '
            . $this->getAttribute('border-color');

        $p = [
            'border-top' => $borderTop,
            'font-size' => '1px',
            'margin' => $computeAlign,
            'width' => $this->getAttribute('width'),
        ];

        return [
            'p' => $p,
            'outlook' => array_merge($p, [
                'width' => $this->getOutlookWidth(),
            ]),
        ];
    }

    private function getOutlookWidth(): string
    {
        $containerWidth = (int) $this->renderContext->containerWidth;
        $paddingSize = $this->getShorthandAttrValue('padding', 'left')
            + $this->getShorthandAttrValue('padding', 'right');

        $width = $this->getAttribute('width') ?? '100%';
        $parsed = WidthParser::parse($width);

        return match ($parsed['unit']) {
            '%' => (($containerWidth - $paddingSize) * ((int) $parsed['value'] / 100)) . 'px',
            'px' => $width,
            default => ($containerWidth - $paddingSize) . 'px',
        };
    }

    private function renderAfter(): string
    {
        $outlookWidth = $this->getOutlookWidth();

        return '<!--[if mso | IE]><table' . $this->htmlAttributes([
            'align' => $this->getAttribute('align'),
            'border' => '0',
            'cellpadding' => '0',
            'cellspacing' => '0',
            'style' => 'outlook',
            'role' => 'presentation',
            'width' => $outlookWidth,
        ]) . ' ><tr><td style="height:0;line-height:0;"> &nbsp; </td></tr></table><![endif]-->';
    }

    public function render(): string
    {
        return '<p' . $this->htmlAttributes(['style' => 'p']) . '></p>'
            . $this->renderAfter();
    }
}
