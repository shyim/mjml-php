<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use Shyim\Mjml\Component\ComponentRegistry;
use Shyim\Mjml\MjmlOptions;
use Shyim\Mjml\Parser\MjmlParser;
use Shyim\Mjml\Validation\ValidationLevel;

final class MjmlParserTest extends TestCase
{
    private MjmlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new MjmlParser(ComponentRegistry::withDefaults());
    }

    public function testSpecialCharacters(): void
    {
        $mjml = <<<'MJML'
        <mjml>
          <mj-body>
            <mj-section background-color="#CCCCCC" full-width="full-width">
              <mj-button href="https://mjml.io?encodedUrl=https%3A%2F%2Fmjml.io&amp;coin=coi">
                Blu &amp; end
                &amp;
                lorem
              </mj-button>
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $this->parser->parse($mjml);

        self::assertSame('mjml', $node->tagName);
        self::assertCount(1, $node->children);

        $body = $node->children[0];
        self::assertSame('mj-body', $body->tagName);
        self::assertCount(1, $body->children);

        $section = $body->children[0];
        self::assertSame('mj-section', $section->tagName);
        self::assertSame('#CCCCCC', $section->attributes['background-color']);
        self::assertSame('full-width', $section->attributes['full-width']);
        self::assertCount(1, $section->children);

        $button = $section->children[0];
        self::assertSame('mj-button', $button->tagName);
        // DOMDocument decodes &amp; in attributes to &
        self::assertSame('https://mjml.io?encodedUrl=https%3A%2F%2Fmjml.io&coin=coi', $button->attributes['href']);
        // Content is extracted before XML parsing, so &amp; is preserved as-is
        self::assertStringContainsString('Blu &amp; end', $button->content);
        self::assertStringContainsString('&amp;', $button->content);
        self::assertStringContainsString('lorem', $button->content);
    }

    public function testEncodedUrlInContent(): void
    {
        $mjml = <<<'MJML'
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-button href="test">https%3A%2F%2Fmjml.io</mj-button>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $this->parser->parse($mjml);
        $body = $node->children[0];
        $section = $body->children[0];
        $column = $section->children[0];
        $button = $column->children[0];

        self::assertSame('mj-button', $button->tagName);
        self::assertSame('https%3A%2F%2Fmjml.io', trim($button->content));
    }

    public function testSimilarTags(): void
    {
        // mj-text-test-wrapper is not a registered component, so it will be parsed
        // as a regular element with mj-text children
        $mjml = <<<'MJML'
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text>MJML</mj-text>
                <mj-text attr="val">FTW</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $this->parser->parse($mjml);
        $body = $node->children[0];
        $section = $body->children[0];
        $column = $section->children[0];

        self::assertCount(2, $column->children);

        $text1 = $column->children[0];
        self::assertSame('mj-text', $text1->tagName);
        self::assertSame('MJML', trim($text1->content));

        $text2 = $column->children[1];
        self::assertSame('mj-text', $text2->tagName);
        self::assertSame('val', $text2->attributes['attr']);
        self::assertSame('FTW', trim($text2->content));
    }

    public function testSimilarTagsReversedOrder(): void
    {
        $mjml = <<<'MJML'
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text attr="val">FTW</mj-text>
                <mj-text>MJML</mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $this->parser->parse($mjml);
        $body = $node->children[0];
        $section = $body->children[0];
        $column = $section->children[0];

        self::assertCount(2, $column->children);

        $text1 = $column->children[0];
        self::assertSame('mj-text', $text1->tagName);
        self::assertSame('val', $text1->attributes['attr']);
        self::assertSame('FTW', trim($text1->content));

        $text2 = $column->children[1];
        self::assertSame('mj-text', $text2->tagName);
        self::assertSame('MJML', trim($text2->content));
    }

    public function testSelfClosingTagsInAttributes(): void
    {
        $mjml = <<<'MJML'
        <mjml>
          <mj-head>
            <mj-attributes>
              <mj-text color="blue" />
              <mj-text font-size="40px" />
            </mj-attributes>
          </mj-head>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text>
                  Hello !
                </mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $this->parser->parse($mjml);

        self::assertSame('mjml', $node->tagName);
        self::assertCount(2, $node->children);

        // mj-head
        $head = $node->children[0];
        self::assertSame('mj-head', $head->tagName);
        self::assertCount(1, $head->children);

        // mj-attributes with two self-closing mj-text children
        $attributes = $head->children[0];
        self::assertSame('mj-attributes', $attributes->tagName);
        self::assertCount(2, $attributes->children);

        $attrText1 = $attributes->children[0];
        self::assertSame('mj-text', $attrText1->tagName);
        self::assertSame('blue', $attrText1->attributes['color']);

        $attrText2 = $attributes->children[1];
        self::assertSame('mj-text', $attrText2->tagName);
        self::assertSame('40px', $attrText2->attributes['font-size']);

        // mj-body
        $body = $node->children[1];
        self::assertSame('mj-body', $body->tagName);

        $section = $body->children[0];
        $column = $section->children[0];
        $text = $column->children[0];
        self::assertSame('mj-text', $text->tagName);
        self::assertSame('Hello !', trim($text->content));
    }

    public function testMultilineAttributes(): void
    {
        $mjml = <<<'MJML'
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-text
                    padding-left="16px"

                    padding-right="16px">
                    <a href="https://www.test.com" style="color: #60788c">View blog ]]post</a>
                </mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $this->parser->parse($mjml);
        $body = $node->children[0];
        $section = $body->children[0];
        $column = $section->children[0];
        $text = $column->children[0];

        self::assertSame('mj-text', $text->tagName);
        self::assertSame('16px', $text->attributes['padding-left']);
        self::assertSame('16px', $text->attributes['padding-right']);
        self::assertStringContainsString('<a href="https://www.test.com" style="color: #60788c">View blog ]]post</a>', $text->content);
    }

    public function testSelfClosingEndingTags(): void
    {
        $mjml = <<<'MJML'
        <mjml>
          <mj-head>
            <mj-title></mj-title>
            <mj-attributes>
              <mj-text font-size="27px" />
            </mj-attributes>
          </mj-head>
          <mj-body>
            <mj-section>
              <mj-column width="65%">
                <mj-text mj-class="small" align="left" font-family="Helvetica" color="#000000" padding-top="20px">
                  coin
                  <a href="https://test" style="text-decoration:underline;color:#336666;font-weight:bold" class="mobile-small-letters">Majors and Minors</a>
                  bla
                  <a href="https://test" style="text-decoration:underline;color:#336666;font-weight:bold" class="mobile-small-letters">Majors and Minors</a>
                  <mj-raw>
                    coin
                  </mj-raw>
                </mj-text>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $this->parser->parse($mjml);

        self::assertSame('mjml', $node->tagName);
        self::assertCount(2, $node->children);

        // mj-head
        $head = $node->children[0];
        self::assertSame('mj-head', $head->tagName);
        self::assertCount(2, $head->children);

        $title = $head->children[0];
        self::assertSame('mj-title', $title->tagName);

        $attrs = $head->children[1];
        self::assertSame('mj-attributes', $attrs->tagName);
        self::assertCount(1, $attrs->children);
        self::assertSame('mj-text', $attrs->children[0]->tagName);
        self::assertSame('27px', $attrs->children[0]->attributes['font-size']);

        // mj-body
        $body = $node->children[1];
        $section = $body->children[0];
        $column = $section->children[0];

        self::assertSame('65%', $column->attributes['width']);
        self::assertCount(1, $column->children);

        $text = $column->children[0];
        self::assertSame('mj-text', $text->tagName);
        self::assertSame('small', $text->attributes['mj-class']);
        self::assertSame('left', $text->attributes['align']);
        self::assertSame('Helvetica', $text->attributes['font-family']);
        self::assertSame('#000000', $text->attributes['color']);
        self::assertSame('20px', $text->attributes['padding-top']);

        // Content should contain all the inner HTML including links and mj-raw
        self::assertStringContainsString('coin', $text->content);
        self::assertStringContainsString('Majors and Minors', $text->content);
        self::assertStringContainsString('bla', $text->content);
        self::assertStringContainsString('<mj-raw>', $text->content);
    }

    public function testInclude(): void
    {
        $fixtureDir = __DIR__ . '/Fixtures';
        $options = new MjmlOptions(
            validationLevel: ValidationLevel::Skip,
            ignoreIncludes: false,
            filePath: $fixtureDir,
        );
        $parser = new MjmlParser(ComponentRegistry::withDefaults(), $options);

        $mjml = <<<MJML
        <mjml>
          <mj-body>
            <mj-section>
              <mj-include path="incl.mjml" />
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $parser->parse($mjml, $fixtureDir . '/test.mjml');

        self::assertSame('mjml', $node->tagName);

        $body = $node->children[0];
        self::assertSame('mj-body', $body->tagName);

        $section = $body->children[0];
        self::assertSame('mj-section', $section->tagName);
        self::assertCount(1, $section->children);

        // The included file contains mj-column with two mj-text children
        $column = $section->children[0];
        self::assertSame('mj-column', $column->tagName);
        self::assertCount(2, $column->children);

        $text1 = $column->children[0];
        self::assertSame('mj-text', $text1->tagName);
        self::assertSame('22px', $text1->attributes['font-size']);
        self::assertStringContainsString('COIN', $text1->content);
        self::assertStringContainsString('<a src="test">aze</a>', $text1->content);

        $text2 = $column->children[1];
        self::assertSame('mj-text', $text2->tagName);
        self::assertSame('22px', $text2->attributes['font-size']);
        self::assertStringContainsString('COIN2', $text2->content);
        self::assertStringContainsString('<a src="test">aze2</a>', $text2->content);
    }

    public function testSingleOpeningTagInEndingTagSingleLine(): void
    {
        $mjml = <<<'MJML'
        <mjml>
          <mj-body>
            <mj-section>
              <mj-column>
                <mj-raw test="test"><?php endif ?></mj-raw>
              </mj-column>
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $this->parser->parse($mjml);
        $body = $node->children[0];
        $section = $body->children[0];
        $column = $section->children[0];

        self::assertCount(1, $column->children);

        $raw = $column->children[0];
        self::assertSame('mj-raw', $raw->tagName);
        self::assertSame('test', $raw->attributes['test']);
        self::assertSame('<?php endif ?>', trim($raw->content));
    }

    public function testIncludeIgnoredByDefault(): void
    {
        $fixtureDir = __DIR__ . '/Fixtures';
        // Default options have ignoreIncludes: true
        $parser = new MjmlParser(ComponentRegistry::withDefaults());

        $mjml = <<<MJML
        <mjml>
          <mj-body>
            <mj-section>
              <mj-include path="incl.mjml" />
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $parser->parse($mjml, $fixtureDir . '/test.mjml');

        $body = $node->children[0];
        $section = $body->children[0];
        // Include was ignored — section should have no children
        self::assertCount(0, $section->children);
    }

    public function testIncludeDeniedPathTraversal(): void
    {
        $fixtureDir = __DIR__ . '/Fixtures';
        $options = new MjmlOptions(
            validationLevel: ValidationLevel::Skip,
            ignoreIncludes: false,
            filePath: $fixtureDir,
        );
        $parser = new MjmlParser(ComponentRegistry::withDefaults(), $options);

        $mjml = <<<MJML
        <mjml>
          <mj-body>
            <mj-section>
              <mj-include path="../outside.mjml" />
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $parser->parse($mjml, $fixtureDir . '/test.mjml');

        $body = $node->children[0];
        $section = $body->children[0];
        // Path traversal should produce a denial comment
        self::assertCount(1, $section->children);
        self::assertSame('mj-raw', $section->children[0]->tagName);
        self::assertStringContainsString('mj-include denied', $section->children[0]->content);
    }

    public function testIncludeDeniedAbsolutePath(): void
    {
        $fixtureDir = __DIR__ . '/Fixtures';
        $options = new MjmlOptions(
            validationLevel: ValidationLevel::Skip,
            ignoreIncludes: false,
            filePath: $fixtureDir,
        );
        $parser = new MjmlParser(ComponentRegistry::withDefaults(), $options);

        $mjml = <<<MJML
        <mjml>
          <mj-body>
            <mj-section>
              <mj-include path="/etc/passwd" />
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $parser->parse($mjml, $fixtureDir . '/test.mjml');

        $body = $node->children[0];
        $section = $body->children[0];
        self::assertCount(1, $section->children);
        self::assertSame('mj-raw', $section->children[0]->tagName);
        self::assertStringContainsString('mj-include denied', $section->children[0]->content);
    }

    public function testIncludeCssType(): void
    {
        $fixtureDir = __DIR__ . '/Fixtures';
        // Create a temp CSS file
        $cssFile = $fixtureDir . '/test-include.css';
        file_put_contents($cssFile, '.test { color: red; }');

        try {
            $options = new MjmlOptions(
                validationLevel: ValidationLevel::Skip,
                ignoreIncludes: false,
                filePath: $fixtureDir,
            );
            $parser = new MjmlParser(ComponentRegistry::withDefaults(), $options);

            $mjml = <<<MJML
            <mjml>
              <mj-body>
                <mj-section>
                  <mj-include path="test-include.css" type="css" />
                </mj-section>
              </mj-body>
            </mjml>
            MJML;

            $node = $parser->parse($mjml, $fixtureDir . '/test.mjml');

            // CSS include should create mj-head with mj-style child
            $head = $node->findFirstByTag('mj-head');
            self::assertNotNull($head);
            self::assertCount(1, $head->children);
            self::assertSame('mj-style', $head->children[0]->tagName);
            self::assertStringContainsString('.test { color: red; }', $head->children[0]->content);
        } finally {
            @unlink($cssFile);
        }
    }

    public function testIncludeHtmlType(): void
    {
        $fixtureDir = __DIR__ . '/Fixtures';
        // Create a temp HTML file
        $htmlFile = $fixtureDir . '/test-include.html';
        file_put_contents($htmlFile, '<div>Custom HTML</div>');

        try {
            $options = new MjmlOptions(
                validationLevel: ValidationLevel::Skip,
                ignoreIncludes: false,
                filePath: $fixtureDir,
            );
            $parser = new MjmlParser(ComponentRegistry::withDefaults(), $options);

            $mjml = <<<MJML
            <mjml>
              <mj-body>
                <mj-section>
                  <mj-include path="test-include.html" type="html" />
                </mj-section>
              </mj-body>
            </mjml>
            MJML;

            $node = $parser->parse($mjml, $fixtureDir . '/test.mjml');

            $body = $node->children[0];
            $section = $body->children[0];
            // HTML include should become mj-raw
            self::assertCount(1, $section->children);
            self::assertSame('mj-raw', $section->children[0]->tagName);
            self::assertStringContainsString('<div>Custom HTML</div>', $section->children[0]->content);
        } finally {
            @unlink($htmlFile);
        }
    }

    public function testIncludeDeniedNullByte(): void
    {
        $fixtureDir = __DIR__ . '/Fixtures';
        $options = new MjmlOptions(
            validationLevel: ValidationLevel::Skip,
            ignoreIncludes: false,
            filePath: $fixtureDir,
        );
        $parser = new MjmlParser(ComponentRegistry::withDefaults(), $options);

        $mjml = <<<MJML
        <mjml>
          <mj-body>
            <mj-section>
              <mj-include path="incl.mjml%00" />
            </mj-section>
          </mj-body>
        </mjml>
        MJML;

        $node = $parser->parse($mjml, $fixtureDir . '/test.mjml');

        $body = $node->children[0];
        $section = $body->children[0];
        self::assertCount(1, $section->children);
        self::assertSame('mj-raw', $section->children[0]->tagName);
        self::assertStringContainsString('mj-include denied', $section->children[0]->content);
    }

    public function testIncludeWithIncludePath(): void
    {
        $rootDir = sys_get_temp_dir() . '/mjml-include-root-' . bin2hex(random_bytes(4));
        $extraDir = sys_get_temp_dir() . '/mjml-include-extra-' . bin2hex(random_bytes(4));
        mkdir($rootDir);
        mkdir($extraDir);
        file_put_contents($extraDir . '/extra.mjml', '<mj-text>From extra dir</mj-text>');

        try {
            $relativePath = $this->relativePath($rootDir, $extraDir . '/extra.mjml');

            $deniedOptions = new MjmlOptions(
                validationLevel: ValidationLevel::Skip,
                ignoreIncludes: false,
                filePath: $rootDir,
            );
            $deniedParser = new MjmlParser(ComponentRegistry::withDefaults(), $deniedOptions);

            $mjml = <<<MJML
            <mjml>
              <mj-body>
                <mj-section>
                  <mj-include path="{$relativePath}" />
                </mj-section>
              </mj-body>
            </mjml>
            MJML;

            $deniedNode = $deniedParser->parse($mjml, $rootDir . '/template.mjml');
            $deniedSection = $deniedNode->children[0]->children[0];
            self::assertSame('mj-raw', $deniedSection->children[0]->tagName);
            self::assertStringContainsString('mj-include denied', $deniedSection->children[0]->content);

            $allowedOptions = new MjmlOptions(
                validationLevel: ValidationLevel::Skip,
                ignoreIncludes: false,
                filePath: $rootDir,
                includePath: [$extraDir],
            );
            $allowedParser = new MjmlParser(ComponentRegistry::withDefaults(), $allowedOptions);

            $allowedNode = $allowedParser->parse($mjml, $rootDir . '/template.mjml');
            $allowedSection = $allowedNode->children[0]->children[0];
            self::assertCount(1, $allowedSection->children);
            self::assertSame('mj-text', $allowedSection->children[0]->tagName);
            self::assertSame('From extra dir', trim($allowedSection->children[0]->content));
        } finally {
            @unlink($extraDir . '/extra.mjml');
            @rmdir($extraDir);
            @rmdir($rootDir);
        }
    }

    public function testCommentsAreKeptByDefault(): void
    {
        $mjml = <<<'MJML'
        <mjml>
          <mj-body>
            <!-- root comment -->
            <mj-section />
          </mj-body>
        </mjml>
        MJML;

        $node = $this->parser->parse($mjml);
        $body = $node->children[0];

        self::assertSame('mj-raw', $body->children[0]->tagName);
        self::assertSame('<!-- root comment -->', $body->children[0]->content);
    }

    public function testCommentsCanBeRemoved(): void
    {
        $parser = new MjmlParser(ComponentRegistry::withDefaults(), new MjmlOptions(
            validationLevel: ValidationLevel::Skip,
            keepComments: false,
        ));

        $mjml = <<<'MJML'
        <mjml>
          <mj-body>
            <!-- root comment -->
            <mj-section />
          </mj-body>
        </mjml>
        MJML;

        $node = $parser->parse($mjml);
        $body = $node->children[0];

        self::assertCount(1, $body->children);
        self::assertSame('mj-section', $body->children[0]->tagName);
    }

    private function relativePath(string $from, string $to): string
    {
        $from = explode(DIRECTORY_SEPARATOR, trim(realpath($from) ?: $from, DIRECTORY_SEPARATOR));
        $to = explode(DIRECTORY_SEPARATOR, trim(realpath($to) ?: $to, DIRECTORY_SEPARATOR));

        while ($from !== [] && $to !== [] && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        return str_repeat('..' . DIRECTORY_SEPARATOR, count($from)) . implode(DIRECTORY_SEPARATOR, $to);
    }

    public function testEmptyTagIsParsedCorrectly(): void
    {
        $mjml = <<<'MJML'
        <mjml>
          <mj-head>
            <mj-title></mj-title>
          </mj-head>
        </mjml>
        MJML;

        $node = $this->parser->parse($mjml);
        $head = $node->children[0];

        self::assertSame('mj-head', $head->tagName);
        self::assertCount(1, $head->children);

        $title = $head->children[0];
        self::assertSame('mj-title', $title->tagName);
        self::assertSame('', $title->content);
    }
}
