<?php

declare(strict_types=1);

namespace Shyim\Mjml\Helper;

final class WidthParser
{
    /**
     * Parse a CSS width value like "300px" or "50%" into its numeric value and unit.
     *
     * @param bool $parseFloatToInt When true (default), px values are truncated to integers
     *                              (matching JS parseInt behavior). Percent values always use float.
     * @return array{value: float, unit: string}
     */
    public static function parse(string $width, bool $parseFloatToInt = true): array
    {
        if (preg_match('/^(\d+(?:\.\d+)?)(px|%|em|rem)?$/', trim($width), $matches)) {
            $value = (float) $matches[1];
            $unit = $matches[2] ?? 'px';

            // JS uses parseInt for px and default; parseFloat for %
            if ($parseFloatToInt && $unit !== '%') {
                $value = (float) (int) $value;
            }

            return [
                'value' => $value,
                'unit' => $unit,
            ];
        }

        return [
            'value' => 0.0,
            'unit' => 'px',
        ];
    }
}
