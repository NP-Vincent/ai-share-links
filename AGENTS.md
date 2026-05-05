# AGENTS Instructions

This repository contains the **AI Share Links** WordPress plugin.

## Project Scope

- Keep plugin behavior aligned with WordPress coding and escaping best practices.
- Preserve modular architecture in `includes/`:
  - `class-sanitizer.php`
  - `class-admin-settings.php`
  - `class-frontend-renderer.php`
  - `class-assets.php`

## Contributor Guidelines

- Update `README.md` whenever:
  - user-facing settings change,
  - supported AI platforms change,
  - prompt tokens or integration behavior change,
  - compatibility behavior changes.
- Keep version history entries synchronized with plugin header version in `ai-share-links.php`.
- Prefer small, focused commits with clear messages.
- Run basic checks (`php -l` on edited PHP files) before committing.

## Documentation Expectations

When documenting features, use the current behavior:

- Theme-inherited styling (not fixed color scheme picker)
- Prompt tokens `{URL}`, `{SITE}`, `{TITLE}`
- Supported platforms: Perplexity, ChatGPT, Claude, Gemini, DeepSeek
- Optional compatibility mode for themes with late featured-image insertion
