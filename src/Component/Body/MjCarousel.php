<?php

declare(strict_types=1);

namespace Shyim\Mjml\Component\Body;

use Shyim\Mjml\Component\BodyComponent;
use Shyim\Mjml\Context\RenderContext;

final class MjCarousel extends BodyComponent
{
    private string $carouselId;

    public static function getComponentName(): string
    {
        return 'mj-carousel';
    }

    public static function allowedAttributes(): array
    {
        return [
            'align' => 'enum(left,center,right)',
            'border-radius' => 'unit(px,%){1,4}',
            'container-background-color' => 'color',
            'icon-width' => 'unit(px,%)',
            'left-icon' => 'string',
            'padding' => 'unit(px,%){1,4}',
            'padding-top' => 'unit(px,%)',
            'padding-bottom' => 'unit(px,%)',
            'padding-left' => 'unit(px,%)',
            'padding-right' => 'unit(px,%)',
            'right-icon' => 'string',
            'thumbnails' => 'enum(visible,hidden,supported)',
            'tb-border' => 'string',
            'tb-border-radius' => 'unit(px,%)',
            'tb-hover-border-color' => 'color',
            'tb-selected-border-color' => 'color',
            'tb-width' => 'unit(px,%)',
            'css-class' => 'string',
        ];
    }

    public static function defaultAttributes(): array
    {
        return [
            'align' => 'center',
            'border-radius' => '6px',
            'icon-width' => '44px',
            'left-icon' => 'https://i.imgur.com/xTh3hln.png',
            'right-icon' => 'https://i.imgur.com/os7o9kz.png',
            'thumbnails' => 'visible',
            'tb-border' => '2px solid transparent',
            'tb-border-radius' => '6px',
            'tb-hover-border-color' => '#fead0d',
            'tb-selected-border-color' => '#ccc',
        ];
    }

    private function getCarouselId(): string
    {
        if (!isset($this->carouselId)) {
            $this->carouselId = bin2hex(random_bytes(8));
        }

        return $this->carouselId;
    }

    public function getChildContext(): RenderContext
    {
        return $this->renderContext;
    }

    protected function getStyles(): array
    {
        return [
            'carousel-div' => [
                'display' => 'table',
                'width' => '100%',
                'table-layout' => 'fixed',
                'text-align' => 'center',
                'font-size' => '0px',
            ],
            'carousel-table' => [
                'caption-side' => 'top',
                'display' => 'table-caption',
                'table-layout' => 'fixed',
                'width' => '100%',
            ],
            'images-td' => [
                'padding' => '0px',
            ],
            'controls-div' => [
                'display' => 'none',
                'mso-hide' => 'all',
            ],
            'controls-img' => [
                'display' => 'block',
                'width' => $this->getAttribute('icon-width'),
                'height' => 'auto',
            ],
            'controls-td' => [
                'font-size' => '0px',
                'display' => 'none',
                'mso-hide' => 'all',
                'padding' => '0px',
            ],
        ];
    }

    private function thumbnailsWidth(): string
    {
        $childCount = \count($this->node->children);
        if ($childCount === 0) {
            return '0';
        }

        $tbWidth = $this->getAttribute('tb-width');
        if ($tbWidth) {
            return $tbWidth;
        }

        $parentWidth = (float) $this->renderContext->containerWidth;

        return min($parentWidth / $childCount, 110) . 'px';
    }

    public function getComponentHeadStyle(): string
    {
        $length = \count($this->node->children);
        $carouselId = $this->getCarouselId();

        if ($length === 0) {
            return '';
        }

        // Build CSS selectors for radio-checked states
        $hideSelectors = [];
        $showSelectors = [];
        $nextSelectors = [];
        $prevSelectors = [];
        $thumbSelectedSelectors = [];
        $thumbDisplaySelectors = [];
        $thumbHoverHideSelectors = [];
        $thumbHoverShowSelectors = [];

        for ($i = 0; $i < $length; $i++) {
            $repeatStr = str_repeat('+ * ', $i);
            $hideSelectors[] = ".mj-carousel-{$carouselId}-radio:checked {$repeatStr}+ .mj-carousel-content .mj-carousel-image";

            $repeatStr2 = str_repeat('+ * ', $length - $i - 1);
            $showSelectors[] = ".mj-carousel-{$carouselId}-radio-" . ($i + 1) . ":checked {$repeatStr2}+ .mj-carousel-content .mj-carousel-image-" . ($i + 1);

            $nextIdx = (($i + 1) % $length) + 1;
            $nextSelectors[] = ".mj-carousel-{$carouselId}-radio-" . ($i + 1) . ":checked {$repeatStr2}+ .mj-carousel-content .mj-carousel-next-{$nextIdx}";

            $prevIdx = ((($i - 1) + $length) % $length) + 1;
            $prevSelectors[] = ".mj-carousel-{$carouselId}-radio-" . ($i + 1) . ":checked {$repeatStr2}+ .mj-carousel-content .mj-carousel-previous-{$prevIdx}";

            $thumbSelectedSelectors[] = ".mj-carousel-{$carouselId}-radio-" . ($i + 1) . ":checked {$repeatStr2}+ .mj-carousel-content .mj-carousel-{$carouselId}-thumbnail-" . ($i + 1);
            $thumbDisplaySelectors[] = ".mj-carousel-{$carouselId}-radio-" . ($i + 1) . ":checked {$repeatStr2}+ .mj-carousel-content .mj-carousel-{$carouselId}-thumbnail";

            $thumbHoverRepeat = str_repeat('+ * ', $length - $i - 1);
            $thumbHoverHideSelectors[] = ".mj-carousel-{$carouselId}-thumbnail:hover {$thumbHoverRepeat}+ .mj-carousel-main .mj-carousel-image";
            $thumbHoverShowSelectors[] = ".mj-carousel-{$carouselId}-thumbnail-" . ($i + 1) . ":hover {$thumbHoverRepeat}+ .mj-carousel-main .mj-carousel-image-" . ($i + 1);
        }

        $tbSelectedColor = $this->getAttribute('tb-selected-border-color');
        $tbHoverColor = $this->getAttribute('tb-hover-border-color');

        $css = "
    .mj-carousel {
      -webkit-user-select: none;
      -moz-user-select: none;
      user-select: none;
    }

    .mj-carousel-{$carouselId}-icons-cell {
      display: table-cell !important;
      width: {$this->getAttribute('icon-width')} !important;
    }

    .mj-carousel-radio,
    .mj-carousel-next,
    .mj-carousel-previous {
      display: none !important;
    }

    .mj-carousel-thumbnail,
    .mj-carousel-next,
    .mj-carousel-previous {
      touch-action: manipulation;
    }

    " . implode(',', $hideSelectors) . " {
      display: none !important;
    }

    " . implode(',', $showSelectors) . " {
      display: block !important;
    }

    .mj-carousel-previous-icons,
    .mj-carousel-next-icons,
    " . implode(",\n    ", $nextSelectors) . ",
    " . implode(",\n    ", $prevSelectors) . " {
      display: block !important;
    }

    " . implode(',', $thumbSelectedSelectors) . " {
      border-color: {$tbSelectedColor} !important;
    }

    " . implode(',', $thumbDisplaySelectors) . " {
      display: inline-block !important;
    }

    .mj-carousel-image img + div,
    .mj-carousel-thumbnail img + div {
      display: none !important;
    }

    " . implode(',', $thumbHoverHideSelectors) . " {
      display: none !important;
    }

    .mj-carousel-thumbnail:hover {
      border-color: {$tbHoverColor} !important;
    }

    " . implode(',', $thumbHoverShowSelectors) . " {
      display: block !important;
    }
    ";

        $repeatFirst = str_repeat('+ *', $length - 1);
        $fallback = "
      .mj-carousel noinput { display: block !important; }
      .mj-carousel noinput .mj-carousel-image-1 { display: block !important;  }
      .mj-carousel noinput .mj-carousel-arrows,
      .mj-carousel noinput .mj-carousel-thumbnails { display: none !important; }

      [owa] .mj-carousel-thumbnail { display: none !important; }

      @media screen yahoo {
          .mj-carousel-{$carouselId}-icons-cell,
          .mj-carousel-previous-icons,
          .mj-carousel-next-icons {
              display: none !important;
          }

          .mj-carousel-{$carouselId}-radio-1:checked {$repeatFirst}+ .mj-carousel-content .mj-carousel-{$carouselId}-thumbnail-1 {
              border-color: transparent;
          }
      }
    ";

        return $css . "\n" . $fallback;
    }

    private function generateRadios(): string
    {
        $carouselId = $this->getCarouselId();

        return $this->renderChildren(
            attributes: ['carouselId' => $carouselId],
            renderer: fn(BodyComponent $component): string => method_exists($component, 'renderRadio')
                ? $component->renderRadio()
                : '',
        );
    }

    private function generateThumbnails(): string
    {
        $thumbnails = $this->getAttribute('thumbnails');
        if (!\in_array($thumbnails, ['visible', 'supported'], true)) {
            return '';
        }

        $carouselId = $this->getCarouselId();

        return $this->renderChildren(
            attributes: [
                'tb-border' => $this->getAttribute('tb-border') ?? '',
                'tb-border-radius' => $this->getAttribute('tb-border-radius') ?? '',
                'tb-width' => $this->thumbnailsWidth(),
                'carouselId' => $carouselId,
                'thumbnails' => $thumbnails,
            ],
            renderer: fn(BodyComponent $component): string => method_exists($component, 'renderThumbnail')
                ? $component->renderThumbnail()
                : '',
        );
    }

    private function generateControls(string $direction, string $icon): string
    {
        $carouselId = $this->getCarouselId();
        $iconWidth = (int) ($this->getAttribute('icon-width') ?? '44');
        $childCount = \count($this->node->children);

        $labels = '';
        for ($i = 1; $i <= $childCount; $i++) {
            $labels .= '<label'
                . $this->htmlAttributes([
                    'for' => "mj-carousel-{$carouselId}-radio-{$i}",
                    'class' => "mj-carousel-{$direction} mj-carousel-{$direction}-{$i}",
                ])
                . '><img'
                . $this->htmlAttributes([
                    'src' => $icon,
                    'alt' => $direction,
                    'style' => 'controls-img',
                    'width' => (string) $iconWidth,
                ])
                . ' /></label>';
        }

        return '<td'
            . $this->htmlAttributes([
                'class' => "mj-carousel-{$carouselId}-icons-cell",
                'style' => 'controls-td',
            ])
            . '><div'
            . $this->htmlAttributes([
                'class' => "mj-carousel-{$direction}-icons",
                'style' => 'controls-div',
            ])
            . '>' . $labels . '</div></td>';
    }

    private function generateImages(): string
    {
        $imagesHtml = $this->renderChildren(
            attributes: ['border-radius' => $this->getAttribute('border-radius') ?? ''],
        );

        return '<td' . $this->htmlAttributes(['style' => 'images-td']) . '>'
            . '<div' . $this->htmlAttributes(['class' => 'mj-carousel-images']) . '>'
            . $imagesHtml
            . '</div></td>';
    }

    private function generateCarousel(): string
    {
        return '<table'
            . $this->htmlAttributes([
                'style' => 'carousel-table',
                'border' => '0',
                'cellpadding' => '0',
                'cellspacing' => '0',
                'width' => '100%',
                'role' => 'presentation',
                'class' => 'mj-carousel-main',
            ])
            . '><tbody><tr>'
            . $this->generateControls('previous', $this->getAttribute('left-icon') ?? '')
            . $this->generateImages()
            . $this->generateControls('next', $this->getAttribute('right-icon') ?? '')
            . '</tr></tbody></table>';
    }

    private function renderFallback(): string
    {
        $children = $this->node->children;
        if (\count($children) === 0) {
            return '';
        }

        $firstChildHtml = $this->renderChildren(
            children: [$children[0]],
            attributes: ['border-radius' => $this->getAttribute('border-radius') ?? ''],
        );

        return '<!--[if mso | IE]>' . $firstChildHtml . '<![endif]-->';
    }

    public function render(): string
    {
        $carouselId = $this->getCarouselId();

        // Register head style
        $this->globalContext->addComponentHeadStyle(fn() => $this->getComponentHeadStyle());

        $innerHtml = '<div' . $this->htmlAttributes(['class' => 'mj-carousel']) . '>'
            . $this->generateRadios()
            . '<div'
            . $this->htmlAttributes([
                'class' => "mj-carousel-content mj-carousel-{$carouselId}-content",
                'style' => 'carousel-div',
            ])
            . '>'
            . $this->generateThumbnails()
            . $this->generateCarousel()
            . '</div></div>';

        // Wrap in mso conditional (negated — hide from mso)
        return '<!--[if !mso | IE]><!-->' . $innerHtml . '<!--<![endif]-->'
            . $this->renderFallback();
    }
}
