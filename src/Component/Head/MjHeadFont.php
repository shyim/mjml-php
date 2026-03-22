<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Head;

use Shyim\Mjml\Component\HeadComponent;

final class MjHeadFont extends HeadComponent
{
    public static function getComponentName(): string
    {
        return 'mj-font';
    }

    public static function allowedAttributes(): array
    {
        return [
            'name' => 'string',
            'href' => 'string',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [];
    }

    public function handler(): void
    {
        $name = $this->getAttribute('name');
        $href = $this->getAttribute('href');

        if ($name !== null && $href !== null) {
            $this->globalContext->fonts[$name] = $href;
        }
    }
}
