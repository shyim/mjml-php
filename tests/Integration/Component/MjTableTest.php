<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration\Component;

use Mjml\Tests\Integration\AbstractIntegrationTest;

class MjTableTest extends AbstractIntegrationTest
{
    public function testRawHtmlTableContentPassesThrough(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-table><tr><td>Cell 1</td><td>Cell 2</td></tr><tr><td>Cell 3</td><td>Cell 4</td></tr></mj-table></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('Cell 1', $html);
        self::assertStringContainsString('Cell 2', $html);
        self::assertStringContainsString('Cell 3', $html);
        self::assertStringContainsString('Cell 4', $html);
    }

    public function testStylingAttributesAreApplied(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-table color="#ff0000" font-family="Arial" font-size="16px"><tr><td>Styled</td></tr></mj-table></mj-column></mj-section></mj-body></mjml>');

        // Find the mj-table rendered table (not the wrapper tables)
        // The table with raw content should have the styling
        self::assertStringContainsString('Styled', $html);

        // Check for color in style attribute
        self::assertStringContainsString('color:#ff0000', $html);
        self::assertStringContainsString('font-family:Arial', $html);
        self::assertStringContainsString('font-size:16px', $html);
    }

    public function testWidthAttributeWorks(): void
    {
        $html = $this->renderMjml('<mjml><mj-body><mj-section><mj-column><mj-table width="80%"><tr><td>Width Test</td></tr></mj-table></mj-column></mj-section></mj-body></mjml>');

        self::assertStringContainsString('Width Test', $html);
        // The table should have width attribute or width style
        self::assertStringContainsString('width:80%', $html);
    }
}
