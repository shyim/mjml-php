<?php

declare(strict_types=1);

namespace Mjml\Helper;

use Mjml\Parser\Node;

final class JsonToXml
{
    public static function convert(Node $node): string
    {
        $tag = $node->tagName;
        $attrs = '';

        foreach ($node->attributes as $name => $value) {
            $attrs .= ' ' . $name . '="' . $value . '"';
        }

        $hasChildren = $node->children !== [];
        $hasContent = $node->content !== '';

        if (!$hasChildren && !$hasContent) {
            return '<' . $tag . $attrs . ' />';
        }

        $inner = $node->content;

        foreach ($node->children as $child) {
            $inner .= self::convert($child);
        }

        return '<' . $tag . $attrs . '>' . $inner . '</' . $tag . '>';
    }
}
