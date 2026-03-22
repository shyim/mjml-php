<?php

declare(strict_types=1);

namespace Shyim\Mjml\Attribute;

enum AttributeType: string
{
    case String = 'string';
    case Color = 'color';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Unit = 'unit';
    case Enum = 'enum';
}
