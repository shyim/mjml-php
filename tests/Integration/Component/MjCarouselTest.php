<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration\Component;

use Mjml\Tests\Integration\AbstractIntegrationTest;

class MjCarouselTest extends AbstractIntegrationTest
{
    public function testCarouselWithMultipleImagesRenders(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-carousel><mj-carousel-image src="https://example.com/img1.jpg" /><mj-carousel-image src="https://example.com/img2.jpg" /><mj-carousel-image src="https://example.com/img3.jpg" /></mj-carousel></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('https://example.com/img1.jpg', $html);
        self::assertStringContainsString('https://example.com/img2.jpg', $html);
        self::assertStringContainsString('https://example.com/img3.jpg', $html);
        self::assertStringContainsString('mj-carousel', $html);
    }

    public function testRadioInputsArePresent(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-carousel><mj-carousel-image src="https://example.com/img1.jpg" /><mj-carousel-image src="https://example.com/img2.jpg" /></mj-carousel></mj-column></mj-section></mj-body></mjml>');

        // Carousel uses radio inputs for slide selection
        self::assertStringContainsString('type="radio"', $html);
        self::assertStringContainsString('mj-carousel-radio', $html);

        // First radio should be checked
        self::assertStringContainsString('checked="checked"', $html);
    }

    public function testPrevNextControlsRendered(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-carousel><mj-carousel-image src="https://example.com/img1.jpg" /><mj-carousel-image src="https://example.com/img2.jpg" /></mj-carousel></mj-column></mj-section></mj-body></mjml>');

        // Prev/next controls should be present
        self::assertStringContainsString('mj-carousel-previous', $html);
        self::assertStringContainsString('mj-carousel-next', $html);

        // Default icon URLs
        self::assertStringContainsString('https://i.imgur.com/xTh3hln.png', $html);
        self::assertStringContainsString('https://i.imgur.com/os7o9kz.png', $html);
    }

    public function testImagesHaveCorrectSrc(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-carousel><mj-carousel-image src="https://example.com/slide1.jpg" alt="Slide 1" /><mj-carousel-image src="https://example.com/slide2.jpg" alt="Slide 2" /></mj-carousel></mj-column></mj-section></mj-body></mjml>');

        // Images should be rendered with correct src
        $nodes = $this->querySelectorAll($html, 'img[src="https://example.com/slide1.jpg"]');
        self::assertGreaterThan(0, $nodes->length, 'Expected img with slide1.jpg src');

        $nodes = $this->querySelectorAll($html, 'img[src="https://example.com/slide2.jpg"]');
        self::assertGreaterThan(0, $nodes->length, 'Expected img with slide2.jpg src');
    }
}
