<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;

final class CliTest extends TestCase
{
    private string $bin;

    protected function setUp(): void
    {
        $this->bin = dirname(__DIR__, 3) . '/bin/mjml-php';
    }

    public function testRendersInputFileToStdout(): void
    {
        $tmpDir = $this->createTempDir();
        $input = $tmpDir . '/input.mjml';
        file_put_contents($input, $this->mjml('Hello CLI'));

        $command = PHP_BINARY . ' ' . escapeshellarg($this->bin) . ' ' . escapeshellarg($input);
        exec($command, $lines, $exitCode);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Hello CLI', implode("\n", $lines));
    }

    public function testRendersInputFileToOutputFile(): void
    {
        $tmpDir = $this->createTempDir();
        $input = $tmpDir . '/input.mjml';
        $output = $tmpDir . '/output.html';
        file_put_contents($input, $this->mjml('Hello Output'));

        $command = PHP_BINARY . ' ' . escapeshellarg($this->bin)
            . ' ' . escapeshellarg($input)
            . ' -o ' . escapeshellarg($output);
        exec($command, $lines, $exitCode);

        self::assertSame(0, $exitCode);
        self::assertFileExists($output);
        self::assertStringContainsString('Hello Output', (string) file_get_contents($output));
        self::assertSame([], $lines);
    }

    public function testReadsFromStdin(): void
    {
        $tmpDir = $this->createTempDir();
        $input = $tmpDir . '/stdin.mjml';
        file_put_contents($input, $this->mjml('Hello STDIN'));

        $command = PHP_BINARY . ' ' . escapeshellarg($this->bin) . ' < ' . escapeshellarg($input);
        exec($command, $lines, $exitCode);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Hello STDIN', implode("\n", $lines));
    }

    private function mjml(string $text): string
    {
        return <<<MJML
<mjml>
  <mj-body>
    <mj-section>
      <mj-column>
        <mj-text>{$text}</mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
MJML;
    }

    private function createTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/mjml-php-cli-' . bin2hex(random_bytes(4));
        mkdir($dir);

        return $dir;
    }
}
