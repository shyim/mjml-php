<?php

declare(strict_types=1);

namespace Mjml\Component\Head;

use Mjml\Component\HeadComponent;

final class MjHead extends HeadComponent
{
    public static function getComponentName(): string
    {
        return 'mj-head';
    }

    public static function allowedAttributes(): array
    {
        return [];
    }

    public static function defaultAttributes(): array
    {
        return [];
    }

    public function handler(): void
    {
        $this->handlerChildren();
    }
}
