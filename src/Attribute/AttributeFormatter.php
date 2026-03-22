<?php

declare(strict_types=1);

namespace Shyim\Mjml\Attribute;

final class AttributeFormatter
{
    /**
     * Format and validate attributes against their type definitions.
     *
     * @param array<string, string> $attributes Raw attribute values
     * @param array<string, string> $allowedAttributes Attribute name => type string
     * @return array<string, string> Formatted attributes
     */
    public static function format(array $attributes, array $allowedAttributes): array
    {
        $result = [];

        foreach ($attributes as $name => $value) {
            if (!isset($allowedAttributes[$name])) {
                // Pass through attributes not in allowed list (e.g., mj-class, css-class)
                $result[$name] = $value;
                continue;
            }

            $result[$name] = self::formatValue($value, $allowedAttributes[$name]);
        }

        return $result;
    }

    private static function formatValue(string $value, string $typeString): string
    {
        // For now, pass values through - type validation happens in the validator
        // The JS version also mostly passes values through in formatAttributes
        return $value;
    }
}
