<?php

declare(strict_types=1);

namespace Mjml\Context;

/**
 * Mutable context bag that accumulates head-component output during the
 * head-processing phase and is consumed by the body renderer and skeleton.
 *
 * Scalar properties are private with setters that prevent accidental
 * overwrites. Collection properties (arrays) are exposed through dedicated
 * add/get methods so the append-only nature is explicit.
 */
final class GlobalContext
{
    // ── Scalar properties ────────────────────────────────────────────

    private string $title = '';
    private string $preview = '';
    private string $breakpoint = '480px';
    private string $backgroundColor = '';
    private string $beforeDoctype = '';
    private string $language = 'und';
    private string $dir = 'auto';
    private bool $forceOWADesktop = false;
    private ?string $bodyId = null;
    private ?string $bodyCssClass = null;

    // ── Collection properties ────────────────────────────────────────

    /** @var array<string, string> Font name => URL */
    private array $fonts = [];

    /** @var list<string> CSS style blocks for <style> tags */
    private array $styles = [];

    /** @var list<string> CSS to be inlined */
    private array $inlineStyles = [];

    /** @var array<string, string> CSS class => media query rule */
    private array $mediaQueries = [];

    /** @var array<string, array<string, string>> Class name => [attr => value] (from mj-class tag attributes) */
    private array $classes = [];

    /** @var array<string, array<string, string>> Component tag => [attr => value] */
    private array $defaultAttributes = [];

    /** @var array<string, array<string, array<string, string>>> Class name => component tag => [attr => value] */
    private array $classesDefault = [];

    /** @var array<string, array<string, string>> Selector => [attr => value] */
    private array $htmlAttributes = [];

    /** @var list<string> Raw HTML for <head> */
    private array $headRaw = [];

    /** @var array<string, callable> Component name => style callable */
    private array $headStyle = [];

    /** @var list<callable> Component head style callables */
    private array $componentsHeadStyle = [];

    // ── Scalar setters ───────────────────────────────────────────────

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
    public function setPreview(string $preview): void
    {
        $this->preview = $preview;
    }
    public function setBreakpoint(string $breakpoint): void
    {
        $this->breakpoint = $breakpoint;
    }
    public function setBackgroundColor(string $color): void
    {
        $this->backgroundColor = $color;
    }
    public function setBeforeDoctype(string $html): void
    {
        $this->beforeDoctype = $html;
    }
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }
    public function setDir(string $dir): void
    {
        $this->dir = $dir;
    }
    public function setForceOWADesktop(bool $force): void
    {
        $this->forceOWADesktop = $force;
    }
    public function setBodyId(?string $id): void
    {
        $this->bodyId = $id;
    }
    public function setBodyCssClass(?string $cssClass): void
    {
        $this->bodyCssClass = $cssClass;
    }

    // ── Scalar getters ───────────────────────────────────────────────

    public function getTitle(): string
    {
        return $this->title;
    }
    public function getPreview(): string
    {
        return $this->preview;
    }
    public function getBreakpoint(): string
    {
        return $this->breakpoint;
    }
    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }
    public function getBeforeDoctype(): string
    {
        return $this->beforeDoctype;
    }
    public function getLanguage(): string
    {
        return $this->language;
    }
    public function getDir(): string
    {
        return $this->dir;
    }
    public function getForceOWADesktop(): bool
    {
        return $this->forceOWADesktop;
    }
    public function getBodyId(): ?string
    {
        return $this->bodyId;
    }
    public function getBodyCssClass(): ?string
    {
        return $this->bodyCssClass;
    }

    // ── Collection setters / adders ──────────────────────────────────

    /** @param array<string, string> $fonts */
    public function setFonts(array $fonts): void
    {
        $this->fonts = $fonts;
    }
    public function addFont(string $name, string $url): void
    {
        $this->fonts[$name] = $url;
    }
    public function addStyle(string $css): void
    {
        $this->styles[] = $css;
    }
    public function addInlineStyle(string $css): void
    {
        $this->inlineStyles[] = $css;
    }

    /** @param array<string, string> $attributes */
    public function mergeDefaultAttributes(string $tag, array $attributes): void
    {
        $this->defaultAttributes[$tag] = array_merge(
            $this->defaultAttributes[$tag] ?? [],
            $attributes,
        );
    }

    /** @param array<string, string> $attributes */
    public function mergeClasses(string $className, array $attributes): void
    {
        $this->classes[$className] = array_merge(
            $this->classes[$className] ?? [],
            $attributes,
        );
    }

    /** @param array<string, array<string, string>> $childDefaults */
    public function mergeClassesDefault(string $className, array $childDefaults): void
    {
        $this->classesDefault[$className] = array_merge(
            $this->classesDefault[$className] ?? [],
            $childDefaults,
        );
    }

    /** @param array<string, string> $attributes */
    public function mergeHtmlAttributes(string $selector, array $attributes): void
    {
        $this->htmlAttributes[$selector] = array_merge(
            $this->htmlAttributes[$selector] ?? [],
            $attributes,
        );
    }

    public function addHeadRaw(string $html): void
    {
        $this->headRaw[] = $html;
    }
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

    // ── Collection getters ───────────────────────────────────────────

    /** @return array<string, string> */
    public function getFonts(): array
    {
        return $this->fonts;
    }

    /** @return list<string> */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /** @return list<string> */
    public function getInlineStyles(): array
    {
        return $this->inlineStyles;
    }

    /** @return array<string, string> */
    public function getMediaQueries(): array
    {
        return $this->mediaQueries;
    }

    /** @return array<string, array<string, string>> */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /** @return array<string, array<string, string>> */
    public function getDefaultAttributes(): array
    {
        return $this->defaultAttributes;
    }

    /** @return array<string, array<string, array<string, string>>> */
    public function getClassesDefault(): array
    {
        return $this->classesDefault;
    }

    /** @return array<string, array<string, string>> */
    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    /** @return list<string> */
    public function getHeadRaw(): array
    {
        return $this->headRaw;
    }

    /** @return array<string, callable> */
    public function getHeadStyle(): array
    {
        return $this->headStyle;
    }

    /** @return list<callable> */
    public function getComponentsHeadStyle(): array
    {
        return $this->componentsHeadStyle;
    }
}
