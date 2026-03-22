<?php

declare(strict_types=1);

namespace Shyim\Mjml;

use Shyim\Mjml\Parser\Node;
use Shyim\Mjml\Validation\ValidationError;

final readonly class MjmlResult
{
    /**
     * @param list<ValidationError> $errors
     */
    public function __construct(
        public string $html,
        public Node $json,
        public array $errors,
    ) {}
}
