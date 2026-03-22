<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Head;

use Shyim\Mjml\Component\HeadComponent;

final class MjHeadBreakpoint extends HeadComponent
{
    public static function getComponentName(): string
    {
        return 'mj-breakpoint';
    }

    public static function allowedAttributes(): array
    {
        return [
            'width' => 'unit(px)',
        ];
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
        $width = $this->getAttribute('width');

        if ($width !== null) {
            $this->globalContext->breakpoint = $width;
        }
    }
}
