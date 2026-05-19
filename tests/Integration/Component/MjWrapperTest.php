<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration\Component;

use Mjml\Tests\Integration\AbstractIntegrationTest;

class MjWrapperTest extends AbstractIntegrationTest
{
    public function testWrapperRendersChildrenSections(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-wrapper><mj-section><mj-column><mj-text>Section 1</mj-text></mj-column></mj-section><mj-section><mj-column><mj-text>Section 2</mj-text></mj-column></mj-section></mj-wrapper></mj-body></mjml>');

        self::assertStringContainsString('Section 1', $html);
        self::assertStringContainsString('Section 2', $html);
    }

    public function testBackgroundColorIsApplied(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-wrapper background-color="#ff0000"><mj-section><mj-column><mj-text>Red bg</mj-text></mj-column></mj-section></mj-wrapper></mj-body></mjml>');

        self::assertStringContainsString('#ff0000', $html);
    }

    public function testFullWidthModeWorks(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-wrapper full-width="full-width" background-color="#00ff00"><mj-section><mj-column><mj-text>Full Width</mj-text></mj-column></mj-section></mj-wrapper></mj-body></mjml>');

        self::assertStringContainsString('Full Width', $html);
        // Full width wrapper should have a table with width:100%
        $widthStyle = $this->extractStyleValue($html, 'table[role="presentation"]', 'width');
        self::assertSame('100%', $widthStyle);
    }

    public function testSectionsInsideWrapperAreWrappedForOutlook(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-wrapper><mj-section><mj-column><mj-text>Wrapped Section</mj-text></mj-column></mj-section></mj-wrapper></mj-body></mjml>');

        // Wrapper wraps each child section in separate Outlook conditional tr/td
        // The wrapper renderWrappedChildren uses <!--[if mso | IE]><tr><td...> with width attribute
        self::assertStringContainsString('Wrapped Section', $html);
        // Outlook wrapper renders sections inside td elements with width attribute
        self::assertStringContainsString('width="600px"', $html);
    }
}
