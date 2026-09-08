<?php

declare(strict_types=1);

namespace MjmlPHP\Renderer\PostProcessor;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

final class CssInliner
{
    /**
     * Matches a downlevel-*hidden* Outlook conditional comment (`<!--[if …]>…<![endif]-->`).
     *
     * The negative lookahead excludes the downlevel-*revealed* form
     * (`<!--[if …]><!-->…<!--<![endif]-->`), whose body is live DOM for every
     * non-Word parser and must therefore still be inlined into.
     */
    private const CONDITIONAL_COMMENT = '/<!--\[if\s[^\]]*\]>(?!<!-->)[\s\S]*?<!\[endif\]-->/';

    private const STYLE_TAG = '/<style[^>]*>.*?<\/style>/s';

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

        /** @var array<string, string> $placeholders */
        $placeholders = [];

        // Protect downlevel-hidden conditional comments *before* the <style> pass.
        // Their bodies are inert comment text to every non-Word parser, so juice
        // never inlines into them either — but stashing the <style> they contain
        // would replace it with a placeholder whose own "-->" closes the enclosing
        // conditional early, and the DOM round trip below then drops the orphaned
        // "<![endif]-->". Word is the only engine that scans for that token, so the
        // result renders everywhere except Outlook for Windows.
        $protected = self::stash($html, self::CONDITIONAL_COMMENT, 'COND', $placeholders);

        // Temporarily remove the remaining <style> tags so the inliner doesn't process them
        $protected = self::stash($protected, self::STYLE_TAG, 'STYLE', $placeholders);

        $inliner = new CssToInlineStyles();
        $result = $inliner->convert($protected, $extraCss);

        // Restore. No stashed payload contains another payload's token, so the
        // order the placeholders are replaced in does not matter.
        foreach ($placeholders as $token => $original) {
            $result = str_replace($token, $original, $result);
        }

        return $result;
    }

    /**
     * @param array<string, string> $placeholders
     */
    private static function stash(string $html, string $pattern, string $kind, array &$placeholders): string
    {
        return preg_replace_callback(
            $pattern,
            static function (array $match) use ($kind, &$placeholders): string {
                $token = '<!--MJMLPHP_' . $kind . '_' . \count($placeholders) . '-->';
                $placeholders[$token] = $match[0];

                return $token;
            },
            $html,
        ) ?? $html;
    }
}
