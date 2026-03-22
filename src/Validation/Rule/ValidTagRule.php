<?php

declare(strict_types=1);

namespace Shyim\Mjml\Validation\Rule;

use Shyim\Mjml\Component\ComponentRegistry;
use Shyim\Mjml\Parser\Node;
use Shyim\Mjml\Validation\ValidationError;

/**
 * Checks that every tag is either registered in the registry or is a known meta-tag.
 */
final class ValidTagRule implements ValidationRuleInterface
{
    /** Tags that have no associated component but are allowed */
    private const COMPONENT_LESS_TAGS = [
        'mj-all',
        'mj-class',
        'mj-selector',
        'mj-html-attribute',
    ];

    public function validate(Node $node, ComponentRegistry $registry): ?ValidationError
    {
        if (\in_array($node->tagName, self::COMPONENT_LESS_TAGS, true)) {
            return null;
        }

        if (!$registry->has($node->tagName)) {
            return new ValidationError(
                message: "Element {$node->tagName} doesn't exist or is not registered",
                tagName: $node->tagName,
                line: $node->line,
            );
        }

        return null;
    }
}
