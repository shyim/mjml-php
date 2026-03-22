<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Head;

use Shyim\Mjml\Component\HeadComponent;

final class MjHeadPreview extends HeadComponent
{
    public static function getComponentName(): string
    {
        return 'mj-preview';
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
        $this->globalContext->preview = $this->getContent();
    }
}
