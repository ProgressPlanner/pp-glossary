# Glossary by Progress Planner

[![Try Glossary on the WordPress playground](https://img.shields.io/badge/Try%20Glossary%20on%20the%20WordPress%20Playground-%23117AC9.svg?style=for-the-badge&logo=WordPress&logoColor=ddd)](https://playground.wordpress.net/?blueprint-url=https%3A%2F%2Fprogressplanner.com%2Fresearch%2Fblueprint-glossary.php)

A semantic, accessible WordPress glossary plugin that automatically links terms to hover-triggered popover definitions using native WordPress functionality.

## Features

- **Custom Post Type**: Register glossary entries with custom fields (no content editor needed)
- **Native WordPress Fields**: Uses WordPress custom meta boxes for field management (short description, long description, synonyms)
- **Automatic Term Linking**: Automatically transforms the first mention of glossary terms in your content
- **Hover-Triggered Popovers**: Display definitions on hover/focus using the native Popover API with CSS Anchor Positioning
- **Semantic HTML**: Uses `<dfn>` and `<aside>` elements with proper ARIA attributes
- **Schema.org Integration**: Full DefinedTerm and DefinedTermSet structured data support
  - Integrates with Yoast SEO schema graph when available
  - Falls back to Microdata when Yoast SEO is not active
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

The plugin generates semantic, accessible HTML with CSS Anchor Positioning:

```html
<!-- Glossary term with anchor definition -->
<dfn id="dfn-term-1"
     class="pp-glossary-term"
     style="anchor-name: --dfn-term-1;">
  <span data-glossary-popover="pop-term-1"
        aria-describedby="help-def"
        tabindex="0"
        role="button"
        aria-expanded="false">
    term
  </span>
</dfn>

<!-- Popover anchored to the term -->
<aside id="pop-term-1"
       popover="manual"
       role="tooltip"
       aria-labelledby="dfn-term-1"
       style="position-anchor: --dfn-term-1;">
  <strong class="glossary-title">Term</strong>
  <p>Short description of the term.</p>
  <p><a href="/glossary/#term-slug">Read more about <strong>Term</strong></a></p>
</aside>

<!-- Hidden helper text for accessibility -->
<p id="help-def" hidden>Hover or focus to see the definition of the term.</p>
```

The glossary block itself includes Schema.org structured data (Microdata when Yoast SEO is not active, JSON-LD when Yoast SEO is active):

```html
<!-- Glossary block with schema markup -->
<div class="pp-glossary-block"
     itemscope
     itemtype="https://schema.org/DefinedTermSet"
     itemid="https://example.com/glossary/#glossary">

  <meta itemprop="name" content="Glossary">

  <!-- Each entry -->
  <article id="term-slug"
           class="glossary-entry"
           itemprop="hasDefinedTerm"
           itemscope
           itemtype="https://schema.org/DefinedTerm">

    <link itemprop="url" href="https://example.com/glossary/#term-slug">

    <h4 class="glossary-entry-title" itemprop="name">Term Title</h4>

    <div class="glossary-synonyms">
      <span class="synonyms-label">Also known as:</span>
      <span>Synonym 1, Synonym 2</span>
      <meta itemprop="alternateName" content="Synonym 1">
      <meta itemprop="alternateName" content="Synonym 2">
    </div>

    <div class="glossary-long-description" itemprop="description">
      Long description of the term...
    </div>
  </article>
</div>
```

**Note**: When Yoast SEO is active, the Microdata attributes are omitted and structured data is added to Yoast's JSON-LD schema graph instead.

## Customization

### Styling

The plugin uses CSS custom properties for easy theming, with their defaults listed:

```css
:root {
  --glossary-underline-color: rgba(0, 0, 0, 0.4);
  --glossary-underline-hover-color: rgba(0, 0, 0, 0.7);
  --glossary-focus-color: #005a87;
  --glossary-bg-color: #fff;
  --glossary-border-color: #ddd;
  --glossary-text-color: #333;
  --glossary-heading-color: #000;
  --glossary-link-color: #0073aa;
  --glossary-accent-color: #0073aa;
  --glossary-nav-bg: #fff;
  --glossary-letter-bg: #f5f5f5;
  --glossary-letter-color: #333;
  --glossary-letter-hover-bg: #0073aa;
  --glossary-letter-hover-color: #fff;
  --glossary-entry-bg: #f9f9f9;
  --glossary-meta-color: #666;
}
```

### Filters

Modify behavior using WordPress filters:

```php
// Disable content filtering for specific post types.
add_filter( 'pp_glossary_disabled_post_types', function( $post_types ) {
    // Disable filtering for 'product' and 'custom_post_type'.
    return array( 'product', 'custom_post_type' );
} );
```

## Browser Support

The plugin uses modern web platform features:

**Popover API:**
- Chrome/Edge 114+
- Safari 17+
- Firefox (experimental support behind flag)

**CSS Anchor Positioning:**
- Chrome/Edge 125+/Safari: supported
- Firefox (not yet supported)

For older browsers:
- Consider adding the [Popover API polyfill](https://github.com/oddbird/popover-polyfill)
- CSS Anchor Positioning gracefully degrades (popovers may not position optimally but will still be functional)

## Schema.org Structured Data

The plugin automatically adds Schema.org structured data for glossary entries:

### With Yoast SEO

When Yoast SEO is active, the plugin integrates with the Yoast schema graph API to add:
- **DefinedTermSet** for the glossary page
- **DefinedTerm** for each glossary entry

The structured data appears in Yoast's JSON-LD output and is compatible with Yoast's schema features.

### Without Yoast SEO

When Yoast SEO is not active, the plugin outputs Microdata markup directly in the HTML:
- Uses `itemscope` and `itemtype` attributes on the glossary block
- Each entry includes proper `itemprop` attributes for name, description, URL, and synonyms
- Fully compliant with Schema.org DefinedTerm specification

### Schema Properties

Each glossary entry includes:
- **@type**: DefinedTerm
- **name**: The term title
- **description**: Short description (shown in popovers)
- **url**: Anchor link to the entry on the glossary page
- **alternateName**: Array of synonyms (alternative terms)

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
- Hover-triggered popovers using Popover API with CSS Anchor Positioning
- Automatic term linking (first occurrence only)
- Glossary List Gutenberg block
- Settings page for glossary page configuration
- Schema.org structured data (DefinedTerm and DefinedTermSet)
  - Yoast SEO integration (JSON-LD)
  - Microdata fallback when Yoast is not active
- Semantic, accessible HTML
- Responsive design with CSS custom properties
- Full keyboard and screen reader support
- No external plugin dependencies
