<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration;

final class AccordionPaddingTest extends AbstractIntegrationTest
{
    public function testAccordionPaddingOverrides(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-accordion>
                  <mj-accordion-element>
                    <mj-accordion-title padding="20px" padding-bottom="40px" padding-left="40px" padding-right="40px" padding-top="40px">Why use an accordion?</mj-accordion-title>
                    <mj-accordion-text padding="20px" padding-bottom="40px" padding-left="40px" padding-right="40px" padding-top="40px">
                        Because emails with a lot of content are most of the time a very bad experience on mobile, mj-accordion comes handy when you want to deliver a lot of information in a concise way.
                    </mj-accordion-text>
                  </mj-accordion-element>
                </mj-accordion>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        $selector = '.mj-accordion-title td:first-child, .mj-accordion-content td:first-child';

        $paddings = ['padding-left', 'padding-right', 'padding-top', 'padding-bottom'];

        foreach ($paddings as $padding) {
            $values = $this->collectStyleValues($html, $selector, $padding);
            self::assertSame(
                ['40px', '40px'],
                $values,
                sprintf('%s should be 40px on both accordion-title and accordion-text', $padding),
            );
        }
    }
}
