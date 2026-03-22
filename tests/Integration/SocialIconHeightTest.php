<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration;

final class SocialIconHeightTest extends AbstractIntegrationTest
{
    public function testSocialIconHeightRendersCorrectly(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column css-class="my-social-element">
                <mj-social icon-height="40px">
                  <mj-social-element name="facebook" href="https://mjml.io/" css-class="my-social-element">
                    Facebook
                  </mj-social-element>
                </mj-social>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // height style value should be 40px
        $heightValues = $this->collectStyleValues(
            $html,
            '.my-social-element > td > table > tbody > tr > td',
            'height',
        );
        self::assertSame(['40px'], $heightValues);

        // img should NOT have a height attribute
        $imgHeightValues = $this->collectAttributeValues(
            $html,
            '.my-social-element > td > table > tbody > tr > td img',
            'height',
        );

        foreach ($imgHeightValues as $value) {
            self::assertNull($value, 'img should not have a height attribute');
        }
    }
}
