<?php

declare(strict_types=1);

namespace Mjml\Context;

final class RenderContext
{
    public function __construct(
        public readonly string $containerWidth = '600px',
        public readonly bool $first = false,
        public readonly bool $last = false,
        public readonly int $index = 0,
        public readonly int $sibling = 0,
        public readonly int $nonRawSiblings = 0,
        public readonly ?string $sectionGap = null,
        public readonly float $columnGap = 0,
    ) {}

    public function withContainerWidth(string $containerWidth): self
    {
        return new self(
            containerWidth: $containerWidth,
            first: $this->first,
            last: $this->last,
            index: $this->index,
            sibling: $this->sibling,
            nonRawSiblings: $this->nonRawSiblings,
            sectionGap: $this->sectionGap,
            columnGap: $this->columnGap,
        );
    }
}
