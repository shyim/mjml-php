<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration;

final class AccordionFontFamilyTest extends AbstractIntegrationTest
{
    public function testAccordionFontFamilyInheritance(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-accordion css-class="my-accordion-1" font-family="serif">
                  <mj-accordion-element>
                    <mj-accordion-title>Why use an accordion?</mj-accordion-title>
                    <mj-accordion-text>
                        Because emails with a lot of content are most of the time a very bad experience on mobile, mj-accordion comes handy when you want to deliver a lot of information in a concise way.
                    </mj-accordion-text>
                  </mj-accordion-element>
                </mj-accordion>
              </mj-column>
            </mj-section>
            <mj-section>
              <mj-column>
                <mj-accordion css-class="my-accordion-2" font-family="serif">
                  <mj-accordion-element font-family="sans-serif">
                    <mj-accordion-title font-family="monospace">Why use an accordion?</mj-accordion-title>
                    <mj-accordion-text font-family="monospace">
                        Because emails with a lot of content are most of the time a very bad experience on mobile, mj-accordion comes handy when you want to deliver a lot of information in a concise way.
                    </mj-accordion-text>
                  </mj-accordion-element>
                </mj-accordion>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        // First accordion: both title and text td should inherit font-family "serif"
        $accordion1Values = $this->collectStyleValues(
            $html,
            '.my-accordion-1 .mj-accordion-title td:first-child, .my-accordion-1 .mj-accordion-content td:first-child',
            'font-family',
        );

        // Second accordion: title and text override to "monospace"
        $accordion2Values = $this->collectStyleValues(
            $html,
            '.my-accordion-2 .mj-accordion-title td:first-child, .my-accordion-2 .mj-accordion-content td:first-child',
            'font-family',
        );

        $allValues = array_merge($accordion1Values, $accordion2Values);
        self::assertSame(['serif', 'serif', 'monospace', 'monospace'], $allValues);
    }
}
