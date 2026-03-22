<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration;

final class HtmlCommentsTest extends AbstractIntegrationTest
{
    public function testHtmlCommentsPreserveWhitespace(): void
    {
        $mjml = '
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text>
                <p>View source to see comments below</p>
                <!-- comment with standard spaces -->
                <br>
                <!--comment without spaces-->
                <br>
                <!--     comment with 5 spaces     -->
                </mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>';

        $html = $this->renderMjml($mjml);

        $expected = [
            '<!-- comment with standard spaces -->',
            '<!--comment without spaces-->',
            '<!--     comment with 5 spaces     -->',
        ];

        foreach ($expected as $comment) {
            self::assertStringContainsString($comment, $html, 'Comment syntax should be unaltered: ' . $comment);
        }
    }
}
