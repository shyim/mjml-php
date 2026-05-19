<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration\Component;

use Mjml\Tests\Integration\AbstractIntegrationTest;

final class MjTextTest extends AbstractIntegrationTest
{
    public function testBasicTextRendersWithDefaultStyles(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text css-class="txt">Hello World</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertSame('Ubuntu, Helvetica, Arial, sans-serif', $this->extractStyleValue($html, 'td.txt > div', 'font-family'));
        self::assertSame('13px', $this->extractStyleValue($html, 'td.txt > div', 'font-size'));
        self::assertSame('1', $this->extractStyleValue($html, 'td.txt > div', 'line-height'));
        self::assertSame('left', $this->extractStyleValue($html, 'td.txt > div', 'text-align'));
        self::assertSame('#000000', $this->extractStyleValue($html, 'td.txt > div', 'color'));
        self::assertStringContainsString('Hello World', $html);
    }

    public function testCustomAttributes(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text color="#ff0000" font-size="20px" font-weight="bold" align="center" css-class="styled">Styled Text</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertSame('#ff0000', $this->extractStyleValue($html, 'td.styled > div', 'color'));
        self::assertSame('20px', $this->extractStyleValue($html, 'td.styled > div', 'font-size'));
        self::assertSame('bold', $this->extractStyleValue($html, 'td.styled > div', 'font-weight'));
        self::assertSame('center', $this->extractStyleValue($html, 'td.styled > div', 'text-align'));
    }

    public function testHeightAttributeWrapsContentInTable(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text height="100px" css-class="tall">Tall Text</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // The height attribute wraps content in conditional comment table for Outlook
        self::assertStringContainsString('height="100px"', $html);
        self::assertStringContainsString('vertical-align:top;height:100px;', $html);
        // The div itself should contain height:100px in its style
        self::assertStringContainsString('height:100px;', $html);
    }

    public function testCssClassIsApplied(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text css-class="my-text">Classed Text</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // css-class is applied to the wrapping td, not the div itself
        $nodes = $this->querySelectorAll($html, 'td.my-text');
        self::assertGreaterThan(0, $nodes->length, 'css-class "my-text" should be applied');
    }
}
