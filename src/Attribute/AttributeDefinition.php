<?php

declare(strict_types=1);

namespace Shyim\Mjml\Attribute;

final readonly class AttributeDefinition
{
    /**
     * @param list<string>|null $enumValues Allowed values for Enum type
     * @param string|null $unitPattern Unit specification like 'px', 'px,%', 'px,em'
     */
    public function __construct(
        public AttributeType $type,
        public ?string $default = null,
        public ?array $enumValues = null,
        public ?string $unitPattern = null,
    ) {}

    /**
     * Parse a JS-style type string like "unit(px,%)", "enum(left,center,right)", "color", "string".
     */
    public static function fromTypeString(string $typeString, ?string $default = null): self
    {
        // Handle unit types: unit(px), unit(px,%), unit(px,%){1,4}, unitWithNegative(px,em)
        if (preg_match('/^(?:unitWithNegative|unit)\(([^)]+)\)/', $typeString, $matches)) {
            return new self(
                type: AttributeType::Unit,
                default: $default,
                unitPattern: $matches[1],
            );
        }

        // Handle enum types: enum(left,center,right)
        if (preg_match('/^enum\(([^)]+)\)/', $typeString, $matches)) {
            return new self(
                type: AttributeType::Enum,
                default: $default,
                enumValues: array_map(trim(...), explode(',', $matches[1])),
            );
        }

        // Simple types
        $type = match ($typeString) {
            'color' => AttributeType::Color,
            'boolean' => AttributeType::Boolean,
            'integer' => AttributeType::Integer,
            'string' => AttributeType::String,
            default => AttributeType::String,
        };

        return new self(type: $type, default: $default);
    }
}
