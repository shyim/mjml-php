<?php

declare(strict_types=1);

namespace Mjml\Renderer\PostProcessor;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

final class CssInliner
{
    /**
     * Inline CSS styles into HTML elements.
     *
     * Only inlines the explicitly passed CSS rules (like the JS "juice" library
     * with applyStyleTags: false). Does NOT inline styles from <style> tags.
     *
     * @param list<string> $inlineStyles Extra CSS rules to inline
     */
    public static function inline(string $html, array $inlineStyles): string
    {
        if ($inlineStyles === []) {
            return $html;
        }

        $extraCss = implode('', $inlineStyles);

        // Temporarily remove <style> tags so the inliner doesn't process them
        $styleTags = [];
        $htmlWithoutStyles = preg_replace_callback(
            '/<style[^>]*>.*?<\/style>/s',
            static function (array $match) use (&$styleTags): string {
                $placeholder = '<!--STYLE_PLACEHOLDER_' . \count($styleTags) . '-->';
                $styleTags[] = $match[0];

                return $placeholder;
            },
            $html,
        ) ?? $html;

        $inliner = new CssToInlineStyles();
        $result = $inliner->convert($htmlWithoutStyles, $extraCss);

        // Restore <style> tags
        foreach ($styleTags as $i => $tag) {
            $result = str_replace('<!--STYLE_PLACEHOLDER_' . $i . '-->', $tag, $result);
        }

        return $result;
    }
}
