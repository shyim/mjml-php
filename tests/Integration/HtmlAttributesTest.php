<?php

declare(strict_types=1);

namespace Mjml\Tests\Integration;

final class HtmlAttributesTest extends AbstractIntegrationTest
{
    public function testHtmlAttributesPlacedCorrectly(): void
    {
        $mjml = '
<mjml>
  <mj-head>
    <mj-html-attributes>
      <mj-selector path=".text div">
        <mj-html-attribute name="data-id">42</mj-html-attribute>
      </mj-selector>
      <mj-selector path=".image td">
        <mj-html-attribute name="data-name">43</mj-html-attribute>
      </mj-selector>
    </mj-html-attributes>
  </mj-head>
  <mj-body>
    <mj-raw>{ if item < 5 }</mj-raw>
    <mj-section css-class="section">
      <mj-column>
        <mj-raw>{ if item > 10 }</mj-raw>
        <mj-text css-class="text">
          Hello World! { item }
        </mj-text>
        <mj-raw>{ end if }</mj-raw>
        <mj-text css-class="text">
          Hello World! { item + 1 }
        </mj-text>
        <mj-image css-class="image" src="https://via.placeholder.com/150x30"/>
      </mj-column>
    </mj-section>
    <mj-raw>{ end if }</mj-raw>
  </mj-body>
</mjml>';

        $html = $this->renderMjml($mjml);

        // Custom data-id attributes on .text div elements
        $dataIdValues = $this->collectAttributeValues($html, '.text div', 'data-id');
        self::assertSame(['42', '42'], $dataIdValues);

        // Custom data-name attribute on .image td elements
        $dataNameValues = $this->collectAttributeValues($html, '.image td', 'data-name');
        self::assertSame(['43'], $dataNameValues);

        // Templating syntax should be preserved and in correct order
        $expected = [
            '{ if item < 5 }',
            'class="section"',
            '{ if item > 10 }',
            'class="text"',
            '{ item }',
            '{ end if }',
            '{ item + 1 }',
        ];

        $indexes = [];
        foreach ($expected as $str) {
            $pos = strpos($html, $str);
            self::assertNotFalse($pos, 'Expected string not found in output: ' . $str);
            $indexes[] = $pos;
        }

        // Verify order is preserved (indexes should be sorted)
        $sorted = $indexes;
        sort($sorted);
        self::assertSame($sorted, $indexes, 'Mj-raws should keep same positions');
    }
}
