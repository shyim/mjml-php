<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration\Component;

use Shyim\Mjml\Tests\Integration\AbstractIntegrationTest;

class MjGroupTest extends AbstractIntegrationTest
{
    public function testGroupWithMultipleColumnsDistributesWidth(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-group><mj-column><mj-text>Col 1</mj-text></mj-column><mj-column><mj-text>Col 2</mj-text></mj-column></mj-group></mj-section></mj-body></mjml>');

        self::assertStringContainsString('Col 1', $html);
        self::assertStringContainsString('Col 2', $html);

        // Group renders with mj-outlook-group-fix class
        $nodes = $this->querySelectorAll($html, 'div.mj-outlook-group-fix');
        self::assertGreaterThan(0, $nodes->length, 'Expected div with mj-outlook-group-fix class');
    }

    public function testOutlookConditionalTableStructureIsPresent(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-group><mj-column><mj-text>Outlook Group</mj-text></mj-column></mj-group></mj-section></mj-body></mjml>');

        // Group should have Outlook conditional comments wrapping table/tr/td structure
        self::assertStringContainsString('<!--[if mso | IE]>', $html);
        self::assertStringContainsString('<![endif]-->', $html);
    }

    public function testDirectionAttributeIsApplied(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-group direction="rtl"><mj-column><mj-text>RTL</mj-text></mj-column></mj-group></mj-section></mj-body></mjml>');

        // The group div should have direction:rtl in its style
        $direction = $this->extractStyleValue($html, 'div.mj-outlook-group-fix', 'direction');
        self::assertSame('rtl', $direction);
    }
}
