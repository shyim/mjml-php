<?php

declare(strict_types=1);

namespace Mjml\Component\Head;

use Mjml\Component\HeadComponent;

final class MjHeadAttributes extends HeadComponent
{
    public static function getComponentName(): string
    {
        return 'mj-attributes';
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
        foreach ($this->node->children as $child) {
            $tagName = $child->tagName;
            $attributes = $child->attributes;

            if ($tagName === 'mj-class') {
                $className = $attributes['name'] ?? null;

                if ($className === null) {
                    continue;
                }

                // Store class attributes (without the 'name' key)
                $classAttrs = $attributes;
                unset($classAttrs['name']);

                $this->globalContext->mergeClasses($className, $classAttrs);

                // Process children of mj-class for classesDefault
                // Each child's tagName maps to its attributes
                $childDefaults = [];
                foreach ($child->children as $classChild) {
                    $childDefaults[$classChild->tagName] = $classChild->attributes;
                }

                $this->globalContext->mergeClassesDefault($className, $childDefaults);
            } else {
                // For mj-all, mj-text, mj-section, etc. — store as defaultAttributes
                $this->globalContext->mergeDefaultAttributes($tagName, $attributes);
            }
        }
    }
}
