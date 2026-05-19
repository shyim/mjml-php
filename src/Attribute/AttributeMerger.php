<?php

declare(strict_types=1);

namespace Mjml\Attribute;

/**
 * Merges explicit, default, global, and mj-class attribute values in priority order.
 *
 * The merge order (lowest to highest priority):
 *   1. Component defaults        (defaultAttributes)
 *   2. Global defaults for all   (mj-attributes → mj-all)
 *   3. Component-specific defaults (mj-attributes → mj-text, etc.)
 *   4. mj-class attributes       (classes → classesDefault)
 *   5. Explicit node attributes  (from markup)
 */
final class AttributeMerger
{
    /**
     * Merge and format attributes against their type definitions.
     *
     * @param array<string, string|null> $attributes Raw attribute values
     * @param array<string, string> $allowedAttributes Attribute name => type string
     * @return array<string, string|null> Merged attributes
     */
    public static function merge(array $attributes, array $allowedAttributes): array
    {
        $result = [];

        foreach ($attributes as $name => $value) {
            if (!isset($allowedAttributes[$name])) {
                // Pass through attributes not in allowed list (e.g., mj-class, css-class)
                $result[$name] = $value;
                continue;
            }

            $result[$name] = $value;
        }

        return $result;
    }
}
