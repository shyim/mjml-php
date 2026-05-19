# MJML-PHP

Native PHP port of [MJML](https://mjml.io/) — the markup language for responsive HTML emails.

MJML-PHP converts MJML markup into responsive HTML that works across all major email clients, including Outlook. No Node.js dependency required.

## Requirements

- PHP 8.2+
- `ext-dom`
- `ext-libxml`

## Installation

```bash
composer require shyim/mjml-php
```

## Usage

### Basic

```php
use Mjml\Mjml;

$result = Mjml::render('<mjml>
  <mj-body>
    <mj-section>
      <mj-column>
        <mj-text>Hello World</mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>');

echo $result->html;
```

### With Options

```php
use Mjml\Mjml;
use Mjml\MjmlOptions;

$result = Mjml::render($mjml, new MjmlOptions(
    keepComments: true,
    minify: false,
    beautify: false,
    language: 'en',
    dir: 'ltr',
));

echo $result->html;
```

### Custom Components

```php
use Mjml\Mjml;

$mjml = new Mjml();
$mjml->registerComponent(MyCustomComponent::class);

$result = $mjml->toHtml('<mjml>...</mjml>');
```

## CLI

A small dependency-free CLI is exposed as Composer bin `mjml-php`:

```bash
vendor/bin/mjml-php email.mjml -o email.html
vendor/bin/mjml-php < email.mjml > email.html
```

Useful options:

```bash
vendor/bin/mjml-php email.mjml --validation-level=skip
vendor/bin/mjml-php email.mjml --process-includes --include-path=partials
```

Run `vendor/bin/mjml-php --help` for all options.

## Supported Components

### Structure
- `mj-body` — Email body container
- `mj-section` — Horizontal section with background image/color support and Outlook VML
- `mj-column` — Responsive column with auto-width distribution
- `mj-group` — Non-responsive column grouping
- `mj-wrapper` — Section wrapper with gap support

### Content
- `mj-text` — Styled text block
- `mj-image` — Responsive image with srcset/sizes and fluid-on-mobile
- `mj-button` — Call-to-action button
- `mj-divider` — Horizontal rule
- `mj-spacer` — Vertical spacing
- `mj-table` — HTML table passthrough
- `mj-raw` — Raw HTML passthrough

### Interactive
- `mj-accordion` — Expandable/collapsible sections (CSS-only, no JavaScript)
- `mj-carousel` — Image carousel (CSS radio-button technique)
- `mj-navbar` — Navigation bar with responsive hamburger menu
- `mj-hero` — Full-width hero section with VML background for Outlook
- `mj-social` — Social media icons (17 built-in networks)

### Head
- `mj-title` — Email title
- `mj-preview` — Preview text
- `mj-attributes` — Default attribute values and `mj-class` definitions
- `mj-font` — Web font imports
- `mj-style` — Custom CSS (inline or in `<style>` tag)
- `mj-breakpoint` — Mobile responsive breakpoint
- `mj-html-attributes` — Custom HTML attributes via CSS selectors

## Validation

MJML-PHP validates your markup and throws a `ValidationException` on errors:

```php
use Mjml\Validation\ValidationException;
use Mjml\Validation\ValidationLevel;

try {
    $result = Mjml::render($mjml);
} catch (ValidationException $e) {
    echo $e->getMessage();

    foreach ($e->errors as $error) {
        echo $error; // "Line 5: Attribute 'colr' is not allowed on mj-text (mj-text)"
    }
}
```

- **Strict** (default) — Validate and throw `ValidationException` on errors
- **Soft** — Validate but do not throw; errors are exposed via `$result->errors`
- **Skip** — No validation

```php
$result = Mjml::render($mjml, new MjmlOptions(
    validationLevel: ValidationLevel::Soft,
));

foreach ($result->errors as $error) {
    error_log((string) $error);
}
```

## Exception Model

All library exceptions extend `Mjml\MjmlException`, so you can catch them with a single `catch`:

```php
use Mjml\MjmlException;
use Mjml\Parser\ParseException;
use Mjml\Validation\ValidationException;

try {
    $result = Mjml::render($mjml);
} catch (ValidationException $e) {
    // Markup-level validation failures
} catch (ParseException $e) {
    // Circular includes, broken mj-include references, etc.
} catch (MjmlException $e) {
    // Any other library failure
}
```

`MjmlException` extends `\RuntimeException`, so existing `catch (\RuntimeException $e)` blocks still work.

## Security

**Input is trusted.** MJML markup is treated as a template authored by you, not as end-user input. Do not concatenate untrusted strings into MJML markup — attribute values flow into the rendered HTML without HTML-escaping, the same as the official JS MJML.

Defensive measures the renderer already applies:

- URL attributes (`href`, `src`, `background`, `action`, `formaction`, `poster`) pass through a scheme allowlist. `javascript:`, `vbscript:`, `file:`, and similar are rewritten to `#`. Only `http`, `https`, `mailto`, `tel`, `sms`, `ftp`, `cid`, anchor fragments, protocol-relative URLs, and relative paths pass through. `data:image/*` is allowed for inline images.
- `mj-font` URLs that are not `http`/`https`/protocol-relative are dropped instead of being emitted into `<link>` / `@import`.
- `mj-include` is **disabled by default** (unlike the JS MJML CLI). When enabled (`ignoreIncludes: false`), included paths are jailed under the current file's directory plus any explicit `includePath` roots, with `realpath` resolution, null-byte / URL-encoded-traversal rejection, and circular-include detection.
- libxml is invoked with `LIBXML_NONET` (no network access). Under PHP 8 / libxml ≥ 2.9, external entities are not resolved by default, so this parser is not vulnerable to XXE.

If you must interpolate user data into MJML, escape it yourself before passing it to the renderer (`htmlspecialchars` for text content; URL-encode parameters you put into `href` query strings).

## MJML Compatibility

This is a native PHP port aligned with MJML 5.2.1. The HTML output is tested against the original JavaScript implementation using snapshot tests to ensure identical rendering. A CI job re-renders the snapshot fixtures with `mjml@5.2.1` and fails on drift, so if the upstream JS package publishes a patch you may see CI failures — open an issue and regenerate the fixtures.

## Limitations

- **CSS `@import` inlining**: The CSS inliner does not resolve `@import` directives found in inline style blocks. This matches the behavior of the JS MJML reference implementation for email-safe output.
- **CSS shorthand parsing**: Only `padding`, `margin`, and `border` shorthands are fully supported for width calculation. More exotic shorthand properties (e.g., `border-radius` with `/` syntax) are passed through as-is.

## Development

```bash
# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Run only snapshot tests (compares against JS MJML output)
vendor/bin/phpunit --testsuite Snapshot
```

### Fixture Tests

Small render and validation cases can be added as `.test` files under `tests/Fixtures/`.
Use labeled sections:

```text
--OPTIONS--
validationLevel: skip
ignoreIncludes: false

--MJML--
<mjml>...</mjml>

--HTML--
Expected HTML fragment

--HTML-NOT--
Unexpected HTML fragment

--HTML-RAW--
Expected raw multi-line HTML fragment

--HTML-NOT-RAW--
Unexpected raw multi-line HTML fragment

--ERRORS--
Expected validation error fragment

--EXCEPTION--
Mjml\Parser\ParseException
Expected exception message fragment
```

For simple render fixtures, the legacy shorthand is also supported:

```text
<mjml>...</mjml>
----
Expected HTML fragment
```

### Regenerating Snapshot References

The snapshot test fixtures compare PHP output against reference HTML generated by the JS MJML CLI. To regenerate:

```bash
npm install -g mjml

for f in tests/Snapshot/Fixtures/*.mjml; do
    npx mjml@5.2.1 "$f" --no-minify > "${f%.mjml}.html"
done
```

## License

MIT
