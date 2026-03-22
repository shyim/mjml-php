<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shyim\Mjml\Component\HeadComponent;
use Shyim\Mjml\Mjml;
use Shyim\Mjml\MjmlOptions;
use Shyim\Mjml\Validation\ValidationLevel;

final class LazyHeadStyleTest extends TestCase
{
    public function testStyleCalledWithCorrectBreakpoint(): void
    {
        $mjml = new Mjml(new MjmlOptions(validationLevel: ValidationLevel::Skip));
        $mjml->registerComponent(MjHeadComponentWithFunctionStyle::class);

        $result = $mjml->toHtml('
        <mjml>
          <mj-head>
            <mj-head-component-with-function-style />
            <mj-breakpoint width="300px" />
          </mj-head>
          <mj-body>
          </mj-body>
        </mjml>
        ');

        $expectedCss = '@media only screen and (max-width:300px) { h1 { font-size: 20px; } }';

        self::assertStringContainsString($expectedCss, $result->html);
    }
}

final class MjHeadComponentWithFunctionStyle extends HeadComponent
{
    public static function getComponentName(): string
    {
        return 'mj-head-component-with-function-style';
    }

    public static function allowedAttributes(): array
    {
        return [];
    }

    public static function defaultAttributes(): array
    {
        return [];
    }

    public static function isEndingTag(): bool
    {
        return true;
    }

    public function handler(): void
    {
        $this->globalContext->addComponentHeadStyle(
            static fn(string $breakpoint): string => '@media only screen and (max-width:' . $breakpoint . ') { h1 { font-size: 20px; } }',
        );
    }
}
