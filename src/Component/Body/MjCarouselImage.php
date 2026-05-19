<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;

final class MjCarouselImage extends BodyComponent
{
    public static function getComponentName(): string
    {
        return 'mj-carousel-image';
    }

    public static function isEndingTag(): bool
    {
        return true;
    }

    public static function allowedAttributes(): array
    {
        return [
            'alt' => 'string',
            'href' => 'string',
            'rel' => 'string',
            'target' => 'string',
            'title' => 'string',
            'src' => 'string',
            'thumbnails-src' => 'string',
            'thumbnails' => 'enum(visible,hidden,supported)',
            'border-radius' => 'string',
            'tb-border' => 'string',
            'tb-border-radius' => 'string',
            'tb-width' => 'unit(px,%)',
            'css-class' => 'string',
            'carouselId' => 'string',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'alt' => '',
            'target' => '_blank',
        ];
    }

    protected function getStyles(): array
    {
        return [
            'images-img' => [
                'border-radius' => $this->getAttribute('border-radius'),
                'display' => 'block',
                'width' => $this->renderContext->containerWidth,
                'max-width' => '100%',
                'height' => 'auto',
            ],
            'images-firstImageDiv' => [],
            'images-otherImageDiv' => [
                'display' => 'none',
                'mso-hide' => 'all',
            ],
            'radio-input' => [
                'display' => 'none',
                'mso-hide' => 'all',
            ],
            'thumbnails-a' => [
                'border' => $this->getAttribute('tb-border'),
                'border-radius' => $this->getAttribute('tb-border-radius'),
                'display' => $this->getAttribute('thumbnails') === 'supported' ? 'none' : 'inline-block',
                'overflow' => 'hidden',
                'width' => $this->getAttribute('tb-width'),
            ],
            'thumbnails-img' => [
                'display' => 'block',
                'width' => '100%',
                'height' => 'auto',
            ],
        ];
    }

    public function renderThumbnail(): string
    {
        $carouselId = $this->getAttribute('carouselId') ?? '';
        $src = $this->getAttribute('src') ?? '';
        $alt = $this->getAttribute('alt') ?? '';
        $tbWidth = $this->getAttribute('tb-width') ?? '';
        $target = $this->getAttribute('target') ?? '_blank';
        $imgIndex = $this->renderContext->index + 1;
        $cssClass = self::suffixCssClasses($this->getAttribute('css-class'), 'thumbnail');

        return '<a'
            . $this->htmlAttributes([
                'style' => 'thumbnails-a',
                'href' => '#' . $imgIndex,
                'target' => $target,
                'class' => "mj-carousel-thumbnail mj-carousel-{$carouselId}-thumbnail mj-carousel-{$carouselId}-thumbnail-{$imgIndex}" . ($cssClass !== '' ? " {$cssClass}" : ''),
            ])
            . '><label'
            . $this->htmlAttributes([
                'for' => "mj-carousel-{$carouselId}-radio-{$imgIndex}",
            ])
            . '><img'
            . $this->htmlAttributes([
                'style' => 'thumbnails-img',
                'src' => $this->getAttribute('thumbnails-src') ?: $src,
                'alt' => $alt,
                'width' => (string) (int) $tbWidth,
            ])
            . ' /></label></a>';
    }

    public function renderRadio(): string
    {
        $index = $this->renderContext->index;
        $carouselId = $this->getAttribute('carouselId') ?? '';

        return '<input'
            . $this->htmlAttributes([
                'class' => "mj-carousel-radio mj-carousel-{$carouselId}-radio mj-carousel-{$carouselId}-radio-" . ($index + 1),
                'checked' => $index === 0 ? 'checked' : null,
                'type' => 'radio',
                'name' => "mj-carousel-radio-{$carouselId}",
                'id' => "mj-carousel-{$carouselId}-radio-" . ($index + 1),
                'style' => 'radio-input',
            ])
            . ' />';
    }

    public function render(): string
    {
        $src = $this->getAttribute('src') ?? '';
        $alt = $this->getAttribute('alt') ?? '';
        $href = $this->getAttribute('href');
        $rel = $this->getAttribute('rel');
        $title = $this->getAttribute('title');
        $index = $this->renderContext->index;
        $containerWidth = (int) $this->renderContext->containerWidth;

        $image = '<img'
            . $this->htmlAttributes([
                'title' => $title,
                'src' => $src,
                'alt' => $alt,
                'style' => 'images-img',
                'width' => (string) $containerWidth,
                'border' => '0',
            ])
            . ' />';

        $cssClass = $this->getAttribute('css-class') ?? '';
        $styleName = $index === 0 ? 'images-firstImageDiv' : 'images-otherImageDiv';

        $content = $href
            ? '<a' . $this->htmlAttributes(['href' => $href, 'rel' => $rel, 'target' => '_blank']) . '>' . $image . '</a>'
            : $image;

        return '<div'
            . $this->htmlAttributes([
                'class' => "mj-carousel-image mj-carousel-image-" . ($index + 1) . ($cssClass ? " {$cssClass}" : ''),
                'style' => $styleName,
            ])
            . '>' . $content . '</div>';
    }
}
