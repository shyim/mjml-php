<?php

declare(strict_types=1);

namespace Mjml\Validation\Rule;

use Mjml\Component\ComponentRegistry;
use Mjml\Parser\Node;
use Mjml\Validation\ValidationError;

interface ValidationRuleInterface
{
    /**
     * @return ValidationError|list<ValidationError>|null
     */
    public function validate(Node $node, ComponentRegistry $registry): ValidationError|array|null;
}
