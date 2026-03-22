<?php

declare(strict_types=1);

namespace Shyim\Mjml\Renderer\PostProcessor;

interface PostProcessorInterface
{
    public function process(string $html): string;
}
