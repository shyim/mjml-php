<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Head;

use Shyim\Mjml\Component\HeadComponent;

final class MjHeadStyle extends HeadComponent
{
    public static function getComponentName(): string
    {
        return 'mj-style';
    }

    public static function allowedAttributes(): array
    {
        return [
            'inline' => 'string',
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
        $content = $this->getContent();

        if ($this->getAttribute('inline') === 'inline') {
            $this->globalContext->inlineStyles[] = $content;
        } else {
            $this->globalContext->styles[] = $content;
        }
    }
}
