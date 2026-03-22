<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration\Component;

use Shyim\Mjml\Tests\Integration\AbstractIntegrationTest;

class MjSocialTest extends AbstractIntegrationTest
{
    public function testSocialWithMultipleElementsRenders(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-social><mj-social-element name="facebook" href="https://example.com">Facebook</mj-social-element><mj-social-element name="twitter" href="https://example.com">Twitter</mj-social-element></mj-social></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('Facebook', $html);
        self::assertStringContainsString('Twitter', $html);
    }

    public function testIconImagesUseCorrectMailjetCdnUrls(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-social><mj-social-element name="facebook" href="https://example.com">FB</mj-social-element><mj-social-element name="twitter" href="https://example.com">TW</mj-social-element><mj-social-element name="github" href="https://example.com">GH</mj-social-element></mj-social></mj-column></mj-section></mj-body></mjml>');

        // Icon images should use Mailjet CDN URLs
        self::assertStringContainsString('https://www.mailjet.com/images/theme/v1/icons/ico-social/facebook.png', $html);
        self::assertStringContainsString('https://www.mailjet.com/images/theme/v1/icons/ico-social/twitter.png', $html);
        self::assertStringContainsString('https://www.mailjet.com/images/theme/v1/icons/ico-social/github.png', $html);
    }

    public function testHrefIsTransformedIntoShareUrl(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-social><mj-social-element name="facebook" href="https://mysite.com">Share</mj-social-element></mj-social></mj-column></mj-section></mj-body></mjml>');

        // Facebook share URL should contain the href with the share URL pattern
        self::assertStringContainsString('https://www.facebook.com/sharer/sharer.php?u=https://mysite.com', $html);
    }

    public function testHorizontalModeUsesTableLayout(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-social mode="horizontal"><mj-social-element name="facebook" href="https://example.com">FB</mj-social-element><mj-social-element name="twitter" href="https://example.com">TW</mj-social-element></mj-social></mj-column></mj-section></mj-body></mjml>');

        // Horizontal mode wraps each element in its own table with Outlook conditional td separators
        self::assertStringContainsString('<!--[if mso | IE]></td><td><![endif]-->', $html);

        // Each social element is wrapped in a separate table
        $nodes = $this->querySelectorAll($html, 'table[role="presentation"]');
        self::assertGreaterThanOrEqual(2, $nodes->length, 'Expected multiple presentation tables for horizontal layout');
    }

    public function testVerticalModeUsesTrBasedLayout(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-social mode="vertical"><mj-social-element name="facebook" href="https://example.com">FB</mj-social-element><mj-social-element name="twitter" href="https://example.com">TW</mj-social-element></mj-social></mj-column></mj-section></mj-body></mjml>');

        // Vertical mode renders children directly in a table with tr-based layout
        // Should not contain the inline-table wrapper
        self::assertStringNotContainsString('display:inline-table', $html);

        // Each social element renders as a <tr>
        $nodes = $this->querySelectorAll($html, 'table[role="presentation"] tr');
        self::assertGreaterThan(0, $nodes->length);
    }
}
