<?php

declare(strict_types=1);

namespace Mjml\Validation\Rule;

use Mjml\Component\ComponentRegistry;
use Mjml\Parser\Node;
use Mjml\Validation\ValidationError;

/**
 * Checks that children of each component are allowed according to the dependency map.
 */
final class ValidChildrenRule implements ValidationRuleInterface
{
    /** @var array<string, list<string>>|null Cached reverse lookup: childTag => valid parent tags */
    private ?array $parentCache = null;
    private ?ComponentRegistry $cachedRegistry = null;

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
     * Uses a lazily-built reverse-lookup cache so the full dependency
     * map is only scanned once per registry instance.
     *
     * @return list<string>
     */
    private function findValidParents(string $childTag, ComponentRegistry $registry): array
    {
        // Build the reverse-lookup cache once per registry instance
        if ($this->parentCache === null || $this->cachedRegistry !== $registry) {
            $this->parentCache = [];
            $this->cachedRegistry = $registry;

            foreach ($registry->getTagNames() as $tagName) {
                $allowed = $registry->getAllowedChildren($tagName);

                if ($allowed !== null) {
                    foreach ($allowed as $allowedChild) {
                        $this->parentCache[$allowedChild][] = $tagName;
                    }
                }
            }

            // Also check non-component parents like "mjml"
            $mjmlChildren = $registry->getAllowedChildren('mjml');
            if ($mjmlChildren !== null) {
                foreach ($mjmlChildren as $allowedChild) {
                    $this->parentCache[$allowedChild][] = 'mjml';
                }
            }
        }

        return $this->parentCache[$childTag] ?? [];
    }
}
