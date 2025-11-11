# Glossary by Progress Planner

[![Try Glossary on the WordPress playground](https://img.shields.io/badge/Try%20Glossary%20on%20the%20WordPress%20Playground-%23117AC9.svg?style=for-the-badge&logo=WordPress&logoColor=ddd)](https://playground.wordpress.net/?blueprint-url=https%3A%2F%2Fprogressplanner.com%2Fresearch%2Fblueprint-glossary.php)

A semantic, accessible WordPress glossary plugin that automatically links terms to hover-triggered popover definitions using native WordPress functionality.

## Features

- **Custom Post Type**: Register glossary entries with custom fields (no content editor needed)
- **Native WordPress Fields**: Uses WordPress custom meta boxes for field management (short description, long description, synonyms)
- **Automatic Term Linking**: Automatically transforms the first mention of glossary terms in your content
- **Hover-Triggered Popovers**: Display definitions on hover/focus using the native Popover API
- **Semantic HTML**: Uses `<dfn>` and `<aside>` elements with proper ARIA attributes
- **Synonyms Support**: Define alternative terms that trigger the same glossary entry
- **Glossary Block**: Gutenberg block to display full glossary with alphabetical navigation
- **Settings Page**: Configure which page displays the glossary
- **Accessible**: Full keyboard navigation and screen reader compatibility
- **Responsive Design**: Mobile-friendly with CSS custom properties for easy theming
- **No External Dependencies**: Pure WordPress core functionality, no third-party plugins required

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Modern browser with Popover API support (Chrome 114+, Edge 114+, Safari 17+)

## Installation

1. Download or clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone [repository-url] pp-glossary
   ```

2. Activate the "Glossary by Progress Planner" plugin in your WordPress admin panel

3. Navigate to **Glossary** in the WordPress admin menu to start adding entries

## Setup

### 1. Create a Glossary Page

1. Create a new page in WordPress (e.g., "Glossary" or "Terms")
2. Add the **Glossary List** block to the page
3. Configure the block settings (show/hide title, custom title text)
4. Publish the page

### 2. Configure Settings

1. Go to **Glossary > Settings** in the WordPress admin
2. Select the page you created as the "Glossary Page"
3. Save settings

### 3. Add Glossary Entries

1. Go to **Glossary > Add New**
2. Enter the term as the title (e.g., "Cumulative Layout Shift")
3. Fill in the custom fields in the "Glossary Entry Details" meta box:
   - **Short Description** (required): Brief definition (1-2 sentences) shown in popovers
   - **Long Description**: Detailed explanation shown on the glossary page
   - **Synonyms**: Alternative terms (e.g., "CLS", "layout shift") - click "Add Synonym" to add more
4. Publish the entry

## Usage

### Automatic Term Linking

Once you've added glossary entries, the plugin automatically:

- Scans post and page content for mentions of glossary terms (case-insensitive)
- Transforms the **first mention** of each term into an interactive element
- Shows a popover with the short description when users hover over or focus on the term
- Adds a "Read more" link to the full glossary entry

### The Glossary Block

The Glossary List block displays:
- Optional title
- Alphabetical navigation (A-Z)
- All entries grouped by first letter
- Short and long descriptions for each entry
- Synonym listings

### Hover Behavior

- **Mouse users**: Hover over a dotted underlined term to see the definition
- **Keyboard users**: Tab to the term and it will show automatically
- **Touch users**: Tap the term to toggle the popover
- Popovers stay open when hovering over them (to click "Read more" links)

## HTML Structure

The plugin generates semantic, accessible HTML:

```html
<dfn id="dfn-term-1" class="pp-glossary-term">
  <span data-glossary-popover="pop-term-1"
        aria-describedby="help-def"
        tabindex="0"
        role="button"
        aria-expanded="false">
    term
  </span>
</dfn>

<aside id="pop-term-1"
       popover="manual"
       role="tooltip"
       aria-labelledby="dfn-term-1">
  <strong>Term</strong>
  <p>Short description of the term.</p>
  <p><a href="/glossary/#entry-123">Read more about term</a></p>
</aside>

<p id="help-def" hidden>Hover or focus to see the definition of the term.</p>
```

## Customization

### Styling

The plugin uses CSS custom properties for easy theming:

```css
:root {
  --glossary-underline-color: rgba(0, 0, 0, 0.4);
  --glossary-underline-hover-color: rgba(0, 0, 0, 0.7);
  --glossary-bg-color: #fff;
  --glossary-border-color: #ddd;
  --glossary-accent-color: #0073aa;
  --glossary-link-color: #0073aa;
  /* ... and more */
}
```

### Block Customization

The Glossary List block supports:
- Wide and full alignment
- Toggle title visibility
- Custom title text

### Filters

Modify behavior using WordPress filters:

```php
// Modify which post types get glossary term replacement
add_filter( 'the_content', 'your_custom_filter', 19 ); // Run before glossary filter at priority 20
```

## Browser Support

The plugin uses modern web platform features:

**Popover API:**
- Chrome/Edge 114+
- Safari 17+
- Firefox (experimental support behind flag)

**CSS Anchor Positioning:**
- Chrome/Edge 125+
- Safari (not yet supported)
- Firefox (not yet supported)

For older browsers:
- Consider adding the [Popover API polyfill](https://github.com/oddbird/popover-polyfill)
- CSS Anchor Positioning gracefully degrades (popovers may not position optimally but will still be functional)

## Accessibility

The plugin follows WCAG 2.1 Level AA guidelines:

- Semantic HTML elements (`<dfn>`, `<aside>`, proper roles)
- Full keyboard navigation with visible focus indicators
- ARIA attributes for screen readers
- Hover delay to prevent accidental triggers
- `cursor: help` to indicate interactive terms
- Color contrast ratios meet AA standards

## Development

### No Build Process

The plugin uses vanilla JavaScript and CSS - no build process required!

### Coding Standards

Follows WordPress Coding Standards:
- [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)

To check code:
```bash
composer install
composer run phpcs
```

## License

GPL v2 or later

## Credits

Developed by Joost de Valk for Progress Planner.

## Changelog

### 1.0.0
- Initial release
- Custom post type for glossary entries
- Native WordPress custom fields (short description, long description, synonyms)
- Hover-triggered popovers using Popover API
- Automatic term linking (first occurrence only)
- Glossary List Gutenberg block
- Settings page for glossary page configuration
- Semantic, accessible HTML
- Responsive design with CSS custom properties
- Full keyboard and screen reader support
- No external plugin dependencies
