<?php

declare(strict_types=1);

namespace Shyim\Mjml\Validation;

enum ValidationLevel: string
{
    case Skip = 'skip';
    case Soft = 'soft';
    case Strict = 'strict';
}
