<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component;

interface ComponentInterface
{
    /**
     * The MJML tag name (e.g., "mj-text", "mj-section").
     */
    public static function getComponentName(): string;

    /**
     * Map of allowed attributes and their type strings.
     *
     * Keys are attribute names, values are JS-style type strings like:
     *   "color", "string", "boolean", "integer",
     *   "unit(px)", "unit(px,%)", "unit(px,%){1,4}",
     *   "enum(left,center,right)"
     *
     * @return array<string, string>
     */
    public static function allowedAttributes(): array;

    /**
     * Default values for attributes.
     *
     * @return array<string, string>
     */
    public static function defaultAttributes(): array;

    /**
     * Whether this component has a closing tag with inner content (like mj-text, mj-button).
     */
    public static function isEndingTag(): bool;

    /**
     * Whether this is a raw element (content passed through without processing).
     */
    public static function isRawElement(): bool;
}
