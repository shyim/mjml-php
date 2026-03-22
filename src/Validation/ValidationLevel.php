<?php

declare(strict_types=1);

namespace Shyim\Mjml\Validation;

enum ValidationLevel: string
{
    case Skip = 'skip';
    case Strict = 'strict';
}
