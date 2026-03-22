<?php

declare(strict_types=1);

namespace Shyim\Mjml\Validation\Rule;

use Shyim\Mjml\Component\ComponentRegistry;
use Shyim\Mjml\Parser\Node;
use Shyim\Mjml\Validation\ValidationError;

interface ValidationRuleInterface
{
    /**
     * @return ValidationError|list<ValidationError>|null
     */
    public function validate(Node $node, ComponentRegistry $registry): ValidationError|array|null;
}
