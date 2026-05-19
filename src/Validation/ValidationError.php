<?php

declare(strict_types=1);

namespace Mjml\Validation;

final readonly class ValidationError
{
    public function __construct(
        public string $message,
        public string $tagName = '',
        public int $line = 0,
        public string $type = 'error',
    ) {}

    public function __toString(): string
    {
        $prefix = $this->line > 0 ? "Line {$this->line}: " : '';
        $tag = $this->tagName !== '' ? " ({$this->tagName})" : '';

        return "{$prefix}{$this->message}{$tag}";
    }
}
