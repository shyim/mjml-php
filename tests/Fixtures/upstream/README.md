# Upstream MJML fixtures

These fixtures are adapted from the MJML v5.2.1 upstream test suite:

- https://github.com/mjmlio/mjml/tree/v5.2.1/packages/mjml/test
- https://github.com/mjmlio/mjml/tree/v5.2.1/packages/mjml-parser-xml/test

The original project is MIT licensed. Fixtures are converted to this repository's `.test` format and generally assert stable behavior fragments instead of reproducing the upstream Jest/Mocha test harness exactly.

## Import audit

Imported as fixture coverage:

- `accordion-fontFamily.test.js`
- `accordion-padding.test.js`
- `accordionTitle-fontWeight.test.js`
- `beautify-output.test.js` render-output cases that are applicable to this dependency-free formatter
- `border-radius-string.test.js`
- `carousel-hoverSupported.test.js`
- `column-border-radius.test.js`
- `html-attributes.test.js`
- `html-comments.test.js`
- `htmlmin-ignore.test.js`
- `ignore-includes.test.js`
- `include-path.test.js`
- `include-path-security.test.js`
- `include-path-traversal.test.js`
- `navbar-ico-padding.test.js`
- `social-align.test.js`
- `social-icon-height.test.js`
- `table-cellspacing.test.js`
- `tableWidth.test.js`
- `wrapper-border-radius.test.js`
- `wrapper-gap.test.js`
- parser XML cases from `test-values.js`: special characters, similar tags/order, self-closing tags, regex-timeout shape, multiline attributes, self-closing ending tags, include behavior, single-opening-tag ending content

Covered by dedicated PHP tests instead of `.test` fixtures because they exercise internals or PHP-specific entry points:

- `lazy-head-style.test.js` → `tests/Integration/LazyHeadStyleTest.php`
- parser XML include tree metadata → parser/include unit and integration tests

Not imported because they are not applicable to this PHP port or need unsupported JS-only options:

- `ignore-includes-cli.test.js`, `include-path-cli.test.js`, `watch-cli.test.js`: upstream Node CLI/watch tests; this package has a separate PHP CLI covered by `tests/Unit/Cli/CliTest.php`.
- `preprocessors.test.js`: upstream parser callback option is not part of `MjmlOptions`.
- template syntax sanitization error/minifyCss variants requiring `sanitizeStyles`, `templateSyntax`, `allowMixedSyntax`, or `minifyOptions`: these options are not part of this PHP renderer. Stable template-token preservation is covered by `render/template-syntax-preservation.test`.
