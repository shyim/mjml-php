<?php

declare(strict_types=1);

namespace Shyim\Mjml\Context;

final class GlobalContext
{
    public string $title = '';
    public string $preview = '';
    public string $breakpoint = '480px';
    public string $backgroundColor = '';
    public string $beforeDoctype = '';
    public string $language = 'und';
    public string $dir = 'auto';
    public bool $forceOWADesktop = false;
    public ?string $bodyId = null;
    public ?string $bodyCssClass = null;

    /** @var array<string, string> Font name => URL */
    public array $fonts = [];

    /** @var list<string> CSS style blocks for <style> tags */
    public array $styles = [];

    /** @var list<string> CSS to be inlined */
    public array $inlineStyles = [];

    /** @var array<string, string> CSS class => media query rule */
    public array $mediaQueries = [];

    /** @var array<string, array<string, string>> Class name => [attr => value] (from mj-class tag attributes) */
    public array $classes = [];

    /** @var array<string, array<string, string>> Component tag => [attr => value] */
    public array $defaultAttributes = [];

    /** @var array<string, array<string, array<string, string>>> Class name => component tag => [attr => value] */
    public array $classesDefault = [];

    /** @var array<string, array<string, string>> Selector => [attr => value] */
    public array $htmlAttributes = [];

    /** @var list<string> Raw HTML for <head> */
    public array $headRaw = [];

    /** @var array<string, callable> Component name => style callable */
    public array $headStyle = [];

    /** @var list<callable> Component head style callables */
    public array $componentsHeadStyle = [];

    public function addMediaQuery(string $className, string $rule): void
    {
        $this->mediaQueries[$className] = $rule;
    }

    public function addHeadStyle(string $name, callable $styleFunc): void
    {
        $this->headStyle[$name] = $styleFunc;
    }

    public function addComponentHeadStyle(callable $styleFunc): void
    {
        $this->componentsHeadStyle[] = $styleFunc;
    }
}
