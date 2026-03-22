<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration;

final class CarouselTest extends AbstractIntegrationTest
{
    public function testCarouselThumbnailsDisplayNoneWhenSupported(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-carousel thumbnails="supported">
                  <mj-carousel-image src="https://placehold.co/450x300/333/ccc/png" />
                  <mj-carousel-image src="https://placehold.co/450x300/ccc/000/png" />
                  <mj-carousel-image src="https://placehold.co/450x300/f45e43/fff/png" />
                </mj-carousel>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        $displayValues = $this->collectStyleValues($html, '.mj-carousel-thumbnail', 'display');

        self::assertSame(['none', 'none', 'none'], $displayValues);
    }
}
