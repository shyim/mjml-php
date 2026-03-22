<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration\Component;

use Shyim\Mjml\Tests\Integration\AbstractIntegrationTest;

final class MjDividerTest extends AbstractIntegrationTest
{
    public function testDividerRendersWithDefaultBorderTop(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-divider />
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // Default: solid 4px #000000
        self::assertSame('solid 4px #000000', $this->extractStyleValue($html, 'p', 'border-top'));
    }

    public function testCustomBorderAttributes(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-divider border-color="#ff0000" border-style="dashed" border-width="2px" />
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertSame('dashed 2px #ff0000', $this->extractStyleValue($html, 'p', 'border-top'));
    }

    public function testWidthAttributeSetsWidth(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-divider width="50%" />
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertSame('50%', $this->extractStyleValue($html, 'p', 'width'));
    }

    public function testOutlookConditionalCommentTableIsPresent(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-divider />
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertStringContainsString('<!--[if mso | IE]>', $html);
        self::assertStringContainsString('<![endif]-->', $html);
    }
}
