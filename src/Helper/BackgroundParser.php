<?php

declare(strict_types=1);

namespace Shyim\Mjml\Helper;

final class BackgroundParser
{
    /**
     * Parse CSS background-position into x and y components.
     *
     * Handles 1-value, 2-value, and 3+-value syntaxes, including keyword swapping
     * (e.g., "bottom left" → x=left, y=bottom).
     *
     * @return array{x: string, y: string}
     */
    public static function parsePosition(string $position): array
    {
        $parts = preg_split('/\s+/', trim($position));

        if ($parts === false || $parts === []) {
            return ['x' => 'center', 'y' => 'top'];
        }

        if (\count($parts) === 1) {
            $val = $parts[0];

            if (\in_array($val, ['top', 'bottom'], true)) {
                return ['x' => 'center', 'y' => $val];
            }

            return ['x' => $val, 'y' => 'center'];
        }

        if (\count($parts) === 2) {
            $val1 = $parts[0];
            $val2 = $parts[1];

            if (
                \in_array($val1, ['top', 'bottom'], true)
                || ($val1 === 'center' && \in_array($val2, ['left', 'right'], true))
            ) {
                return ['x' => $val2, 'y' => $val1];
            }

            return ['x' => $val1, 'y' => $val2];
        }

        // More than 2 values is not supported, treat as default
        return ['x' => 'center', 'y' => 'top'];
    }

    /**
     * Convert a position keyword to percentage.
     */
    public static function positionToPercentage(string $value, bool $isX): string
    {
        return match ($value) {
            'left', 'top' => '0%',
            'center' => '50%',
            'right', 'bottom' => '100%',
            default => self::isPercentage($value) ? $value : ($isX ? '50%' : '0%'),
        };
    }

    /**
     * Check if a string is a percentage value like "50%".
     */
    public static function isPercentage(string $value): bool
    {
        return (bool) preg_match('/^\d+(\.\d+)?%$/', $value);
    }

    /**
     * Calculate VML origin and position values for Outlook background images.
     *
     * @return array{originX: string, posX: string, originY: string, posY: string}
     */
    public static function calculateVmlPositions(string $bgPosX, string $bgPosY, bool $bgRepeat): array
    {
        $results = [];

        foreach (['x', 'y'] as $coordinate) {
            $isX = $coordinate === 'x';
            $pos = $isX ? $bgPosX : $bgPosY;

            if (self::isPercentage($pos)) {
                preg_match('/^(\d+(\.\d+)?)%$/', $pos, $matches);
                $decimal = (int) $matches[1] / 100;

                if ($bgRepeat) {
                    $origin = (string) $decimal;
                    $posVal = (string) $decimal;
                } else {
                    $val = (-50 + $decimal * 100) / 100;
                    $origin = (string) $val;
                    $posVal = (string) $val;
                }
            } elseif ($bgRepeat) {
                $origin = $isX ? '0.5' : '0';
                $posVal = $isX ? '0.5' : '0';
            } else {
                $origin = $isX ? '0' : '-0.5';
                $posVal = $isX ? '0' : '-0.5';
            }

            $results[$coordinate] = ['origin' => $origin, 'pos' => $posVal];
        }

        return [
            'originX' => $results['x']['origin'],
            'posX' => $results['x']['pos'],
            'originY' => $results['y']['origin'],
            'posY' => $results['y']['pos'],
        ];
    }
}
