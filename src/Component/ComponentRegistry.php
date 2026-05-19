<?php

declare(strict_types=1);

namespace Mjml\Component;

final class ComponentRegistry
{
    /** @var array<string, class-string<ComponentInterface>> */
    private array $components = [];

    /** @var array<string, list<string>> Parent tag => allowed child tags */
    private array $dependencies = [];

    /**
     * Register a component class.
     *
     * @param class-string<ComponentInterface> $componentClass
     */
    public function register(string $componentClass): self
    {
        $this->components[$componentClass::getComponentName()] = $componentClass;

        return $this;
    }

    /**
     * Get a component class by tag name.
     *
     * @return class-string<ComponentInterface>|null
     */
    public function get(string $tagName): ?string
    {
        return $this->components[$tagName] ?? null;
    }

    public function has(string $tagName): bool
    {
        return isset($this->components[$tagName]);
    }

    /**
     * Check if a tag name corresponds to an ending tag component.
     */
    public function isEndingTag(string $tagName): bool
    {
        $class = $this->components[$tagName] ?? null;

        if ($class === null) {
            return false;
        }

        return $class::isEndingTag();
    }

    /**
     * Check if a tag name corresponds to a raw element.
     */
    public function isRawElement(string $tagName): bool
    {
        $class = $this->components[$tagName] ?? null;

        if ($class === null) {
            return false;
        }

        return $class::isRawElement();
    }

    /**
     * Set parent-child dependency rules.
     *
     * @param array<string, list<string>> $dependencies
     */
    public function setDependencies(array $dependencies): self
    {
        $this->dependencies = $dependencies;

        return $this;
    }

    /**
     * Get allowed children for a parent tag.
     *
     * @return list<string>|null
     */
    public function getAllowedChildren(string $parentTag): ?array
    {
        return $this->dependencies[$parentTag] ?? null;
    }

    /**
     * Get all registered component tag names.
     *
     * @return list<string>
     */
    public function getTagNames(): array
    {
        return array_keys($this->components);
    }

    /**
     * Create a registry with all default MJML components.
     */
    public static function withDefaults(): self
    {
        $registry = new self();

        // Head components
        $registry->register(\Mjml\Component\Head\MjHead::class);
        $registry->register(\Mjml\Component\Head\MjHeadTitle::class);
        $registry->register(\Mjml\Component\Head\MjHeadPreview::class);
        $registry->register(\Mjml\Component\Head\MjHeadFont::class);
        $registry->register(\Mjml\Component\Head\MjHeadStyle::class);
        $registry->register(\Mjml\Component\Head\MjHeadBreakpoint::class);
        $registry->register(\Mjml\Component\Head\MjHeadAttributes::class);
        $registry->register(\Mjml\Component\Head\MjHeadHtmlAttributes::class);

        // Body components
        $registry->register(\Mjml\Component\Body\MjBody::class);
        $registry->register(\Mjml\Component\Body\MjSection::class);
        $registry->register(\Mjml\Component\Body\MjColumn::class);
        $registry->register(\Mjml\Component\Body\MjWrapper::class);
        $registry->register(\Mjml\Component\Body\MjGroup::class);
        $registry->register(\Mjml\Component\Body\MjText::class);
        $registry->register(\Mjml\Component\Body\MjImage::class);
        $registry->register(\Mjml\Component\Body\MjButton::class);
        $registry->register(\Mjml\Component\Body\MjDivider::class);
        $registry->register(\Mjml\Component\Body\MjSpacer::class);
        $registry->register(\Mjml\Component\Body\MjTable::class);
        $registry->register(\Mjml\Component\Body\MjRaw::class);
        $registry->register(\Mjml\Component\Body\MjHero::class);
        $registry->register(\Mjml\Component\Body\MjCarousel::class);
        $registry->register(\Mjml\Component\Body\MjCarouselImage::class);
        $registry->register(\Mjml\Component\Body\MjAccordion::class);
        $registry->register(\Mjml\Component\Body\MjAccordionElement::class);
        $registry->register(\Mjml\Component\Body\MjAccordionTitle::class);
        $registry->register(\Mjml\Component\Body\MjAccordionText::class);
        $registry->register(\Mjml\Component\Body\MjNavbar::class);
        $registry->register(\Mjml\Component\Body\MjNavbarLink::class);
        $registry->register(\Mjml\Component\Body\MjSocial::class);
        $registry->register(\Mjml\Component\Body\MjSocialElement::class);

        // Dependencies (parent → allowed children)
        $registry->setDependencies([
            'mjml' => ['mj-head', 'mj-body'],
            'mj-head' => [
                'mj-attributes', 'mj-breakpoint', 'mj-font',
                'mj-html-attributes', 'mj-preview', 'mj-style', 'mj-title', 'mj-raw',
            ],
            'mj-body' => ['mj-section', 'mj-wrapper', 'mj-hero', 'mj-raw'],
            'mj-section' => ['mj-column', 'mj-group', 'mj-raw'],
            'mj-wrapper' => ['mj-section', 'mj-raw'],
            'mj-group' => ['mj-column'],
            'mj-column' => [
                'mj-accordion', 'mj-button', 'mj-carousel', 'mj-divider',
                'mj-image', 'mj-raw', 'mj-social', 'mj-spacer', 'mj-table',
                'mj-text', 'mj-navbar', 'mj-hero',
            ],
            'mj-hero' => [
                'mj-accordion', 'mj-button', 'mj-carousel', 'mj-divider',
                'mj-image', 'mj-navbar', 'mj-raw', 'mj-social', 'mj-spacer',
                'mj-table', 'mj-text',
            ],
            'mj-accordion' => ['mj-accordion-element'],
            'mj-accordion-element' => ['mj-accordion-title', 'mj-accordion-text'],
            'mj-navbar' => ['mj-navbar-link', 'mj-raw'],
            'mj-carousel' => ['mj-carousel-image'],
            'mj-social' => ['mj-social-element'],
            'mj-attributes' => ['mj-all', 'mj-class', 'mj-text', 'mj-section', 'mj-column', 'mj-image', 'mj-button', 'mj-divider', 'mj-spacer', 'mj-table', 'mj-raw', 'mj-hero', 'mj-carousel', 'mj-carousel-image', 'mj-accordion', 'mj-accordion-element', 'mj-accordion-title', 'mj-accordion-text', 'mj-navbar', 'mj-navbar-link', 'mj-social', 'mj-social-element', 'mj-wrapper', 'mj-group', 'mj-body'],
            'mj-html-attributes' => ['mj-selector'],
            'mj-selector' => ['mj-html-attribute'],
        ]);

        return $registry;
    }
}
