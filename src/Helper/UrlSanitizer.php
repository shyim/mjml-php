<?php

declare(strict_types=1);

namespace Mjml\Helper;

/**
 * Defensive URL-scheme allowlist for attributes that end up in browser-rendered
 * email HTML (href, src, background, etc.).
 *
 * MJML input is conventionally treated as trusted template-author input, but
 * many integrations interpolate user-supplied data into MJML markup. This
 * helper neutralizes the most common drive-by XSS sinks (javascript:, vbscript:,
 * the dangerous form of data: URIs) without changing the appearance of any
 * legitimate http(s)/mailto/tel/anchor links.
 */
final class UrlSanitizer
{
    /** @var list<string> Lower-cased URL schemes allowed in href / src / background attributes */
    private const ALLOWED_SCHEMES = [
        'http',
        'https',
        'mailto',
        'tel',
        'sms',
        'ftp',
        'cid',
    ];

    /** @var list<string> Attribute names that carry URLs and need sanitization */
    public const URL_ATTRIBUTES = [
        'href',
        'src',
        'background',
        'action',
        'formaction',
        'poster',
    ];

    /**
     * Return a sanitized URL or '#' if the URL uses a disallowed scheme.
     *
     * - Relative URLs, fragments (#foo), and protocol-relative URLs (//host) pass through.
     * - data: is allowed only for image MIME types (data:image/*).
     * - Unknown schemes are rejected.
     */
    public static function sanitize(string $url): string
    {
        // Strip leading and embedded whitespace before scheme detection.
        // Browsers tolerate tabs/newlines inside URLs like "java\tscript:..",
        // so we collapse them before deciding whether the URL is safe.
        $normalized = preg_replace('/[\x00-\x20]+/', '', $url) ?? $url;

        if ($normalized === '') {
            return $url;
        }

        // Fragment-only or protocol-relative — safe
        if ($normalized[0] === '#' || str_starts_with($normalized, '//')) {
            return $url;
        }

        // No scheme => relative URL (path or ?query)
        if (!preg_match('/^([a-zA-Z][a-zA-Z0-9+.\-]*):/', $normalized, $match)) {
            return $url;
        }

        $scheme = strtolower($match[1]);

        if (\in_array($scheme, self::ALLOWED_SCHEMES, true)) {
            return $url;
        }

        // data: image/* is the only data: form we accept (used for inline images)
        if ($scheme === 'data' && preg_match('/^data:image\/(png|jpe?g|gif|webp|svg\+xml|bmp);/i', $normalized)) {
            return $url;
        }

        return '#';
    }

    public static function isUrlAttribute(string $attribute): bool
    {
        return \in_array($attribute, self::URL_ATTRIBUTES, true);
    }
}
