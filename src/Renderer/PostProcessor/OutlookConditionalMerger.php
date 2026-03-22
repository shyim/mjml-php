<?php

declare(strict_types=1);

namespace Shyim\Mjml\Renderer\PostProcessor;

final class OutlookConditionalMerger
{
    /**
     * Merge adjacent Outlook conditional comments that are separated only by whitespace.
     *
     * Transforms:
     *   <!--[if mso | IE]>...<![endif]-->  <!--[if mso | IE]>...<![endif]-->
     * Into:
     *   <!--[if mso | IE]>......<![endif]-->
     *
     * Also minifies whitespace within conditional blocks (removes spaces between tags).
     */
    public static function merge(string $html): string
    {
        // Step 1: Minify whitespace within conditional blocks (remove spaces between tags)
        $html = preg_replace_callback(
            '/(<!--\[if\s[^\]]+]>)([\s\S]*?)(<!\[endif]-->)/m',
            static function (array $matches): string {
                $prefix = $matches[1];
                $content = $matches[2];
                $suffix = $matches[3];

                // Remove whitespace between tags
                $content = preg_replace('/(^|>)\s+(<|$)/m', '$1$2', $content) ?? $content;
                // Collapse multiple spaces into one
                $content = preg_replace('/\s{2,}/', ' ', $content) ?? $content;

                return $prefix . $content . $suffix;
            },
            $html,
        ) ?? $html;

        // Step 2: Merge adjacent conditional blocks separated only by whitespace
        $html = preg_replace(
            '/<!\[endif]-->\s*?<!--\[if mso \| IE]>/m',
            '',
            $html,
        ) ?? $html;

        return $html;
    }
}
