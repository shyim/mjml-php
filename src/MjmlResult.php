<?php

declare(strict_types=1);

namespace Mjml;

use Mjml\Parser\Node;
use Mjml\Validation\ValidationError;

final readonly class MjmlResult
{
    /**
     * @param list<ValidationError> $errors Validation errors collected in Soft mode (empty in Strict/Skip)
     */
    public function __construct(
        public string $html,
        public Node $ast,
        public array $errors = [],
    ) {}
}
