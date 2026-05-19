<?php

declare(strict_types=1);

namespace Mjml\Component;

abstract class HeadComponent extends AbstractComponent
{
    /**
     * Process this head component, modifying the GlobalContext.
     */
    abstract public function handler(): void;

    /**
     * Process all children as head components.
     */
    protected function handlerChildren(): void
    {
        foreach ($this->node->children as $child) {
            $componentClass = $this->registry->get($child->tagName);

            if ($componentClass === null) {
                continue;
            }

            $component = new $componentClass(
                $child,
                $this->globalContext,
                $this->renderContext,
                $this->registry,
            );

            if ($component instanceof self) {
                $component->handler();
            }
        }
    }
}
