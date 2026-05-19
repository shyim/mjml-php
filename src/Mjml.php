<?php

declare(strict_types=1);

namespace Mjml;

use Mjml\Cache\NodeCacheInterface;
use Mjml\Component\ComponentRegistry;
use Mjml\Hook\PipelineHooks;
use Mjml\Renderer\RenderingPipeline;

final class Mjml
{
    private ComponentRegistry $registry;

    private MjmlOptions $options;

    private ?NodeCacheInterface $cache;

    /** @var list<PipelineHooks> */
    private array $hooks = [];

    public function __construct(?MjmlOptions $options = null, ?NodeCacheInterface $cache = null)
    {
        $this->registry = ComponentRegistry::withDefaults();
        $this->options = $options ?? new MjmlOptions();
        $this->cache = $cache;
    }

    /**
     * Register a custom component class.
     *
     * @param class-string<\Mjml\Component\ComponentInterface> $componentClass
     */
    public function registerComponent(string $componentClass): self
    {
        $this->registry->register($componentClass);

        return $this;
    }

    /**
     * Register a pipeline hook for extending the render lifecycle.
     */
    public function addHook(PipelineHooks $hook): self
    {
        $this->hooks[] = $hook;

        return $this;
    }

    /**
     * Render MJML string to HTML.
     *
     * @throws \Mjml\Parser\ParseException If the MJML markup is malformed.
     * @throws \Mjml\Validation\ValidationException If validation fails in Strict mode.
     */
    public function toHtml(string $mjml): MjmlResult
    {
        $pipeline = new RenderingPipeline($this->registry, $this->options, $this->cache, $this->hooks);

        return $pipeline->execute($mjml);
    }

    /**
     * Static convenience method to render MJML to HTML.
     *
     * @param list<class-string<\Mjml\Component\ComponentInterface>> $components
     *        Optional extra component classes to register before rendering.
     *
     * @throws \Mjml\Parser\ParseException If the MJML markup is malformed.
     * @throws \Mjml\Validation\ValidationException If validation fails in Strict mode.
     */
    public static function render(string $mjml, ?MjmlOptions $options = null, array $components = []): MjmlResult
    {
        $instance = new self($options);

        foreach ($components as $componentClass) {
            $instance->registerComponent($componentClass);
        }

        return $instance->toHtml($mjml);
    }
}
