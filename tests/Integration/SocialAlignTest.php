<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration;

final class SocialAlignTest extends AbstractIntegrationTest
{
    public function testSocialElementAlignRendersCorrectTextAlign(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-social mode="vertical">
                  <mj-social-element name="facebook" href="https://mjml.io/" icon-position="right" align="right" css-class="my-social-element">
                    Facebook
                  </mj-social-element>
                </mj-social>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        $textAlignValues = $this->collectStyleValues(
            $html,
            '.my-social-element > td:first-child',
            'text-align',
        );
        self::assertSame(['right'], $textAlignValues);
    }
}
