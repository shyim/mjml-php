<?php

declare(strict_types=1);

namespace Mjml\Component\Head;

use Mjml\Component\HeadComponent;

final class MjHeadHtmlAttributes extends HeadComponent
{
    public static function getComponentName(): string
    {
        return 'mj-html-attributes';
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
        foreach ($this->node->children as $selector) {
            if ($selector->tagName !== 'mj-selector') {
                continue;
            }

            $path = $selector->attributes['path'] ?? null;

            if ($path === null) {
                continue;
            }

            $custom = [];

            foreach ($selector->children as $child) {
                if ($child->tagName !== 'mj-html-attribute') {
                    continue;
                }

                $name = $child->attributes['name'] ?? null;

                if ($name === null) {
                    continue;
                }

                $custom[$name] = trim($child->content);
            }

            $this->globalContext->mergeHtmlAttributes($path, $custom);
        }
    }
}
