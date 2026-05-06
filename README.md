# AI Share Links

Add AI-powered sharing buttons to your WordPress site for summarization and analysis across Perplexity, ChatGPT, Claude, Gemini, and DeepSeek.

## Description

AI Share Links helps readers open your content in popular AI tools with one click. Instead of only sharing to social media, visitors can summarize, ask questions, and analyze your posts and selected pages directly in their preferred AI assistant.

## Features

### Core Functionality
- **5 AI Platforms**: Perplexity, ChatGPT, Claude, Gemini, and DeepSeek
- **Automatic Post Integration**: Buttons appear on single blog posts
- **Optional Page Support**: Enable buttons on specific pages using slugs or full paths
- **Custom Prompt Templates**: Define your own AI prompt with token replacement

### Styling & Display
- **Theme Inheritance**: Buttons inherit your active theme’s typography, spacing, and button look
- **Icon Options**: Choose between platform logos or emoji icons
- **Flexible Positioning**: Show buttons at top, bottom, or both
- **Responsive Layout**: Mobile-friendly rendering
- **Compatibility Mode**: Optional JavaScript placement fix for themes that inject featured images after `the_content`

### Prompting & Analytics
- **Prompt Tokens**: Use `{URL}`, `{SITE}`, `{TITLE}`, `{TYPE}`, `{POST_TYPE}`, `{CATEGORY}`, `{TAGS}`, and `{EXCERPT}`
- **Runtime Prompt Generation**: Prompt values are generated at click time while keeping href fallback behavior; empty tokens resolve to blank values
- **Google Analytics Integration**: Optional click tracking

## Installation

1. Upload plugin files to `/wp-content/plugins/ai-share-links/`
2. Activate the plugin in WordPress Admin
3. Go to **Settings → AI Share Links**

## Configuration

### Basic Settings

- **Position**: Top only, bottom only, or both
- **Icon Type**: Logos or emojis
- **Uppercase Button Text**: Toggle uppercase labels
- **Description Text**: Text shown above share buttons
- **Enabled AI Platforms**: Choose which platforms to show

### AI Prompt Template

Use tokens in your prompt:

- `{URL}` – current page URL
- `{SITE}` – site name
- `{TITLE}` – current page/post title
- `{TYPE}` – post type label (for example, `Post` or `Page`)
- `{POST_TYPE}` – post type slug (for example, `post` or `page`)
- `{CATEGORY}` – comma-separated category names (empty when not available)
- `{TAGS}` – comma-separated tag names (empty when not available)
- `{EXCERPT}` – post excerpt, or an auto-generated excerpt from content when no manual excerpt exists

Token values are replaced at click time in JavaScript and in fallback href links rendered by PHP. If a token is unavailable for the current post/page context, it is replaced with an empty string.

Default prompt:

```text
Summarize this {TYPE} from {SITE}: {TITLE}\nURL: {URL}\nCategory: {CATEGORY}\nTags: {TAGS}\nExcerpt: {EXCERPT}
```

### Show on Pages (slugs/paths)

Enter values separated by commas or new lines.

Examples:

```text
about
pricing
resources/guides/getting-started
```

### Compatibility Mode

Enable Compatibility Mode if your theme prepends featured images **after** `the_content` filters and causes the share bar to overlap content. The plugin will move the share bar to the top via JavaScript.

## Supported AI Platforms

1. **Perplexity**
2. **ChatGPT**
3. **Claude**
4. **Gemini**
5. **DeepSeek**

## Requirements

- WordPress 5.0+
- PHP 7.4+

## Version History

### Version 1.1.4
- Introduced theme-inherited styling model for cleaner integration with active themes
- Updated platform set to Perplexity, ChatGPT, Claude, Gemini, and DeepSeek
- Added `{TITLE}` token support in AI prompt generation
- Added compatibility mode option for theme/content ordering conflicts
- Improved plugin internals with modular classes (`sanitizer`, `admin settings`, `frontend renderer`, `assets`)

### Version 1.1.1
- Added Brand Blue color scheme for professional branded appearance
- Added Brand Transparent color scheme for universal compatibility
- Enhanced color scheme options to 7 total schemes

### Version 1.1.0
- Added page slug support for custom page placement
- Support for hierarchical page structures
- Improved admin interface with better organization

### Version 1.0.0
- Initial release

## Support

For support, feature requests, or bug reports, use the GitHub repository issues page.

## License

GPL v2 or later.
