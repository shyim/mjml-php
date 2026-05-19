<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration\Component;

use Mjml\Tests\Integration\AbstractIntegrationTest;

final class MjImageTest extends AbstractIntegrationTest
{
    public function testBasicImageRendersWithCorrectAttributes(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-image src="https://example.com/image.png" alt="Test Image" width="300px" />
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertSame('https://example.com/image.png', $this->extractAttribute($html, 'img', 'src'));
        self::assertSame('Test Image', $this->extractAttribute($html, 'img', 'alt'));
        self::assertSame('300', $this->extractAttribute($html, 'img', 'width'));
    }

    public function testImageWrappedInAnchorWhenHrefPresent(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-image src="https://example.com/image.png" href="https://example.com" />
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertSame('https://example.com', $this->extractAttribute($html, 'a', 'href'));
        self::assertSame('_blank', $this->extractAttribute($html, 'a', 'target'));
        // img should be inside the anchor
        $nodes = $this->querySelectorAll($html, 'a > img');
        self::assertGreaterThan(0, $nodes->length, 'img should be wrapped in an anchor tag');
    }

    public function testFluidOnMobileClassIsAdded(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-image src="https://example.com/image.png" fluid-on-mobile="true" />
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        $nodes = $this->querySelectorAll($html, 'table.mj-full-width-mobile');
        self::assertGreaterThan(0, $nodes->length, 'table should have mj-full-width-mobile class');

        $tdNodes = $this->querySelectorAll($html, 'td.mj-full-width-mobile');
        self::assertGreaterThan(0, $tdNodes->length, 'td should have mj-full-width-mobile class');
    }

    public function testSrcsetAndSizesAttributesArePassedThrough(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-image src="https://example.com/image.png" srcset="https://example.com/small.png 300w, https://example.com/large.png 600w" sizes="(max-width: 600px) 300px, 600px" />
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        self::assertSame(
            'https://example.com/small.png 300w, https://example.com/large.png 600w',
            $this->extractAttribute($html, 'img', 'srcset'),
        );
        self::assertSame(
            '(max-width: 600px) 300px, 600px',
            $this->extractAttribute($html, 'img', 'sizes'),
        );
    }
}
