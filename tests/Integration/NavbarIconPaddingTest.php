<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration;

final class NavbarIconPaddingTest extends AbstractIntegrationTest
{
    public function testNavbarIconPaddingValues(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-navbar hamburger="hamburger" ico-padding="20px" ico-padding-bottom="20px" ico-padding-left="30px" ico-padding-right="40px"  ico-padding-top="50px" >
                    <mj-navbar-link href="/gettings-started-onboard" color="#ffffff">Getting started</mj-navbar-link>
                    <mj-navbar-link href="/try-it-live" color="#ffffff">Try it live</mj-navbar-link>
                </mj-navbar>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        $expected = [
            'padding-bottom' => '20px',
            'padding-left' => '30px',
            'padding-right' => '40px',
            'padding-top' => '50px',
        ];

        foreach ($expected as $property => $expectedValue) {
            $values = $this->collectStyleValues($html, '.mj-menu-label', $property);
            self::assertSame(
                [$expectedValue],
                $values,
                sprintf('%s should be %s on navbar icon', $property, $expectedValue),
            );
        }
    }
}
