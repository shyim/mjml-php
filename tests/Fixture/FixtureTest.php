<?php

declare(strict_types=1);

namespace Mjml\Tests\Fixture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Mjml\Mjml;
use Mjml\Validation\ValidationException;

final class FixtureTest extends TestCase
{
    #[DataProvider('fixtureProvider')]
    public function testFixture(string $file): void
    {
        $fixture = FixtureFile::fromFile($file);

        if ($fixture->hasErrors()) {
            try {
                Mjml::render($fixture->mjml(), $fixture->options());
                self::fail('Expected validation failure for fixture: ' . $file);
            } catch (ValidationException $e) {
                foreach ($fixture->expectedErrors() as $expectedError) {
                    self::assertStringContainsString($expectedError, $e->getMessage());
                }
            }

            return;
        }

        if ($fixture->hasException()) {
            try {
                Mjml::render($fixture->mjml(), $fixture->options());
                self::fail('Expected exception for fixture: ' . $file);
            } catch (\Throwable $e) {
                self::assertInstanceOf($fixture->expectedExceptionClass(), $e);

                foreach ($fixture->expectedExceptionMessages() as $expectedMessage) {
                    self::assertStringContainsString($expectedMessage, $e->getMessage());
                }
            }

            return;
        }

        $result = Mjml::render($fixture->mjml(), $fixture->options());

        foreach ($fixture->expectedHtmlFragments() as $expectedHtml) {
            self::assertStringContainsString($expectedHtml, $result->html);
        }

        if ($fixture->expectedRawHtmlFragment() !== null) {
            self::assertStringContainsString($fixture->expectedRawHtmlFragment(), $result->html);
        }

        foreach ($fixture->unexpectedHtmlFragments() as $unexpectedHtml) {
            self::assertStringNotContainsString($unexpectedHtml, $result->html);
        }

        if ($fixture->unexpectedRawHtmlFragment() !== null) {
            self::assertStringNotContainsString($fixture->unexpectedRawHtmlFragment(), $result->html);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function fixtureProvider(): iterable
    {
        $baseDir = __DIR__ . '/../Fixtures';
        if (!is_dir($baseDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || $file->getExtension() !== 'test') {
                continue;
            }

            $path = $file->getPathname();
            yield str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path) => [$path];
        }
    }
}
