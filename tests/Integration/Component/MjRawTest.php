<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration\Component;

use Shyim\Mjml\Tests\Integration\AbstractIntegrationTest;

final class MjRawTest extends AbstractIntegrationTest
{
    public function testRawHtmlContentPassesThroughUnmodified(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-raw>
                  <div class="custom-raw">Raw HTML Content</div>
                </mj-raw>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertStringContainsString('<div class="custom-raw">Raw HTML Content</div>', $html);
    }

    public function testPositionFileStartPlacesContentBeforeDoctype(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-raw position="file-start">
              <!-- file-start content -->
            </mj-raw>
            <mj-section>
              <mj-column>
                <mj-text>Body content</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // Content with position="file-start" should appear before <!doctype
        $fileStartPos = strpos($html, '<!-- file-start content -->');
        $doctypePos = strpos($html, '<!doctype');

        self::assertNotFalse($fileStartPos, 'file-start content should be present');
        self::assertNotFalse($doctypePos, 'doctype should be present');
        self::assertLessThan($doctypePos, $fileStartPos, 'file-start content should appear before doctype');
    }
}
