<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration\Component;

use Shyim\Mjml\Tests\Integration\AbstractIntegrationTest;

class MjAccordionTest extends AbstractIntegrationTest
{
    public function testFullAccordionRendersElementsTitlesAndText(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-accordion><mj-accordion-element><mj-accordion-title>Title 1</mj-accordion-title><mj-accordion-text>Content 1</mj-accordion-text></mj-accordion-element><mj-accordion-element><mj-accordion-title>Title 2</mj-accordion-title><mj-accordion-text>Content 2</mj-accordion-text></mj-accordion-element></mj-accordion></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('Title 1', $html);
        self::assertStringContainsString('Title 2', $html);
        self::assertStringContainsString('Content 1', $html);
        self::assertStringContainsString('Content 2', $html);

        // Should have the mj-accordion class on the table
        $nodes = $this->querySelectorAll($html, 'table.mj-accordion');
        self::assertGreaterThan(0, $nodes->length, 'Expected table with mj-accordion class');
    }

    public function testCheckboxInputIsPresentForExpandCollapse(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-accordion><mj-accordion-element><mj-accordion-title>Title</mj-accordion-title><mj-accordion-text>Content</mj-accordion-text></mj-accordion-element></mj-accordion></mj-column></mj-section></mj-body></mjml>');

        // Accordion elements should contain checkbox inputs for expand/collapse
        self::assertStringContainsString('mj-accordion-checkbox', $html);
        self::assertStringContainsString('type="checkbox"', $html);
    }

    public function testIconImagesAreRendered(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-accordion><mj-accordion-element><mj-accordion-title>Title</mj-accordion-title><mj-accordion-text>Content</mj-accordion-text></mj-accordion-element></mj-accordion></mj-column></mj-section></mj-body></mjml>');

        // Default icons should be present (wrapped and unwrapped)
        self::assertStringContainsString('mj-accordion-more', $html);
        self::assertStringContainsString('mj-accordion-less', $html);
        // Default icon URLs from MjAccordion defaults
        self::assertStringContainsString('https://i.imgur.com/bIXv1bk.png', $html);
        self::assertStringContainsString('https://i.imgur.com/w4uTygT.png', $html);
    }

    public function testBorderAttributeIsApplied(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-accordion border="3px solid red"><mj-accordion-element><mj-accordion-title>Title</mj-accordion-title><mj-accordion-text>Content</mj-accordion-text></mj-accordion-element></mj-accordion></mj-column></mj-section></mj-body></mjml>');

        // The accordion table should have the border style applied
        $border = $this->extractStyleValue($html, 'table.mj-accordion', 'border');
        self::assertSame('3px solid red', $border);
    }

    public function testContentTextIsRendered(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-accordion><mj-accordion-element><mj-accordion-title>FAQ Question</mj-accordion-title><mj-accordion-text>This is the detailed answer to the FAQ question.</mj-accordion-text></mj-accordion-element></mj-accordion></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('FAQ Question', $html);
        self::assertStringContainsString('This is the detailed answer to the FAQ question.', $html);
        // Content should be wrapped in accordion-content div
        self::assertStringContainsString('mj-accordion-content', $html);
    }
}
