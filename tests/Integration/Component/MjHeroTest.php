<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration\Component;

use Mjml\Tests\Integration\AbstractIntegrationTest;

class MjHeroTest extends AbstractIntegrationTest
{
    public function testBasicHeroRendersWithBackgroundUrlAndHeight(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-hero background-url="https://example.com/bg.jpg" background-height="300px" background-width="600px" height="469px"><mj-text>Hello Hero</mj-text></mj-hero></mj-body></mjml>');

        self::assertStringContainsString('Hello Hero', $html);
        self::assertStringContainsString('https://example.com/bg.jpg', $html);

        // The hero should have a td with the background attribute
        $bgAttr = $this->extractAttribute($html, 'td[background="https://example.com/bg.jpg"]', 'background');
        self::assertSame('https://example.com/bg.jpg', $bgAttr);
    }

    public function testFixedHeightModeRendersCorrectly(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-hero mode="fixed-height" height="400px"><mj-text>Fixed Height</mj-text></mj-hero></mj-body></mjml>');

        self::assertStringContainsString('Fixed Height', $html);
        // In fixed-height mode (default), the td should have a height attribute
        // height = 400 - padding-top(0) - padding-bottom(0) = 400
        $nodes = $this->querySelectorAll($html, 'td[height]');
        $found = false;
        for ($i = 0; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            $heightVal = $node?->attributes?->getNamedItem('height')?->nodeValue;
            if ($heightVal === '400') {
                $found = true;
                break;
            }
        }
        self::assertTrue($found, 'Expected a td with height="400" in fixed-height mode');
    }

    public function testContentInsideHeroIsRendered(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-hero height="300px"><mj-text>Hero Text Content</mj-text><mj-button href="https://example.com">Click Me</mj-button></mj-hero></mj-body></mjml>');

        self::assertStringContainsString('Hero Text Content', $html);
        self::assertStringContainsString('Click Me', $html);
        self::assertStringContainsString('https://example.com', $html);
    }

    public function testVmlBackgroundMarkupIsPresentForOutlook(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-hero background-url="https://example.com/bg.jpg" background-height="300px" background-width="600px" height="400px"><mj-text>VML Test</mj-text></mj-hero></mj-body></mjml>');

        // VML v:image should be present for Outlook
        self::assertStringContainsString('v:image', $html);
        self::assertStringContainsString('urn:schemas-microsoft-com:vml', $html);
        self::assertStringContainsString('mso | IE', $html);
    }
}
