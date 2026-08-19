# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- Optional caching layer for parsed MJML templates (PSR-6/PSR-16)
- Extensibility hooks in `RenderingPipeline`
- `--minify`, `--beautify`, `--keep-comments` CLI flags
- `.editorconfig` for consistent contributor formatting
- `border` attribute on `mj-social` / `mj-social-element` (MJML 5.4.0; default `0` on the icon image)
- `gutter` attribute on `mj-section` (MJML 5.4.0; space between columns, including RTL, `%`/`px`, groups, and Outlook)

### Changed
- Renamed `MjmlResult::$json` to `$ast` for clarity
- Renamed `AttributeFormatter` to `AttributeMerger` (reflects actual purpose)
- `GlobalContext` now uses private properties with setters
- `ValidationLevel::Soft` now listed in CLI help text
- Snapshot tests and compatibility target now MJML 5.4.0
- Outlook column pixel widths are rounded to match MJML 5.4.0 (`33.33%` of 600px is `200px`, not `199.98px`)

### Fixed
- libxml errors are now properly inspected and logged
- `ValidChildrenRule` uses cached reverse-lookup for performance
- `composer.lock` removed from git tracking (library convention)
- `BackgroundParser::calculateVmlPositions` float precision preserved

## [1.0] - Initial Release

### Added
- Native PHP port of MJML 5.2.1
- All standard MJML components (Body, Head, interactive)
- Validation (Strict, Soft, Skip)
- Snapshot tests against JS MJML output
- CLI tool (`mjml-php`)
- URL sanitization for security
- `mj-include` support with path jail
- Outlook conditional comment merging
- CSS inlining
- HTML attribute application via selectors
