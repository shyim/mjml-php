<?php

declare(strict_types=1);

namespace Mjml\Component\Body;

use Mjml\Component\BodyComponent;
use Mjml\Context\RenderContext;

final class MjWrapper extends MjSection
{
    public static function getComponentName(): string
    {
        return 'mj-wrapper';
    }

    public static function allowedAttributes(): array
    {
        return array_merge(parent::allowedAttributes(), [
            'gap' => 'unit(px)',
        ]);
    }

    public function getChildContext(): RenderContext
    {
        $boxWidths = $this->getBoxWidths();
        $gap = $this->getAttribute('gap');

        return new RenderContext(
            containerWidth: $boxWidths['box'] . 'px',
            sectionGap: $gap,
            columnGap: $this->renderContext->columnGap,
        );
    }

    protected function renderWrappedChildren(): string
    {
        $containerWidth = $this->renderContext->containerWidth;

        return $this->renderChildren(
            renderer: function (BodyComponent $component) use ($containerWidth): string {
                if ($component::isRawElement()) {
                    return $component->render();
                }

                $outlookClass = self::suffixCssClasses(
                    $component->getAttribute('css-class'),
                    'outlook',
                );

                $tdAttrs = [
                    'align' => $component->getAttribute('align'),
                    'width' => $containerWidth,
                    'style' => 'tdOutlook',
                ];
                if ($outlookClass !== '') {
                    $tdAttrs['class'] = $outlookClass;
                }

                return '<!--[if mso | IE]><tr><td' . $component->htmlAttributes($tdAttrs) . '><![endif]-->'
                    . $component->render()
                    . '<!--[if mso | IE]></td></tr><![endif]-->';
            },
        );
    }
}
