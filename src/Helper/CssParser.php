<?php

declare(strict_types=1);

namespace Shyim\Mjml\Helper;

final class CssParser
{
    /**
     * Parse CSS shorthand value (padding, margin) into directional values.
     *
     * Handles 1, 2, 3, or 4 values:
     *   "10px"              → top=10, right=10, bottom=10, left=10
     *   "10px 20px"         → top=10, right=20, bottom=10, left=20
     *   "10px 20px 30px"    → top=10, right=20, bottom=30, left=20
     *   "10px 20px 30px 40px" → top=10, right=20, bottom=30, left=40
     */
    public static function parseShorthand(string $value, string $direction): int
    {
        $values = preg_split('/\s+/', trim($value));

        if ($values === false || $values === []) {
            return 0;
        }

        $map = match (\count($values)) {
            1 => [
                'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0,
            ],
            2 => [
                'top' => 0, 'right' => 1, 'bottom' => 0, 'left' => 1,
            ],
            3 => [
                'top' => 0, 'right' => 1, 'bottom' => 2, 'left' => 1,
            ],
            default => [
                'top' => 0, 'right' => 1, 'bottom' => 2, 'left' => 3,
            ],
        };

        $index = $map[$direction] ?? 0;

        return (int) $values[$index];
    }

    /**
     * Parse a CSS border shorthand like "1px solid #000" and extract the width.
     */
    public static function parseBorderWidth(string $border): int
    {
        $border = trim($border);

        if ($border === '' || $border === '0') {
            return 0;
        }

        if (preg_match('/^(\d+)\s*px/', $border, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
