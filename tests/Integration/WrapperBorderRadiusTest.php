<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration;

final class WrapperBorderRadiusTest extends AbstractIntegrationTest
{
    public function testWrapperBorderRadiusAndOverflowAndBorderCollapse(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-wrapper border="1px solid red" border-radius="10px">
              <mj-section>
                <mj-column>
                  <mj-text font-size="20px" color="#F45E43" font-family="helvetica">Hello World</mj-text>
                </mj-column>
              </mj-section>
            </mj-wrapper>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // border-radius on the table td and the outer div
        $borderRadiusValues = $this->collectStyleValues(
            $html,
            'body > div > div > table:first-child > tbody > tr > td, body > div > div',
            'border-radius',
        );
        self::assertSame(['10px', '10px'], $borderRadiusValues);

        // overflow: hidden on the outer div
        $overflowValues = $this->collectStyleValues($html, 'body > div > div', 'overflow');
        self::assertSame(['hidden'], $overflowValues);

        // border-collapse: separate on the table
        $borderCollapseValues = $this->collectStyleValues(
            $html,
            'body > div > div > table:first-child',
            'border-collapse',
        );
        self::assertSame(['separate'], $borderCollapseValues);
    }
}
