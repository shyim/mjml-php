<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration\Component;

use Mjml\Tests\Integration\AbstractIntegrationTest;

final class MjSpacerTest extends AbstractIntegrationTest
{
    public function testHeightAttributeCreatesDiv(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-spacer height="50px" />
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // The spacer div uses the height both as height and line-height
        // We need to find the div that has the spacer styles (not the column div)
        self::assertStringContainsString('height:50px;', $html);
        self::assertStringContainsString('line-height:50px;', $html);
        // The spacer renders &#8202; (hair space) inside the div
        self::assertStringContainsString('&#8202;', $html);
    }

    public function testDefaultHeight(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-spacer />
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // Default height is 20px
        self::assertStringContainsString('height:20px;', $html);
        self::assertStringContainsString('line-height:20px;', $html);
    }
}
