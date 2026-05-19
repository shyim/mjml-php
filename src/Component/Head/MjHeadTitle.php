<?php

declare(strict_types=1);

namespace Mjml\Component\Head;

use Mjml\Component\HeadComponent;

final class MjHeadTitle extends HeadComponent
{
    public static function getComponentName(): string
    {
        return 'mj-title';
    }

    public static function allowedAttributes(): array
    {
        return [];
    }

    public static function defaultAttributes(): array
    {
        return [];
    }

    public static function isEndingTag(): bool
    {
        return true;
    }

    public function handler(): void
    {
        $this->globalContext->setTitle($this->getContent());
    }
}
