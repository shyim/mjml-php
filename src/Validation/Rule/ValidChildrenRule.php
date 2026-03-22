<?php

declare(strict_types=1);

namespace Shyim\Mjml\Validation\Rule;

use Shyim\Mjml\Component\ComponentRegistry;
use Shyim\Mjml\Parser\Node;
use Shyim\Mjml\Validation\ValidationError;

/**
 * Checks that children of each component are allowed according to the dependency map.
 */
final class ValidChildrenRule implements ValidationRuleInterface
{
    public function validate(Node $node, ComponentRegistry $registry): ?array
    {
        $componentClass = $registry->get($node->tagName);

        if ($componentClass === null || $node->children === []) {
            return null;
        }

        $allowedChildren = $registry->getAllowedChildren($node->tagName);

        // If no dependency rules defined for this parent, skip validation
        if ($allowedChildren === null) {
            return null;
        }

        $errors = [];

        foreach ($node->children as $child) {
            $childComponent = $registry->get($child->tagName);

            // Skip validation for unregistered child components (validTag handles that)
            if ($childComponent === null) {
                continue;
            }

            if (!\in_array($child->tagName, $allowedChildren, true)) {
                // Find which parents actually allow this child
                $validParents = $this->findValidParents($child->tagName, $registry);

                $parentList = $validParents !== [] ? implode(', ', $validParents) : 'nowhere';

                $errors[] = new ValidationError(
                    message: "{$child->tagName} cannot be used inside {$node->tagName}, only inside: {$parentList}",
                    tagName: $child->tagName,
                    line: $child->line,
                );
            }
        }

        return $errors !== [] ? $errors : null;
    }

    /**
     * Find all parent tags that allow the given child tag.
     *
     * @return list<string>
     */
    private function findValidParents(string $childTag, ComponentRegistry $registry): array
    {
        $validParents = [];

        foreach ($registry->getTagNames() as $tagName) {
            $allowed = $registry->getAllowedChildren($tagName);

            if ($allowed !== null && \in_array($childTag, $allowed, true)) {
                $validParents[] = $tagName;
            }
        }

        // Also check non-component parents like "mjml"
        $mjmlChildren = $registry->getAllowedChildren('mjml');
        if ($mjmlChildren !== null && \in_array($childTag, $mjmlChildren, true)) {
            $validParents[] = 'mjml';
        }

        return $validParents;
    }
}
