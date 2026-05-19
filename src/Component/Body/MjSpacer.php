<?php

declare(strict_types=1);

namespace Mjml\Component\Body;

use Mjml\Component\BodyComponent;

final class MjSpacer extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-spacer';
    }

    public static function allowedAttributes(): array
    {
        return [
            'border' => 'string',
            'border-bottom' => 'string',
            'border-left' => 'string',
            'border-right' => 'string',
            'border-top' => 'string',
            'container-background-color' => 'color',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'padding' => 'unit(px,%){1,4}',
            'height' => 'unit(px,%)',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'height' => '20px',
        ];
    }

    protected function getStyles(): array
    {
        return [
            'div' => [
                'height' => $this->getAttribute('height'),
                'line-height' => $this->getAttribute('height'),
            ],
        ];
    }

    public function render(): string
    {
        return '<div' . $this->htmlAttributes(['style' => 'div']) . '>&#8202;</div>';
    }
}
