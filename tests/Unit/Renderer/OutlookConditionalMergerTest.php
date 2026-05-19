<?php

declare(strict_types=1);

namespace Mjml\Tests\Unit\Renderer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Mjml\Renderer\PostProcessor\OutlookConditionalMerger;

final class OutlookConditionalMergerTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function mergeProvider(): iterable
    {
        yield 'adjacent conditionals are merged' => [
            '<![endif]--><!--[if mso | IE]>',
            '',
        ];

        yield 'adjacent conditionals with surrounding content' => [
            "\n    </tr>\n    <![endif]-->\n    <!--[if mso | IE]>\n    </td>",
            "\n    </tr>\n    \n    </td>",
        ];

        yield 'multiple adjacent conditionals in complex HTML' => [
            "</div>\n              <!--[if mso | IE]>\n            </td>\n          <![endif]-->\n              <!--[if mso | IE]>\n        </tr>\n      <![endif]-->\n              <!--[if mso | IE]>\n                  </table>\n                <![endif]-->\n            </td>\n          </tr>\n        </tbody>\n      </table>\n    </div>\n    <!--[if mso | IE]>\n          </td>",
            "</div>\n              <!--[if mso | IE]></td></tr></table><![endif]-->\n            </td>\n          </tr>\n        </tbody>\n      </table>\n    </div>\n    <!--[if mso | IE]>\n          </td>",
        ];
    }

    #[DataProvider('mergeProvider')]
    public function testMergeOutlookConditionals(string $input, string $expected): void
    {
        $result = OutlookConditionalMerger::merge($input);

        self::assertSame($expected, $result);
    }

    public function testMinifyOutlookConditionals(): void
    {
        $input = <<<'HTML'

      <div
         style=""
      >


      <!--[if mso | IE]>
      <table
         align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600"
      >
        <tr>
          <td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;">
      <![endif]-->


      <div  style="Margin:0px auto;max-width:600px;">

        <table
           align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"
        >
          <tbody>
            <tr>
              <td
                 style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;vertical-align:top;"
              >
                <!--[if mso | IE]>
                  <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <![endif]-->

      <!--[if mso | IE]>
        <tr>
      <![endif]-->

          <!--[if mso | IE]>
            <td
               class="" style="vertical-align:top;width:600px;"
            >
          <![endif]-->

      <div
         class="mj-column-per-100 mj-outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"
      >

      <table
         border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"
      >

            <tr>
              <td
                 align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;"
              >

      <div
         style="font-family:helvetica;font-size:20px;line-height:1;text-align:left;color:#F45E43;"
      >
        Hello World
      </div>

              </td>
            </tr>

      </table>

      </div>

          <!--[if mso | IE]>
            </td>
          <![endif]-->


      <!--[if mso | IE]>
        </tr>
      <![endif]-->

                <!--[if mso | IE]>
                  </table>
                <![endif]-->
              </td>
            </tr>
          </tbody>
        </table>

      </div>


      <!--[if mso | IE]>
          </td>
        </tr>
      </table>
      <![endif]-->


      </div>
HTML;

        $expected = <<<'HTML'

      <div
         style=""
      >


      <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->


      <div  style="Margin:0px auto;max-width:600px;">

        <table
           align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;"
        >
          <tbody>
            <tr>
              <td
                 style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;vertical-align:top;"
              >
                <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]-->

      <div
         class="mj-column-per-100 mj-outlook-group-fix" style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;"
      >

      <table
         border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%"
      >

            <tr>
              <td
                 align="left" style="font-size:0px;padding:10px 25px;word-break:break-word;"
              >

      <div
         style="font-family:helvetica;font-size:20px;line-height:1;text-align:left;color:#F45E43;"
      >
        Hello World
      </div>

              </td>
            </tr>

      </table>

      </div>

          <!--[if mso | IE]></td></tr></table><![endif]-->
              </td>
            </tr>
          </tbody>
        </table>

      </div>


      <!--[if mso | IE]></td></tr></table><![endif]-->


      </div>
HTML;

        $result = OutlookConditionalMerger::merge($input);

        self::assertSame($expected, $result);
    }
}
