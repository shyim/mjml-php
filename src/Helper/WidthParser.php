<?php

declare(strict_types=1);

namespace Shyim\Mjml\Helper;

final class WidthParser
{
    /**
     * Parse a CSS width value like "300px" or "50%" into its numeric value and unit.
     *
     * @return array{value: float, unit: string}
     */
    public static function parse(string $width): array
    {
        if (preg_match('/^(\d+(?:\.\d+)?)(px|%|em|rem)?$/', trim($width), $matches)) {
            return [
                'value' => (float) $matches[1],
                'unit' => $matches[2] ?? 'px',
            ];
        }

        return [
            'value' => 0.0,
            'unit' => 'px',
        ];
    }
}
