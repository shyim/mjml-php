<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;

final class MjNavbarLink extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-navbar-link';
    }

    public static function isEndingTag(): bool
    {
        return true;
    }

    public static function allowedAttributes(): array
    {
        return [
            'color' => 'color',
            'font-family' => 'string',
            'font-size' => 'unit(px)',
            'font-style' => 'string',
            'font-weight' => 'string',
            'href' => 'string',
            'name' => 'string',
            'target' => 'string',
            'rel' => 'string',
            'letter-spacing' => 'unitWithNegative(px,em)',
            'line-height' => 'unit(px,%,)',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'padding-top' => 'unit(px,%)',
            'padding' => 'unit(px,%){1,4}',
            'text-decoration' => 'string',
            'text-transform' => 'string',
            'css-class' => 'string',
            'navbarBaseUrl' => 'string',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'color' => '#000000',
            'font-family' => 'Ubuntu, Helvetica, Arial, sans-serif',
            'font-size' => '13px',
            'font-weight' => 'normal',
            'line-height' => '22px',
            'padding' => '15px 10px',
            'target' => '_blank',
            'text-decoration' => 'none',
            'text-transform' => 'uppercase',
        ];
    }

    protected function getStyles(): array
    {
        return [
            'a' => [
                'display' => 'inline-block',
                'color' => $this->getAttribute('color'),
                'font-family' => $this->getAttribute('font-family'),
                'font-size' => $this->getAttribute('font-size'),
                'font-style' => $this->getAttribute('font-style'),
                'font-weight' => $this->getAttribute('font-weight'),
                'letter-spacing' => $this->getAttribute('letter-spacing'),
                'line-height' => $this->getAttribute('line-height'),
                'text-decoration' => $this->getAttribute('text-decoration'),
                'text-transform' => $this->getAttribute('text-transform'),
                'padding' => $this->getAttribute('padding'),
                'padding-top' => $this->getAttribute('padding-top'),
                'padding-left' => $this->getAttribute('padding-left'),
                'padding-right' => $this->getAttribute('padding-right'),
                'padding-bottom' => $this->getAttribute('padding-bottom'),
            ],
            'td' => [
                'padding' => $this->getAttribute('padding'),
                'padding-top' => $this->getAttribute('padding-top'),
                'padding-left' => $this->getAttribute('padding-left'),
                'padding-right' => $this->getAttribute('padding-right'),
                'padding-bottom' => $this->getAttribute('padding-bottom'),
            ],
        ];
    }

    private function renderContent(): string
    {
        $href = $this->getAttribute('href') ?? '';
        $navbarBaseUrl = $this->getAttribute('navbarBaseUrl');
        $link = ($navbarBaseUrl !== null && $navbarBaseUrl !== '') ? $navbarBaseUrl . $href : $href;

        $cssClass = $this->getAttribute('css-class');
        $className = 'mj-link' . ($cssClass ? ' ' . $cssClass : '');

        return '<a'
            . $this->htmlAttributes([
                'class' => $className,
                'href' => $link,
                'rel' => $this->getAttribute('rel'),
                'target' => $this->getAttribute('target'),
                'name' => $this->getAttribute('name'),
                'style' => 'a',
            ])
            . '>'
            . ' ' . $this->getContent() . ' '
            . '</a>';
    }

    public function render(): string
    {
        $cssClass = $this->getAttribute('css-class');
        $outlookClass = self::suffixCssClasses($cssClass, 'outlook');

        return '<!--[if mso | IE]>'
            . '<td'
            . $this->htmlAttributes([
                'style' => 'td',
                'class' => $outlookClass,
            ])
            . '>'
            . '<![endif]-->'
            . $this->renderContent()
            . '<!--[if mso | IE]>'
            . '</td>'
            . '<![endif]-->';
    }
}
