<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;
use Shyim\Mjml\Component\Data\SocialNetworks;

final class MjSocialElement extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-social-element';
    }

    public static function isEndingTag(): bool
    {
        return true;
    }

    public static function allowedAttributes(): array
    {
        return [
            'align' => 'enum(left,center,right)',
            'icon-position' => 'enum(left,right)',
            'background-color' => 'color',
            'color' => 'color',
            'border-radius' => 'string',
            'font-family' => 'string',
            'font-size' => 'unit(px)',
            'font-style' => 'string',
            'font-weight' => 'string',
            'href' => 'string',
            'icon-size' => 'unit(px,%)',
            'icon-height' => 'unit(px,%)',
            'icon-padding' => 'unit(px,%){1,4}',
            'line-height' => 'unit(px,%,)',
            'name' => 'string',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'padding' => 'unit(px,%){1,4}',
            'text-padding' => 'unit(px,%){1,4}',
            'rel' => 'string',
            'src' => 'string',
            'srcset' => 'string',
            'sizes' => 'string',
            'alt' => 'string',
            'title' => 'string',
            'target' => 'string',
            'text-decoration' => 'string',
            'vertical-align' => 'enum(top,middle,bottom)',
            'css-class' => 'string',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'alt' => '',
            'align' => 'left',
            'icon-position' => 'left',
            'color' => '#000',
            'border-radius' => '3px',
            'font-family' => 'Ubuntu, Helvetica, Arial, sans-serif',
            'font-size' => '13px',
            'line-height' => '1',
            'padding' => '4px',
            'text-padding' => '4px 4px 4px 0',
            'target' => '_blank',
            'text-decoration' => 'none',
            'vertical-align' => 'middle',
        ];
    }

    /**
     * @return array{href: ?string, 'icon-size': ?string, 'icon-height': ?string, srcset: ?string, sizes: ?string, src: ?string, 'background-color': ?string}
     */
    private function getSocialAttributes(): array
    {
        $socialNetwork = SocialNetworks::get($this->getAttribute('name') ?? '') ?? [];
        $href = $this->getAttribute('href');

        if ($href !== null && isset($socialNetwork['share-url'])) {
            $href = str_replace('[[URL]]', $href, $socialNetwork['share-url']);
        }

        $attrs = [];
        foreach (['icon-size', 'icon-height', 'srcset', 'sizes', 'src', 'background-color'] as $attr) {
            $attrs[$attr] = $this->getAttribute($attr) ?: ($socialNetwork[$attr] ?? null);
        }

        return array_merge(['href' => $href], $attrs);
    }

    protected function getStyles(): array
    {
        $social = $this->getSocialAttributes();
        $iconSize = $social['icon-size'] ?? null;
        $iconHeight = $social['icon-height'] ?? null;
        $backgroundColor = $social['background-color'] ?? null;

        return [
            'td' => [
                'padding' => $this->getAttribute('padding'),
                'padding-top' => $this->getAttribute('padding-top'),
                'padding-right' => $this->getAttribute('padding-right'),
                'padding-bottom' => $this->getAttribute('padding-bottom'),
                'padding-left' => $this->getAttribute('padding-left'),
                'vertical-align' => $this->getAttribute('vertical-align'),
            ],
            'table' => [
                'background' => $backgroundColor,
                'border-radius' => $this->getAttribute('border-radius'),
                'width' => $iconSize,
            ],
            'icon' => [
                'padding' => $this->getAttribute('icon-padding'),
                'font-size' => '0',
                'height' => $iconHeight ?: $iconSize,
                'vertical-align' => 'middle',
                'width' => $iconSize,
            ],
            'img' => [
                'border-radius' => $this->getAttribute('border-radius'),
                'display' => 'block',
            ],
            'tdText' => [
                'vertical-align' => 'middle',
                'padding' => $this->getAttribute('text-padding'),
                'text-align' => $this->getAttribute('align'),
            ],
            'text' => [
                'color' => $this->getAttribute('color'),
                'font-size' => $this->getAttribute('font-size'),
                'font-weight' => $this->getAttribute('font-weight'),
                'font-style' => $this->getAttribute('font-style'),
                'font-family' => $this->getAttribute('font-family'),
                'line-height' => $this->getAttribute('line-height'),
                'text-decoration' => $this->getAttribute('text-decoration'),
            ],
        ];
    }

    private function makeIcon(): string
    {
        $social = $this->getSocialAttributes();
        $src = $social['src'] ?? '';
        $href = $social['href'];
        $iconSize = $social['icon-size'] ?? '20px';
        $srcset = $social['srcset'] ?? null;
        $sizes = $social['sizes'] ?? null;
        $hasLink = $this->getAttribute('href') !== null;

        $imgAttrs = [
            'alt' => $this->getAttribute('alt'),
            'title' => $this->getAttribute('title'),
            'src' => $src,
            'style' => 'img',
            'width' => (string) (int) $iconSize,
        ];
        if ($sizes !== null) {
            $imgAttrs['sizes'] = $sizes;
        }
        if ($srcset !== null) {
            $imgAttrs['srcset'] = $srcset;
        }

        $imgTag = '<img' . $this->htmlAttributes($imgAttrs) . ' />';

        $linkOpen = '';
        $linkClose = '';
        if ($hasLink) {
            $linkOpen = '<a' . $this->htmlAttributes([
                'href' => $href,
                'rel' => $this->getAttribute('rel'),
                'target' => $this->getAttribute('target'),
            ]) . '>';
            $linkClose = '</a>';
        }

        return '<td' . $this->htmlAttributes(['style' => 'td']) . '>'
            . '<table'
            . $this->htmlAttributes([
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'role' => 'presentation',
                'style' => 'table',
            ])
            . '><tbody><tr>'
            . '<td' . $this->htmlAttributes(['style' => 'icon']) . '>'
            . $linkOpen . $imgTag . $linkClose
            . '</td>'
            . '</tr></tbody></table>'
            . '</td>';
    }

    private function makeContent(): string
    {
        $content = $this->getContent();
        if ($content === '') {
            return '';
        }

        $social = $this->getSocialAttributes();
        $href = $social['href'];
        $hasLink = $this->getAttribute('href') !== null;

        if ($hasLink) {
            $textHtml = '<a'
                . $this->htmlAttributes([
                    'href' => $href,
                    'style' => 'text',
                    'rel' => $this->getAttribute('rel'),
                    'target' => $this->getAttribute('target'),
                ])
                . '> ' . $content . ' </a>';
        } else {
            $textHtml = '<span' . $this->htmlAttributes(['style' => 'text']) . '>'
                . ' ' . $content . ' </span>';
        }

        return '<td' . $this->htmlAttributes(['style' => 'tdText']) . '>'
            . $textHtml
            . '</td>';
    }

    public function render(): string
    {
        $iconPosition = $this->getAttribute('icon-position') ?? 'left';

        if ($iconPosition === 'left') {
            $innerHtml = $this->makeIcon() . $this->makeContent();
        } else {
            $innerHtml = $this->makeContent() . $this->makeIcon();
        }

        return '<tr'
            . $this->htmlAttributes([
                'class' => $this->getAttribute('css-class'),
            ])
            . '>'
            . $innerHtml
            . '</tr>';
    }
}
