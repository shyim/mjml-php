<?php

declare(strict_types=1);

namespace Shyim\Mjml;

use Shyim\Mjml\Parser\Node;

final readonly class MjmlResult
{
    public function __construct(
        public string $html,
        public Node $json,
    ) {}
}
