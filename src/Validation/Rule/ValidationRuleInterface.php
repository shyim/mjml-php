<?php

declare(strict_types=1);

namespace MjmlPHP\Validation\Rule;

use MjmlPHP\Component\ComponentRegistry;
use MjmlPHP\Parser\Node;
use MjmlPHP\Validation\ValidationError;

interface ValidationRuleInterface
{
    /**
     * @return ValidationError|list<ValidationError>|null
     */
    public function validate(Node $node, ComponentRegistry $registry): ValidationError|array|null;
}
