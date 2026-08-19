<?php

declare(strict_types=1);

namespace MjmlPHP\Context;

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
        public readonly ?string $gutter = null,
        public readonly ?string $direction = null,
        public readonly bool $isInGroup = false,
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
            gutter: $this->gutter,
            direction: $this->direction,
            isInGroup: $this->isInGroup,
        );
    }
}
