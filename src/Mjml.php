<?php

declare(strict_types=1);

namespace Shyim\Mjml;

use Shyim\Mjml\Component\ComponentRegistry;
use Shyim\Mjml\Renderer\RenderingPipeline;

final class Mjml
{
    private ComponentRegistry $registry;

    public function __construct(?MjmlOptions $options = null)
    {
        $this->registry = ComponentRegistry::withDefaults();
        $this->options = $options ?? new MjmlOptions();
    }

    private MjmlOptions $options;

    /**
     * Register a custom component class.
     *
     * @param class-string<\Shyim\Mjml\Component\ComponentInterface> $componentClass
     */
    public function registerComponent(string $componentClass): self
    {
        $this->registry->register($componentClass);

        return $this;
    }

    /**
     * Render MJML string to HTML.
     */
    public function toHtml(string $mjml): MjmlResult
    {
        $pipeline = new RenderingPipeline($this->registry, $this->options);

        return $pipeline->execute($mjml);
    }

    /**
     * Static convenience method to render MJML to HTML.
     */
    public static function render(string $mjml, ?MjmlOptions $options = null): MjmlResult
    {
        $instance = new self($options);

        return $instance->toHtml($mjml);
    }
}
