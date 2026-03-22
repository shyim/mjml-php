<?php

declare(strict_types=1);

namespace Shyim\Mjml\Validation\Rule;

use Shyim\Mjml\Component\ComponentRegistry;
use Shyim\Mjml\Parser\Node;
use Shyim\Mjml\Validation\ValidationError;

/**
 * Checks that all attributes on a component are in its allowedAttributes().
 */
final class ValidAttributesRule implements ValidationRuleInterface
{
    /** Attributes that are always allowed on any component */
    private const WHITELIST = ['mj-class', 'css-class'];

    public function validate(Node $node, ComponentRegistry $registry): ?ValidationError
    {
        $componentClass = $registry->get($node->tagName);

        if ($componentClass === null) {
            return null;
        }

        $allowedAttributes = array_merge(
            array_keys($componentClass::allowedAttributes()),
            self::WHITELIST,
        );

        $unknownAttributes = array_diff(
            array_keys($node->attributes),
            $allowedAttributes,
        );

        if ($unknownAttributes === []) {
            return null;
        }

        $unknownList = array_values($unknownAttributes);
        $attribute = \count($unknownList) > 1 ? 'Attributes' : 'Attribute';
        $illegal = \count($unknownList) > 1 ? 'are illegal' : 'is illegal';

        return new ValidationError(
            message: "{$attribute} " . implode(', ', $unknownList) . " {$illegal}",
            tagName: $node->tagName,
            line: $node->line,
        );
    }
}
