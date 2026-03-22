<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration\Component;

use Shyim\Mjml\Tests\Integration\AbstractIntegrationTest;

final class MjColumnTest extends AbstractIntegrationTest
{
    public function testRendersWithCorrectWidthClass(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text>Single Column</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // Single column = 100% width, so class should be mj-column-per-100
        $nodes = $this->querySelectorAll($html, 'div.mj-column-per-100');
        self::assertGreaterThan(0, $nodes->length, 'Single column should have mj-column-per-100 class');
    }

    public function testMultipleColumnsAutoDistributeWidth(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text>Column 1</mj-text>
              </mj-column>
              <mj-column>
                <mj-text>Column 2</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // Two columns = 50% each
        $nodes = $this->querySelectorAll($html, 'div.mj-column-per-50');
        self::assertSame(2, $nodes->length, 'Two columns should each have mj-column-per-50 class');
    }

    public function testExplicitWidthIsRespected(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column width="33.33%">
                <mj-text>Narrow Column</mj-text>
              </mj-column>
              <mj-column width="66.67%">
                <mj-text>Wide Column</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        $narrowNodes = $this->querySelectorAll($html, 'div.mj-column-per-33-33');
        self::assertSame(1, $narrowNodes->length, 'Narrow column should have mj-column-per-33-33 class');

        $wideNodes = $this->querySelectorAll($html, 'div.mj-column-per-66-67');
        self::assertSame(1, $wideNodes->length, 'Wide column should have mj-column-per-66-67 class');
    }

    public function testVerticalAlignIsApplied(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column vertical-align="middle">
                <mj-text>Middle Aligned</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertSame('middle', $this->extractStyleValue($html, 'div.mj-column-per-100', 'vertical-align'));
    }

    public function testBackgroundColorIsApplied(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column background-color="#cccccc">
                <mj-text>Gray Column</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // background-color is applied to the inner table
        self::assertStringContainsString('background-color:#cccccc;', $html);
    }
}
