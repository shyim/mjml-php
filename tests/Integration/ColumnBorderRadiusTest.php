<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration;

final class ColumnBorderRadiusTest extends AbstractIntegrationTest
{
    public function testColumnBorderRadiusAndBorderCollapse(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column border-radius="50px" inner-border-radius="40px" padding="50px" border="5px solid #000" inner-border="5px solid #666">
                <mj-text>Hello World</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // border-radius: outer td = 50px, inner table = 40px
        $borderRadiusValues = $this->collectStyleValues(
            $html,
            '.mj-column-per-100 > table > tbody > tr > td, .mj-column-per-100 > table > tbody > tr > td > table',
            'border-radius',
        );
        self::assertSame(['50px', '40px'], $borderRadiusValues);

        // border-collapse should be separate on both
        $borderCollapseValues = $this->collectStyleValues(
            $html,
            '.mj-column-per-100 > table > tbody > tr > td, .mj-column-per-100 > table > tbody > tr > td > table',
            'border-collapse',
        );
        self::assertSame(['separate', 'separate'], $borderCollapseValues);
    }
}
