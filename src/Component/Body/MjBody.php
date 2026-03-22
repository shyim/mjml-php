<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;
use Shyim\Mjml\Context\RenderContext;

final class MjBody extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-body';
    }

    public static function allowedAttributes(): array
    {
        return [
            'width' => 'unit(px)',
            'background-color' => 'color',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'width' => '600px',
        ];
    }

    public function getChildContext(): RenderContext
    {
        return $this->renderContext->withContainerWidth(
            $this->getAttribute('width') ?? '600px',
        );
    }

    protected function getStyles(): array
    {
        return [
            'div' => [
                'background-color' => $this->getAttribute('background-color'),
            ],
        ];
    }

    public function render(): string
    {
        $bgColor = $this->getAttribute('background-color');
        if ($bgColor !== null) {
            $this->globalContext->backgroundColor = $bgColor;
        }

        $title = $this->globalContext->title;
        $lang = $this->globalContext->language;
        $dir = $this->globalContext->dir;

        $attrs = [];
        if ($title !== '') {
            $attrs['aria-label'] = $title;
        }
        $attrs['aria-roledescription'] = 'email';
        $cssClass = $this->getAttribute('css-class');
        if ($cssClass !== null) {
            $attrs['class'] = $cssClass;
        }
        $attrs['style'] = 'div';
        $attrs['role'] = 'article';
        $attrs['lang'] = $lang;
        $attrs['dir'] = $dir;

        return '<div' . $this->htmlAttributes($attrs) . '>'
            . $this->renderChildren()
            . '</div>';
    }
}
