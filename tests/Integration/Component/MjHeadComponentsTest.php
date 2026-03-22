<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration\Component;

use Shyim\Mjml\Tests\Integration\AbstractIntegrationTest;

class MjHeadComponentsTest extends AbstractIntegrationTest
{
    public function testMjTitleSetsTheTitleTag(): void
    {
        $html = $this->renderMjml('<mjml><mj-head><mj-title>My Email Title</mj-title></mj-head><mj-body><mj-section><mj-column><mj-text>Content</mj-text></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('<title>My Email Title</title>', $html);
    }

    public function testMjPreviewSetsThePreviewDiv(): void
    {
        $html = $this->renderMjml('<mjml><mj-head><mj-preview>Preview text here</mj-preview></mj-head><mj-body><mj-section><mj-column><mj-text>Content</mj-text></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('Preview text here', $html);
        // Preview should be in a hidden div
        self::assertStringContainsString('display:none', $html);
    }

    public function testMjFontAddsFontImportTags(): void
    {
        $html = $this->renderMjml('<mjml><mj-head><mj-font name="Roboto" href="https://fonts.googleapis.com/css?family=Roboto" /></mj-head><mj-body><mj-section><mj-column><mj-text font-family="Roboto">Content</mj-text></mj-column></mj-section></mj-body></mjml>');

        // Font should be imported via a link or style tag
        self::assertStringContainsString('https://fonts.googleapis.com/css?family=Roboto', $html);
    }

    public function testMjStyleAddsCustomCss(): void
    {
        $html = $this->renderMjml('<mjml><mj-head><mj-style>.custom-class { color: red; }</mj-style></mj-head><mj-body><mj-section><mj-column><mj-text>Content</mj-text></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('.custom-class { color: red; }', $html);
    }

    public function testMjBreakpointChangesTheMediaQueryBreakpoint(): void
    {
        $html = $this->renderMjml('<mjml><mj-head><mj-breakpoint width="400px" /></mj-head><mj-body><mj-section><mj-column><mj-text>Content</mj-text></mj-column></mj-section></mj-body></mjml>');

        // The media query should use 400px as the breakpoint
        self::assertStringContainsString('400px', $html);
        // Default is 480px, so 480 should NOT appear as a breakpoint
        self::assertStringNotContainsString('max-width:480px', $html);
    }

    public function testMjAttributesSetsDefaultAttributesOnComponents(): void
    {
        $html = $this->renderMjml('<mjml><mj-head><mj-attributes><mj-text color="#ff0000" font-size="20px" /></mj-attributes></mj-head><mj-body><mj-section><mj-column><mj-text>Styled via attributes</mj-text></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('Styled via attributes', $html);
        // The text should have the default color and font-size applied
        self::assertStringContainsString('color:#ff0000', $html);
        self::assertStringContainsString('font-size:20px', $html);
    }
}
