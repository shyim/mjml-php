<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration;

final class WrapperGapTest extends AbstractIntegrationTest
{
    public function testWrapperGapRendersCorrectMarginTopOnChildSections(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-wrapper gap="20px" css-class="my-wrapper" background-color="#000">
              <mj-section css-class="my-section" background-color="#f45e43" padding="10px">
                <mj-column>
                  <mj-text>Section 1</mj-text>
                </mj-column>
              </mj-section>
              <mj-section css-class="my-section" background-color="#ccc" padding="10px">
                <mj-column>
                  <mj-text>Section 2</mj-text>
                </mj-column>
              </mj-section>
              <mj-section css-class="my-section" background-color="#333" padding="10px">
                <mj-column>
                  <mj-text color="#fff">Section 3</mj-text>
                </mj-column>
              </mj-section>
            </mj-wrapper>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // The first section should NOT have margin-top, second and third should have 20px
        $marginTopValues = [];
        $nodes = $this->querySelectorAll($html, '.my-section');

        for ($i = 0; $i < $nodes->length; $i++) {
            $style = $nodes->item($i)?->attributes?->getNamedItem('style')?->nodeValue ?? '';
            $marginTop = $this->extractCssProperty($style, 'margin-top');
            if ($marginTop !== null) {
                $marginTopValues[] = $marginTop;
            }
        }

        self::assertSame(['20px', '20px'], $marginTopValues);
    }
}
