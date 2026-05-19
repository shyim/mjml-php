<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration\Component;

use Mjml\Tests\Integration\AbstractIntegrationTest;

class MjNavbarTest extends AbstractIntegrationTest
{
    public function testRendersNavbarLinkItems(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-navbar><mj-navbar-link href="https://example.com/home">Home</mj-navbar-link><mj-navbar-link href="https://example.com/about">About</mj-navbar-link></mj-navbar></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('Home', $html);
        self::assertStringContainsString('About', $html);
        self::assertStringContainsString('https://example.com/home', $html);
        self::assertStringContainsString('https://example.com/about', $html);
    }

    public function testLinksHaveCorrectAttributes(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-navbar><mj-navbar-link href="https://example.com" color="#ff0000" font-family="Arial">Link</mj-navbar-link></mj-navbar></mj-column></mj-section></mj-body></mjml>');

        // Link should have the correct color and font-family in styles
        $color = $this->extractStyleValue($html, 'a.mj-link', 'color');
        self::assertSame('#ff0000', $color);

        $fontFamily = $this->extractStyleValue($html, 'a.mj-link', 'font-family');
        self::assertSame('Arial', $fontFamily);

        // Link should have the correct href
        $href = $this->extractAttribute($html, 'a.mj-link', 'href');
        self::assertSame('https://example.com', $href);
    }

    public function testHamburgerMenuMarkupIsPresent(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-navbar hamburger="hamburger"><mj-navbar-link href="https://example.com">Link</mj-navbar-link></mj-navbar></mj-column></mj-section></mj-body></mjml>');

        // Hamburger mode adds checkbox input and trigger div
        self::assertStringContainsString('mj-menu-checkbox', $html);
        self::assertStringContainsString('mj-menu-trigger', $html);
        self::assertStringContainsString('mj-menu-icon-open', $html);
        self::assertStringContainsString('mj-menu-icon-close', $html);
    }
}
