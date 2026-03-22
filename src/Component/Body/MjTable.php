<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;
use Shyim\Mjml\Helper\WidthParser;

final class MjTable extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-table';
    }

    public static function isEndingTag(): bool
    {
        return true;
    }

    public static function allowedAttributes(): array
    {
        return [
            'align' => 'enum(left,right,center)',
            'border' => 'string',
            'cellpadding' => 'integer',
            'cellspacing' => 'integer',
            'container-background-color' => 'color',
            'color' => 'color',
            'font-family' => 'string',
            'font-size' => 'unit(px)',
            'font-weight' => 'string',
            'line-height' => 'unit(px,%,)',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'padding' => 'unit(px,%){1,4}',
            'role' => 'enum(none,presentation)',
            'table-layout' => 'enum(auto,fixed,initial,inherit)',
            'vertical-align' => 'enum(top,bottom,middle)',
            'width' => 'unit(px,%,auto)',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'align' => 'left',
            'border' => 'none',
            'cellpadding' => '0',
            'cellspacing' => '0',
            'color' => '#000000',
            'font-family' => 'Ubuntu, Helvetica, Arial, sans-serif',
            'font-size' => '13px',
            'line-height' => '22px',
            'padding' => '10px 25px',
            'table-layout' => 'auto',
            'width' => '100%',
        ];
    }

    protected function getStyles(): array
    {
        $hasCellspacing = $this->hasCellspacing();

        $table = [
            'color' => $this->getAttribute('color'),
            'font-family' => $this->getAttribute('font-family'),
            'font-size' => $this->getAttribute('font-size'),
            'line-height' => $this->getAttribute('line-height'),
            'table-layout' => $this->getAttribute('table-layout'),
            'width' => $this->getAttribute('width'),
            'border' => $this->getAttribute('border'),
        ];

        if ($hasCellspacing) {
            $table['border-collapse'] = 'separate';
        }

        return [
            'table' => $table,
        ];
    }

    private function getTableWidth(): string
    {
        $width = $this->getAttribute('width') ?? '100%';

        if ($width === 'auto') {
            return $width;
        }

        $parsed = WidthParser::parse($width);

        return $parsed['unit'] === '%' ? $width : (string) (int) $parsed['value'];
    }

    private function hasCellspacing(): bool
    {
        $cellspacing = $this->getAttribute('cellspacing');

        if ($cellspacing === null) {
            return false;
        }

        $numericValue = (float) preg_replace('/[^\d.]/', '', $cellspacing);

        return !is_nan($numericValue) && $numericValue > 0;
    }

    public function render(): string
    {
        return '<table' . $this->htmlAttributes([
            'cellpadding' => $this->getAttribute('cellpadding'),
            'cellspacing' => $this->getAttribute('cellspacing'),
            'role' => $this->getAttribute('role'),
            'width' => $this->getTableWidth(),
            'border' => '0',
            'style' => 'table',
        ]) . '>' . $this->getContent() . '</table>';
    }
}
