<?php

declare(strict_types=1);

namespace Mjml\Parser;

/**
 * Collects and stores libxml parse errors for inspection.
 *
 * @internal
 */
final class LibXmlErrorCollector
{
    /** @var list<array{level: int, message: string, line: int, column: int}> */
    private array $errors = [];

    /** @var list<array{level: int, message: string, line: int, column: int}> */
    private static array $globalErrors = [];

    /**
     * Start capturing libxml errors.
     */
    public function start(): void
    {
        libxml_use_internal_errors(true);
    }

    /**
     * Collect any pending libxml errors and clear the buffer.
     * Restores the previous error-handling state.
     */
    public function collect(bool $restorePrevious = false): void
    {
        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();

        if ($restorePrevious) {
            libxml_use_internal_errors(false);
        }

        foreach ($xmlErrors as $error) {
            $entry = [
                'level' => $error->level,
                'message' => trim($error->message),
                'line' => $error->line,
                'column' => $error->column,
            ];
            $this->errors[] = $entry;
            self::$globalErrors[] = $entry;
        }
    }

    /**
     * @return list<array{level: int, message: string, line: int, column: int}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasFatalErrors(): bool
    {
        foreach ($this->errors as $error) {
            if ($error['level'] === \LIBXML_ERR_FATAL) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{level: int, message: string, line: int, column: int}>
     */
    public static function drainGlobalErrors(): array
    {
        $errors = self::$globalErrors;
        self::$globalErrors = [];

        return $errors;
    }

    /**
     * @return list<string>
     */
    public function getErrorMessages(): array
    {
        return array_map(
            fn(array $e): string => sprintf(
                '[%s] line %d: %s',
                match ($e['level']) {
                    \LIBXML_ERR_WARNING => 'WARNING',
                    \LIBXML_ERR_ERROR => 'ERROR',
                    \LIBXML_ERR_FATAL => 'FATAL',
                    default => 'UNKNOWN',
                },
                $e['line'],
                $e['message'],
            ),
            $this->errors,
        );
    }
}
