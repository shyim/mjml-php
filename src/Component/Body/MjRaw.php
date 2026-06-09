<?php

declare(strict_types=1);

namespace MjmlPHP\Component\Body;

use MjmlPHP\Component\BodyComponent;

final class MjRaw extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-raw';
    }

    public static function isEndingTag(): bool
    {
        return true;
    }

    public static function isRawElement(): bool
    {
        return true;
    }

    public static function allowedAttributes(): array
    {
        return [
            'position' => 'enum(file-start)',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [];
    }

    public function render(): string
    {
        if ($this->getAttribute('position') === 'file-start') {
            return '';
        }

        return $this->getContent();
    }
}
