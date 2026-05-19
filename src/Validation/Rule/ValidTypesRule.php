<?php

declare(strict_types=1);

namespace Mjml\Validation\Rule;

use Mjml\Component\ComponentRegistry;
use Mjml\Parser\Node;
use Mjml\Validation\ValidationError;

/**
 * Validates that attribute values match their declared types (color, unit, enum, etc.).
 */
final class ValidTypesRule implements ValidationRuleInterface
{
    /** @var array<string, string> Cache of compiled unit-validation regex patterns keyed by typeSpec */
    private static array $unitPatternCache = [];

    public function validate(Node $node, ComponentRegistry $registry): ?array
    {
        $componentClass = $registry->get($node->tagName);

        if ($componentClass === null) {
            return null;
        }

        $allowedAttributes = $componentClass::allowedAttributes();
        $errors = [];

        foreach ($node->attributes as $attr => $value) {
            if (!isset($allowedAttributes[$attr])) {
                continue;
            }

            $typeSpec = $allowedAttributes[$attr];
            $errorMessage = $this->validateType($value, $typeSpec);

            if ($errorMessage !== null) {
                $errors[] = new ValidationError(
                    message: "Attribute {$attr} {$errorMessage}",
                    tagName: $node->tagName,
                    line: $node->line,
                );
            }
        }

        return $errors !== [] ? $errors : null;
    }

    private function validateType(string $value, string $typeSpec): ?string
    {
        // Determine the base type
        if (str_starts_with($typeSpec, 'enum(')) {
            return $this->validateEnum($value, $typeSpec);
        }

        if (str_starts_with($typeSpec, 'unit(') || str_starts_with($typeSpec, 'unitWithNegative(')) {
            return $this->validateUnit($value, $typeSpec);
        }

        return match ($typeSpec) {
            'color' => $this->validateColor($value),
            'boolean' => $this->validateBoolean($value),
            'integer' => $this->validateInteger($value),
            'string' => null, // any string is valid
            default => null,  // unknown types pass validation
        };
    }

    private function validateColor(string $value): ?string
    {
        // Allow transparent
        if ($value === 'transparent' || $value === 'none') {
            return null;
        }

        // Hex colors: #rgb or #rrggbb
        if (preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $value)) {
            return null;
        }

        // rgb() and rgba()
        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*\d?(\.\d{1,3})?\s*)?\)$/i', $value)) {
            return null;
        }

        // Named CSS colors (non-exhaustive but covers common ones)
        if (preg_match('/^[a-zA-Z]+$/', $value) && $this->isNamedColor($value)) {
            return null;
        }

        return "has invalid value: {$value} for type color";
    }

    private function validateBoolean(string $value): ?string
    {
        if ($value === 'true' || $value === 'false') {
            return null;
        }

        return "has invalid value: {$value} for type boolean";
    }

    private function validateInteger(string $value): ?string
    {
        if (preg_match('/^\d+$/', $value)) {
            return null;
        }

        return "has invalid value: {$value} for type integer";
    }

    private function validateEnum(string $value, string $typeSpec): ?string
    {
        // Parse "enum(val1,val2,val3)"
        if (!preg_match('/^enum\(([^)]+)\)$/', $typeSpec, $matches)) {
            return null;
        }

        $allowed = array_map('trim', explode(',', $matches[1]));

        if (\in_array($value, $allowed, true)) {
            return null;
        }

        return "has invalid value: {$value} for type Enum, only accepts " . implode(', ', $allowed);
    }

    private function validateUnit(string $value, string $typeSpec): ?string
    {
        // Parse units from "unit(px,%)" or "unitWithNegative(px,%)"
        if (!preg_match('/\(([^)]+)\)/', $typeSpec, $unitMatch)) {
            return null;
        }

        $units = array_map('trim', explode(',', $unitMatch[1]));

        // Parse repeat count from "{1,4}" if present
        $args = ['1'];
        if (preg_match('/\{([^}]+)\}/', $typeSpec, $argsMatch)) {
            $args = array_map('trim', explode(',', $argsMatch[1]));
        }

        $pattern = self::$unitPatternCache[$typeSpec] ?? null;

        if ($pattern === null) {
            $allowNeg = str_starts_with($typeSpec, 'unitWithNegative');
            $allowAuto = \in_array('auto', $units, true);
            $filteredUnits = array_filter($units, static fn(string $u) => $u !== 'auto');

            $negPart = $allowNeg ? '-?' : '';
            $unitsPart = implode('|', array_map(static fn(string $u) => preg_quote($u, '/'), $filteredUnits));
            $autoPart = $allowAuto ? '|auto' : '';

            $repeatPart = implode(',', $args);
            $pattern = '/^(((' . $negPart . '[\d,.]+)(' . $unitsPart . ')|0' . $autoPart . ')( )?){' . $repeatPart . '}$/';
            self::$unitPatternCache[$typeSpec] = $pattern;
        }

        if (preg_match($pattern, $value)) {
            return null;
        }

        return "has invalid value: {$value} for type Unit, only accepts (" . implode(', ', $units) . ') units and ' . implode(' to ', $args) . ' value(s)';
    }

    private function isNamedColor(string $value): bool
    {
        static $colors = [
            'aliceblue', 'antiquewhite', 'aqua', 'aquamarine', 'azure',
            'beige', 'bisque', 'black', 'blanchedalmond', 'blue', 'blueviolet', 'brown', 'burlywood',
            'cadetblue', 'chartreuse', 'chocolate', 'coral', 'cornflowerblue', 'cornsilk', 'crimson', 'cyan',
            'darkblue', 'darkcyan', 'darkgoldenrod', 'darkgray', 'darkgreen', 'darkgrey', 'darkkhaki',
            'darkmagenta', 'darkolivegreen', 'darkorange', 'darkorchid', 'darkred', 'darksalmon',
            'darkseagreen', 'darkslateblue', 'darkslategray', 'darkslategrey', 'darkturquoise', 'darkviolet',
            'deeppink', 'deepskyblue', 'dimgray', 'dimgrey', 'dodgerblue',
            'firebrick', 'floralwhite', 'forestgreen', 'fuchsia',
            'gainsboro', 'ghostwhite', 'gold', 'goldenrod', 'gray', 'green', 'greenyellow', 'grey',
            'honeydew', 'hotpink',
            'indianred', 'indigo', 'ivory',
            'khaki',
            'lavender', 'lavenderblush', 'lawngreen', 'lemonchiffon', 'lightblue', 'lightcoral', 'lightcyan',
            'lightgoldenrodyellow', 'lightgray', 'lightgreen', 'lightgrey', 'lightpink', 'lightsalmon',
            'lightseagreen', 'lightskyblue', 'lightslategray', 'lightslategrey', 'lightsteelblue', 'lightyellow',
            'lime', 'limegreen', 'linen',
            'magenta', 'maroon', 'mediumaquamarine', 'mediumblue', 'mediumorchid', 'mediumpurple',
            'mediumseagreen', 'mediumslateblue', 'mediumspringgreen', 'mediumturquoise', 'mediumvioletred',
            'midnightblue', 'mintcream', 'mistyrose', 'moccasin',
            'navajowhite', 'navy',
            'oldlace', 'olive', 'olivedrab', 'orange', 'orangered', 'orchid',
            'palegoldenrod', 'palegreen', 'paleturquoise', 'palevioletred', 'papayawhip', 'peachpuff',
            'peru', 'pink', 'plum', 'powderblue', 'purple',
            'rebeccapurple', 'red', 'rosybrown', 'royalblue',
            'saddlebrown', 'salmon', 'sandybrown', 'seagreen', 'seashell', 'sienna', 'silver', 'skyblue',
            'slateblue', 'slategray', 'slategrey', 'snow', 'springgreen', 'steelblue',
            'tan', 'teal', 'thistle', 'tomato', 'turquoise',
            'violet',
            'wheat', 'white', 'whitesmoke',
            'yellow', 'yellowgreen',
        ];

        return \in_array(strtolower($value), $colors, true);
    }
}
