<?php

declare(strict_types=1);

namespace Mjml\Validation;

use Mjml\MjmlException;

final class ValidationException extends MjmlException
{
    /**
     * @param list<ValidationError> $errors
     */
    public function __construct(
        public readonly array $errors,
    ) {
        $messages = array_map(
            static fn(ValidationError $e): string => (string) $e,
            $errors,
        );

        parent::__construct("MJML validation failed:\n- " . implode("\n- ", $messages));
    }
}
