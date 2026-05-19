<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;
use Shyim\Mjml\Context\RenderContext;

final class MjAccordion extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-accordion';
    }

    public static function allowedAttributes(): array
    {
        return [
            'container-background-color' => 'color',
            'border' => 'string',
            'font-family' => 'string',
            'icon-align' => 'enum(top,middle,bottom)',
            'icon-width' => 'unit(px,%)',
            'icon-height' => 'unit(px,%)',
            'icon-wrapped-url' => 'string',
            'icon-wrapped-alt' => 'string',
            'icon-unwrapped-url' => 'string',
            'icon-unwrapped-alt' => 'string',
            'icon-position' => 'enum(left,right)',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'padding' => 'unit(px,%){1,4}',
            'css-class' => 'string',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'border' => '2px solid black',
            'font-family' => 'Ubuntu, Helvetica, Arial, sans-serif',
            'icon-align' => 'middle',
            'icon-wrapped-url' => 'https://i.imgur.com/bIXv1bk.png',
            'icon-wrapped-alt' => '+',
            'icon-unwrapped-url' => 'https://i.imgur.com/w4uTygT.png',
            'icon-unwrapped-alt' => '-',
            'icon-position' => 'right',
            'icon-height' => '32px',
            'icon-width' => '32px',
            'padding' => '10px 25px',
        ];
    }

    public function getComponentHeadStyle(): string
    {
        return '
      noinput.mj-accordion-checkbox { display: block !important; }

      @media yahoo, only screen and (min-width:0) {
        .mj-accordion-element { display: block; }
        .mj-accordion-checkbox[type="checkbox"], .mj-accordion-less { display: none !important; }
        .mj-accordion-checkbox[type="checkbox"] + * .mj-accordion-title { cursor: pointer; touch-action: manipulation; -webkit-user-select: none; -moz-user-select: none; user-select: none; }
        .mj-accordion-checkbox[type="checkbox"] + * .mj-accordion-content { overflow: hidden; display: none; }
        .mj-accordion-checkbox[type="checkbox"] + * .mj-accordion-more { display: block !important; }
        .mj-accordion-checkbox:checked + * .mj-accordion-content { display: block; }
        .mj-accordion-checkbox:checked + * .mj-accordion-more { display: none !important; }
        .mj-accordion-checkbox:checked + * .mj-accordion-less { display: block !important; }
      }

      .moz-text-html input.mj-accordion-checkbox + * .mj-accordion-title { cursor: auto; touch-action: auto; -webkit-user-select: auto; -moz-user-select: auto; user-select: auto; }
      .moz-text-html input.mj-accordion-checkbox + * .mj-accordion-content { overflow: hidden; display: block; }
      .moz-text-html input.mj-accordion-checkbox + * .mj-accordion-ico { display: none; }

      @goodbye { @gmail }
    ';
    }

    protected function getStyles(): array
    {
        return [
            'table' => [
                'width' => '100%',
                'border-collapse' => 'collapse',
                'border' => $this->getAttribute('border'),
                'border-bottom' => 'none',
                'font-family' => $this->getAttribute('font-family'),
            ],
        ];
    }

    public function render(): string
    {
        // Register head style
        $this->globalContext->addComponentHeadStyle(fn() => $this->getComponentHeadStyle());

        $childrenAttr = [];
        foreach ([
            'border', 'icon-align', 'icon-width', 'icon-height',
            'icon-position', 'icon-wrapped-url', 'icon-wrapped-alt',
            'icon-unwrapped-url', 'icon-unwrapped-alt',
        ] as $attr) {
            $val = $this->getAttribute($attr);
            if ($val !== null) {
                $childrenAttr[$attr] = $val;
            }
        }

        // Pass accordion-level font-family to children for inheritance
        $fontFamily = $this->getAttribute('font-family');
        if ($fontFamily !== null) {
            $childrenAttr['accordion-font-family'] = $fontFamily;
        }

        $childrenHtml = $this->renderChildren(attributes: $childrenAttr);

        return '<table'
            . $this->htmlAttributes([
                'cellspacing' => '0',
                'cellpadding' => '0',
                'class' => 'mj-accordion',
                'style' => 'table',
            ])
            . '><tbody>'
            . $childrenHtml
            . '</tbody></table>';
    }
}
