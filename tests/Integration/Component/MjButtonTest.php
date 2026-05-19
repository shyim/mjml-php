<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration\Component;

use Mjml\Tests\Integration\AbstractIntegrationTest;

final class MjButtonTest extends AbstractIntegrationTest
{
    public function testButtonRendersAsTableTdAStructure(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-button href="https://example.com">Click Me</mj-button>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // Should have table > tbody > tr > td > a structure
        $nodes = $this->querySelectorAll($html, 'table > tbody > tr > td > a');
        self::assertGreaterThan(0, $nodes->length, 'Button should render as table/td/a structure');
        self::assertSame('https://example.com', $this->extractAttribute($html, 'td > a', 'href'));
        self::assertStringContainsString('Click Me', $html);
    }

    public function testBackgroundColorAppliedToTd(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-button href="https://example.com" background-color="#ff5500">Button</mj-button>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // background-color is applied as 'background' on the td with role=presentation
        self::assertSame('#ff5500', $this->extractStyleValue($html, 'td[role="presentation"]', 'background'));
    }

    public function testColorAppliedToATag(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-button href="https://example.com" color="#00ff00">Green Button</mj-button>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertSame('#00ff00', $this->extractStyleValue($html, 'a', 'color'));
    }

    public function testBorderRadiusApplied(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-button href="https://example.com" border-radius="10px">Rounded</mj-button>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // border-radius is applied to both td and a
        self::assertSame('10px', $this->extractStyleValue($html, 'td[role="presentation"]', 'border-radius'));
        self::assertSame('10px', $this->extractStyleValue($html, 'a', 'border-radius'));
    }

    public function testWithoutHrefRendersAsPInsteadOfA(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-button>No Link</mj-button>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // Should render p instead of a
        $pNodes = $this->querySelectorAll($html, 'td > p');
        self::assertGreaterThan(0, $pNodes->length, 'Without href, button should render as <p> tag');

        $aNodes = $this->querySelectorAll($html, 'td > a');
        self::assertSame(0, $aNodes->length, 'Without href, there should be no <a> tag');
    }
}
