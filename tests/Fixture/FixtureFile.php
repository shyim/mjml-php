<?php

declare(strict_types=1);

namespace Shyim\Mjml\Tests\Fixture;

use Shyim\Mjml\MjmlOptions;
use Shyim\Mjml\Validation\ValidationLevel;

final readonly class FixtureFile
{
    /**
     * @param array<string, string> $sections
     */
    private function __construct(
        public string $path,
        public array $sections,
    ) {}

    public static function fromFile(string $path): self
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Could not read fixture: ' . $path);
        }

        $sections = self::parseSections($content);

        return new self($path, $sections);
    }

    public function mjml(): string
    {
        return $this->requiredSection('MJML');
    }

    public function hasErrors(): bool
    {
        return isset($this->sections['ERRORS']);
    }

    public function hasException(): bool
    {
        return isset($this->sections['EXCEPTION']);
    }

    /**
     * @return class-string<\Throwable>
     */
    public function expectedExceptionClass(): string
    {
        $lines = $this->nonEmptyLines('EXCEPTION');
        $firstLine = $lines[0] ?? '';
        $class = str_contains($firstLine, '\\') ? $firstLine : '\\' . $firstLine;

        if ($firstLine !== '' && class_exists($class) && is_a($class, \Throwable::class, true)) {
            return $class;
        }

        return \Throwable::class;
    }

    /**
     * @return list<string>
     */
    public function expectedExceptionMessages(): array
    {
        $lines = $this->nonEmptyLines('EXCEPTION');
        if ($lines === []) {
            return [];
        }

        $class = str_contains($lines[0], '\\') ? $lines[0] : '\\' . $lines[0];
        if (class_exists($class) && is_a($class, \Throwable::class, true)) {
            array_shift($lines);
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    public function expectedHtmlFragments(): array
    {
        return $this->nonEmptyLines('HTML');
    }

    public function expectedRawHtmlFragment(): ?string
    {
        return $this->rawSection('HTML-RAW');
    }

    /**
     * @return list<string>
     */
    public function unexpectedHtmlFragments(): array
    {
        return $this->nonEmptyLines('HTML-NOT');
    }

    public function unexpectedRawHtmlFragment(): ?string
    {
        return $this->rawSection('HTML-NOT-RAW');
    }

    /**
     * @return list<string>
     */
    public function expectedErrors(): array
    {
        return $this->nonEmptyLines('ERRORS');
    }

    public function options(): MjmlOptions
    {
        $options = $this->parseOptions();
        $fixtureDir = dirname($this->path);

        $validationLevel = $options['validationLevel'] ?? ($this->hasErrors() ? 'strict' : 'skip');
        $filePath = $options['filePath'] ?? null;
        if ($filePath !== null && !$this->isAbsolutePath($filePath)) {
            $filePath = $fixtureDir . DIRECTORY_SEPARATOR . $filePath;
        }

        $includePath = null;
        if (isset($options['includePath'])) {
            $includePath = [];
            foreach (explode(',', $options['includePath']) as $path) {
                $path = trim($path);
                if ($path === '') {
                    continue;
                }
                $includePath[] = $this->isAbsolutePath($path)
                    ? $path
                    : $fixtureDir . DIRECTORY_SEPARATOR . $path;
            }
        }

        return new MjmlOptions(
            validationLevel: ValidationLevel::from($validationLevel),
            minify: ($options['minify'] ?? 'false') === 'true',
            beautify: ($options['beautify'] ?? 'false') === 'true',
            keepComments: ($options['keepComments'] ?? 'true') === 'true',
            language: $options['language'] ?? 'und',
            dir: $options['dir'] ?? 'auto',
            filePath: $filePath,
            ignoreIncludes: ($options['ignoreIncludes'] ?? 'true') === 'true',
            includePath: $includePath,
        );
    }

    private function requiredSection(string $name): string
    {
        if (!isset($this->sections[$name])) {
            throw new \RuntimeException(sprintf('Fixture %s is missing --%s-- section.', $this->path, $name));
        }

        return trim($this->sections[$name]);
    }

    /**
     * @return array<string, string>
     */
    private static function parseSections(string $content): array
    {
        $lines = preg_split('/\R/', $content);
        if ($lines === false) {
            return [];
        }

        $sections = [];
        $current = null;
        $buffer = [];

        foreach ($lines as $line) {
            if (preg_match('/^--([A-Z0-9_-]+)--\s*$/', $line, $matches)) {
                if ($current !== null) {
                    $sections[$current] = trim(implode("\n", $buffer));
                }

                $current = $matches[1];
                $buffer = [];
                continue;
            }

            if ($current !== null) {
                $buffer[] = $line;
            }
        }

        if ($current !== null) {
            $sections[$current] = trim(implode("\n", $buffer));
        }

        if ($sections !== []) {
            return $sections;
        }

        // Legacy shorthand format: MJML, delimiter line, expected HTML fragments.
        $parts = preg_split('/^\s*----\s*$/m', $content, 2);
        if ($parts !== false && count($parts) === 2) {
            return [
                'MJML' => trim($parts[0]),
                'HTML' => trim($parts[1]),
            ];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function nonEmptyLines(string $section): array
    {
        if (!isset($this->sections[$section])) {
            return [];
        }

        $lines = preg_split('/\R/', $this->sections[$section]);
        if ($lines === false) {
            return [];
        }

        $result = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $result[] = $line;
            }
        }

        return $result;
    }

    private function rawSection(string $section): ?string
    {
        if (!isset($this->sections[$section])) {
            return null;
        }

        return $this->sections[$section];
    }

    /**
     * @return array<string, string>
     */
    private function parseOptions(): array
    {
        if (!isset($this->sections['OPTIONS'])) {
            return [];
        }

        $options = [];
        foreach ($this->nonEmptyLines('OPTIONS') as $line) {
            [$key, $value] = array_pad(explode(':', $line, 2), 2, '');
            $options[trim($key)] = trim($value);
        }

        return $options;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:/', $path) === 1;
    }
}
