<?php

declare(strict_types=1);

namespace Mjml\Component\Body;

use Mjml\Component\BodyComponent;
use Mjml\Context\RenderContext;

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
            'id' => 'string',
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
                'word-spacing' => 'normal',
                'background-color' => $this->getAttribute('background-color'),
            ],
        ];
    }

    public function render(): string
    {
        $bgColor = $this->getAttribute('background-color');
        if ($bgColor !== null) {
            $this->globalContext->setBackgroundColor($bgColor);
        }

        $bodyId = $this->getAttribute('id');
        if ($bodyId !== null) {
            $this->globalContext->setBodyId($bodyId);
        }

        $bodyCssClass = $this->getAttribute('css-class');
        if ($bodyCssClass !== null) {
            $this->globalContext->setBodyCssClass($bodyCssClass);
        }

        $title = $this->globalContext->getTitle();
        $lang = $this->globalContext->getLanguage();
        $dir = $this->globalContext->getDir();

        // Inner <div>
        $divAttrs = [];
        if ($title !== '') {
            $divAttrs['aria-label'] = $title;
        }
        $divAttrs['aria-roledescription'] = 'email';
        $divAttrs['role'] = 'article';
        $divAttrs['lang'] = $lang;
        $divAttrs['dir'] = $dir;
        $divAttrs['style'] = 'div';

        return '<div' . $this->htmlAttributes($divAttrs) . '>'
            . $this->renderChildren()
            . '</div>';
    }
}
