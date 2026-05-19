<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration\Component;

use Mjml\Tests\Integration\AbstractIntegrationTest;

final class MjSectionTest extends AbstractIntegrationTest
{
    public function testBasicSectionRendersWithTableStructure(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text>Content</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // Section renders a div wrapping a table
        $nodes = $this->querySelectorAll($html, 'div > table > tbody > tr > td');
        self::assertGreaterThan(0, $nodes->length, 'Section should render with table structure');
    }

    public function testBackgroundColorIsApplied(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section background-color="#ff0000">
              <mj-column>
                <mj-text>Red Section</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertStringContainsString('background:#ff0000;', $html);
        self::assertStringContainsString('background-color:#ff0000;', $html);
    }

    public function testPaddingIsAppliedToTd(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section padding="30px 40px">
              <mj-column>
                <mj-text>Padded Section</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertStringContainsString('padding:30px 40px;', $html);
    }

    public function testFullWidthModeChangesStructure(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section full-width="full-width" background-color="#0000ff">
              <mj-column>
                <mj-text>Full Width</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // In full-width mode, the background-color is applied to the outer table (tableFullwidth style),
        // and the section is wrapped in an outer table
        $nodes = $this->querySelectorAll($html, 'table > tbody > tr > td > div');
        self::assertGreaterThan(0, $nodes->length, 'Full-width should wrap section in outer table');

        // The outer table should have the background
        self::assertSame('100%', $this->extractStyleValue($html, 'table', 'width'));
    }

    public function testBackgroundUrlGeneratesVmlForOutlook(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section background-url="https://example.com/bg.jpg" background-color="#ffffff">
              <mj-column>
                <mj-text>BG Section</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // VML v:rect and v:fill should be present for Outlook background support
        self::assertStringContainsString('v:rect', $html);
        self::assertStringContainsString('v:fill', $html);
        self::assertStringContainsString('https://example.com/bg.jpg', $html);
    }

    public function testCssClassIsAppliedToDiv(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section css-class="my-section">
              <mj-column>
                <mj-text>Classed Section</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        $nodes = $this->querySelectorAll($html, 'div.my-section');
        self::assertGreaterThan(0, $nodes->length, 'css-class "my-section" should be applied to the div');
    }
}
