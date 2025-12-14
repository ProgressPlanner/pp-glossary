# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Important: Code Quality & Documentation

### Code Quality Checks

Before completing any code changes, always run these three commands to verify code quality:

```bash
composer check-cs   # Check WordPress coding standards (PHPCS)
composer lint       # Check for PHP syntax errors
composer phpstan    # Static analysis for type errors
```

All three commands must pass without errors before changes are considered complete. Fix any issues found before proceeding.

### Documentation Updates

When making changes to this codebase, always update the relevant documentation:

1. **Changelogs**: After implementing a feature or fix, update both:
   - `README.md` - The GitHub changelog (under `## Changelog`)
   - `readme.txt` - The WordPress.org changelog (under `== Changelog ==`)

2. **This file (CLAUDE.md)**: After making significant changes, update this file to reflect:
   - New or modified settings/options
   - Changes to the architecture or file structure
   - New methods or classes
   - Updated testing tips
   - Modified filters or hooks

Keep documentation in sync with code changes to ensure accuracy for future development.

## Project Overview

**Glossary by Progress Planner (pp-glossary)** is a WordPress plugin that automatically links glossary terms to accessible, semantic popovers that appear on click. Uses native WordPress custom fields for field management and includes a Gutenberg block for displaying the full glossary.

### Core Functionality

- Registers a custom post type (`pp_glossary`) for glossary entries (title + custom fields only, no editor)
- Uses native WordPress meta boxes for field management (short description, long description, synonyms)
- Automatically transforms first mentions of glossary terms in content into click-triggered popovers
- Provides a Gutenberg block to display the full glossary with alphabetical navigation
- Settings page to configure which page displays the glossary

## Architecture

### File Structure

```
pp-glossary/
├── pp-glossary.php              # Main plugin file, initialization, hooks
├── includes/
│   ├── functions.php            # Helper functions
│   ├── class-post-type.php      # Post_Type class - CPT registration
│   ├── class-meta-boxes.php     # Meta_Boxes class - Custom meta boxes
│   ├── class-content-filter.php # Content_Filter class - Term replacement
│   ├── class-settings.php       # Settings class - Settings page
│   ├── class-blocks.php         # Blocks class - Block registration
│   ├── class-schema.php         # Schema class - Schema.org integration
│   ├── class-assets.php         # Assets class - CSS/JS enqueuing
│   └── class-migrations.php     # Migrations class - Data migration on upgrade
├── blocks/
│   └── glossary-list/
│       ├── block.json           # Block metadata
│       └── editor.js            # Block editor interface (vanilla JS)
└── assets/
    ├── css/glossary.css         # All plugin styles
    └── js/glossary.js           # Click behavior and accessibility
```

### Key Components

1. **Post Type Registration** (`includes/class-post-type.php`)
   - Post type slug: `pp_glossary`
   - Supports: `title` only (no editor, no revisions - all data is in post meta)
   - `has_archive` set to `false` (uses block instead)
   - `publicly_queryable` set to `false` (no individual entry pages)
   - `exclude_from_search` set to `true` (entries have no public pages)
   - Excluded from Yoast SEO indexables and XML sitemaps

2. **Meta Boxes** (`includes/class-meta-boxes.php`)
   - Native WordPress meta boxes (no external dependencies)
   - All fields stored in a single post meta key `_pp_glossary_data` (array)
   - Fields (in display order):
     - `short_description` (textarea, required)
     - `long_description` (wp_editor)
     - `synonyms` (array of strings)
     - `case_sensitive` (boolean) - only match terms when case matches exactly
     - `disable_autolink` (boolean) - entry appears in glossary but not auto-linked in content
   - JavaScript inline for adding/removing synonyms dynamically

3. **Content Filter** (`includes/class-content-filter.php`)
   - Hooks into `the_content` at priority 20
   - Finds first occurrence of each term (case-insensitive by default, or case-sensitive if enabled per entry)
   - Respects `disable_autolink` setting to skip entries that shouldn't be linked
   - Generates unique IDs for each popover instance
   - Appends popovers at end of content
   - Uses settings page URL for "Read more" links (not individual permalinks)
   - Skips glossary page itself to prevent self-linking

4. **Settings Page** (`includes/class-settings.php`)
   - Submenu under Glossary CPT menu
   - Stores settings in `pp_glossary_settings` option
   - Settings fields:
     - `glossary_page` (int) - ID of the page containing the glossary block
     - `excluded_tags` (array) - HTML tags where terms should not be highlighted (default: `a`, `h1`-`h6`)
     - `excluded_post_types` (array) - Post types where terms should not be highlighted
   - Methods: `get_glossary_page_id()`, `get_glossary_page_url()`, `get_excluded_tags()`, `get_excluded_post_types()`

5. **Block System** (`includes/class-blocks.php`, `blocks/glossary-list/`)
   - Server-side rendered block using `render_callback`
   - No block attributes (simplified interface)
   - Displays all entries grouped alphabetically with navigation
   - Supports wide and full alignment
   - Falls back to short description when long description is empty
   - Shows edit link for logged-in users with edit capabilities per glossary item

6. **Schema.org Integration** (`includes/class-schema.php`)
   - Detects if Yoast SEO is active using `defined('WPSEO_VERSION')`
   - With Yoast SEO: Hooks into `wpseo_schema_graph` filter to add JSON-LD
   - Without Yoast SEO: Outputs Microdata markup in HTML
   - Implements DefinedTermSet (glossary) and DefinedTerm (entries) schemas
   - Methods: `add_to_yoast_schema_graph()`, `get_microdata_attributes()`, `get_entry_microdata_attributes()`, `get_itemprop()`

## Data Storage

All custom field data is stored in a single WordPress post meta key `_pp_glossary_data` as an associative array:

```php
[
    'short_description' => '',      // string
    'long_description'  => '',      // string, HTML allowed
    'synonyms'          => [],      // array of strings
    'case_sensitive'    => false,   // boolean
    'disable_autolink'  => false,   // boolean
]
```

Retrieved using `Meta_Boxes::get_entry_data($post_id)` which handles defaults. Saved using `update_post_meta()`.

**Migration**: The plugin includes an automatic migration system that converts old separate meta keys to the new consolidated format on upgrade.

## HTML Structure Pattern

The plugin generates highly semantic, accessible HTML with click triggers and CSS Anchor Positioning:

```html
<dfn id="dfn-{term}-{counter}"
     class="pp-glossary-term"
     style="anchor-name: --dfn-{term}-{counter};">
  <button data-glossary-popover="pop-{term}-{counter}"
          type="button"
          aria-expanded="false">
    {matched term}
  </button>
</dfn>

<aside id="pop-{term}-{counter}"
       popover="auto"
       role="tooltip"
       aria-labelledby="dfn-{term}-{counter}"
       style="position-anchor: --dfn-{term}-{counter};">
  <p><a href="{glossary_page_url}#{slug}">Read more about {term}</a></p>
  <p>{Short description}</p>
</aside>
```

Key implementation details:
- Uses `popover="auto"` for automatic light-dismiss and mutual exclusivity (only one popover open at a time)
- Uses `<button>` element for proper accessibility (not span)
- Role is `tooltip` for popovers
- Triggered by click (not hover) for better accessibility
- Link appears before description for better screen reader context
- Popover positioned using CSS Anchor Positioning API
- Each dfn defines an `anchor-name` that the popover references with `position-anchor`

## JavaScript Click Implementation

The plugin uses click-based popover control (`assets/js/glossary.js`):

- **Click**: Toggles popover visibility on button click
- **Keyboard**: Enter/Space toggles popover, Escape closes
- **Light dismiss**: Clicking outside popover closes it (handled by `popover="auto"`)
- **Mutual exclusivity**: Opening one popover automatically closes others (handled by `popover="auto"`)
- **Positioning**: Uses CSS Anchor Positioning API for automatic positioning
- **ARIA**: Updates `aria-expanded` attribute on toggle buttons

## CSS Anchor Positioning Implementation

The plugin uses CSS Anchor Positioning for automatic popover placement (`assets/css/glossary.css`):

**How it works:**
1. Each `<dfn>` element defines an anchor using inline `anchor-name: --dfn-{id};`
2. Each popover references its anchor using inline `position-anchor: --dfn-{id};`
3. CSS positions the popover using `anchor()` functions:
   - `top: anchor(bottom)` - Position below the term
   - `left: anchor(left)` - Align with left edge of term
4. Fallback positions defined with `@position-try` rules for viewport overflow:
   - `--top-left` - Above the term when it would overflow bottom
   - `--bottom-right` - Below and right-aligned when it would overflow right
   - `--top-right` - Above and right-aligned

**Benefits:**
- Browser automatically handles viewport containment
- No JavaScript calculations needed
- Better performance than JavaScript positioning
- Respects scrolling and transforms automatically

**Browser Support:**
- Chrome/Edge 125+ (full support)
- Safari/Firefox (not yet supported, popovers still display but may not position optimally)

## Schema.org Integration

The plugin provides rich structured data for glossary entries using Schema.org's DefinedTerm and DefinedTermSet types.

### Implementation Strategy

**Dual-Mode Output:**
1. **Yoast SEO Active**: Integrates with Yoast's schema graph (JSON-LD format)
2. **Yoast SEO Inactive**: Outputs Microdata markup in HTML

This ensures compatibility regardless of whether Yoast SEO is installed.

### Yoast SEO Integration

When `WPSEO_VERSION` is defined:
- Hooks into `wpseo_schema_graph` filter (priority 10)
- Adds DefinedTermSet to the graph with all DefinedTerm entries nested inside
- Only runs on the glossary page (checks `get_glossary_page_id()`)
- Output format: JSON-LD via Yoast's graph system

**Schema Structure (JSON-LD):**
```json
{
  "@type": "DefinedTermSet",
  "@id": "https://example.com/glossary/#glossary",
  "name": "Glossary Page Title",
  "description": "Page excerpt",
  "hasDefinedTerm": [
    {
      "@type": "DefinedTerm",
      "@id": "https://example.com/glossary/#term-slug",
      "name": "Term Title",
      "description": "Short description or Long description (stripped of HTML)",
      "url": "https://example.com/glossary/#term-slug",
      "alternateName": ["Synonym1", "Synonym2"]
    }
  ]
}
```

### Microdata Integration

When Yoast SEO is NOT active:
- Adds `itemscope`, `itemtype`, `itemprop` attributes to HTML elements
- Applied directly to the glossary block container and each entry
- All schema helper methods return empty strings when Yoast is active
- Arrays (like `alternateName`) are represented using multiple elements with the same `itemprop`

**HTML Output (Microdata):**
```html
<div class="pp-glossary-block" itemscope itemtype="https://schema.org/DefinedTermSet" itemid="...">
  <meta itemprop="name" content="Glossary Title">

  <article itemscope itemtype="https://schema.org/DefinedTerm" itemprop="hasDefinedTerm">
    <link itemprop="url" href="...">
    <h4 itemprop="name">Term Title</h4>
    <div class="glossary-synonyms">
      <span>Synonym1, Synonym2</span>
      <meta itemprop="alternateName" content="Synonym1">
      <meta itemprop="alternateName" content="Synonym2">
    </div>
    <div itemprop="description">Long description</div>
  </article>
</div>
```

### Schema Properties Mapping

| Schema Property | WordPress Data | Location |
|----------------|----------------|----------|
| `name` | Entry title | `get_the_title()` |
| `description` | Short description or Long description | `_pp_glossary_short_description` or `_pp_glossary_long_description` meta |
| `url` | Anchor link | `{glossary_page_url}#{slug}` |
| `alternateName` | Array of synonyms | `_pp_glossary_synonyms` meta |

### Key Methods

**`PP_Glossary_Schema::add_to_yoast_schema_graph($graph, $context)`**
- Adds glossary to Yoast's schema graph
- Returns modified `$graph` array with DefinedTermSet added

**`PP_Glossary_Schema::get_microdata_attributes($entries, $page_id)`**
- Returns microdata attributes for glossary container
- Empty string if Yoast SEO is active

**`PP_Glossary_Schema::get_entry_microdata_attributes($entry)`**
- Returns microdata attributes for individual entry
- Empty string if Yoast SEO is active

**`PP_Glossary_Schema::get_itemprop($prop)`**
- Returns itemprop attribute for a property name
- Empty string if Yoast SEO is active

### Detection Logic

```php
if ( defined( 'WPSEO_VERSION' ) ) {
    // Use Yoast integration (JSON-LD)
} else {
    // Use Microdata
}
```

This check is performed:
- In `init()` to determine which hooks to add
- In all helper methods to determine output
- Ensures no duplicate schema markup

### Array Handling for alternateName

The `alternateName` property (synonyms) is output as an array in both formats, but using different methods:

**JSON-LD (Yoast SEO):**
- Simply assigns the PHP array directly: `$defined_term['alternateName'] = $entry['synonyms'];`
- JSON-LD supports arrays natively: `"alternateName": ["Synonym1", "Synonym2"]`

**Microdata:**
- Outputs multiple `<meta>` tags with the same `itemprop="alternateName"`
- Each synonym gets its own meta tag: `<meta itemprop="alternateName" content="Synonym1">`
- Microdata parsers understand multiple properties with the same name as an array
- Visual display shows comma-separated synonyms in a `<span>`, with hidden meta tags for schema

This ensures that search engines see synonyms as an array regardless of the schema format used.

## Development Commands

No build process required - vanilla JavaScript and CSS.

**Linting**:
```bash
composer install
composer run phpcs    # Check coding standards
composer run phpcbf   # Fix coding standards
```

## Important Implementation Details

### Term Matching Algorithm

- Terms sorted by length (longest first) to handle overlapping terms
- Entries with `disable_autolink` enabled are skipped entirely
- Content inside excluded HTML tags is skipped (configurable via settings, default: `a`, `h1`-`h6`)
- Post types in the excluded list are skipped entirely (configurable via settings)
- Uses regex pattern: `/\b({term})\b(?![^<]*>)/u` (or `/iu` for case-insensitive)
  - `\b` = word boundaries
  - `(?![^<]*>)` = negative lookahead to avoid matching inside HTML tags
  - `i` flag = case-insensitive (omitted when `case_sensitive` is enabled)
  - `u` flag = Unicode support
- Only replaces first occurrence per entry per content piece

### State Management

`PP_Glossary_Content_Filter` uses static properties during filtering:
- `$popover_counter` - ensures unique IDs
- `$popovers` - stores popover HTML for end-of-content appending
- These reset on each `filter_content()` call

### Settings Integration

- `PP_Glossary_Settings::get_glossary_page_url()` returns the URL of the page containing the glossary block
- "Read more" links use this URL + `#{slug}` anchor (slug-based, not ID-based)
- If no glossary page is set, "Read more" link is omitted
- `PP_Glossary_Settings::get_excluded_tags()` returns array of HTML tags to skip (default: `['a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']`)
- `PP_Glossary_Settings::get_excluded_post_types()` returns array of post types to skip (default: `[]`)

### Block Registration

- Block registered using `register_block_type()` with JSON file
- `render_callback` points to `PP_Glossary_Blocks::render_glossary_list_block()`
- Editor script uses vanilla JavaScript (no JSX, no build)
- Block shows: entry title (H4), synonyms, description (long description with fallback to short)
- Shows edit link for logged-in users with edit capabilities
- No H2 title at top of block

### Accessibility Features

- Click-to-open behavior (not hover) for better accessibility
- `<button>` element used for triggers (proper keyboard accessibility)
- `aria-expanded` updated via JavaScript when popovers show/hide
- `role="tooltip"` for popovers
- `popover="auto"` ensures only one popover open at a time (no overlapping)
- Light dismiss (click outside to close)
- Escape key closes popover
- Link appears before description in popover for better screen reader context
- Dotted underline to indicate definitions without looking like regular links
- Popover positioning accounts for viewport boundaries via CSS Anchor Positioning

## CSS Customization

Uses CSS custom properties (see `assets/css/glossary.css`):

```css
--glossary-underline-color         # Dotted underline (default: rgba(0,0,0,0.4))
--glossary-underline-hover-color   # Hover state (default: rgba(0,0,0,0.7))
--glossary-bg-color
--glossary-border-color
--glossary-accent-color
/* ...and more */
```

Terms inherit text color from surrounding content, only underline indicates glossary term.

## Browser Compatibility

Requires two modern web platform features:

**Popover API** (required):
- Chrome/Edge 114+
- Safari 17+
- Firefox (experimental)

**CSS Anchor Positioning** (required for optimal positioning):
- Chrome/Edge 125+
- Safari (not yet supported)
- Firefox (not yet supported)

JavaScript checks for both features and logs warnings if unavailable. Consider:
- [Popover API polyfill](https://github.com/oddbird/popover-polyfill) for older browsers
- CSS Anchor Positioning gracefully degrades (popovers still show but may not position optimally)

## Common Modification Points

1. **Change term matching behavior**: Edit `PP_Glossary_Content_Filter::replace_first_occurrence()`
2. **Modify popover HTML**: Edit `PP_Glossary_Content_Filter::create_popover()`
3. **Adjust which content types are processed**: Use Settings page or `pp_glossary_disabled_post_types` filter
4. **Adjust which HTML tags are excluded**: Use Settings page or `pp_glossary_excluded_tags` filter
5. **Customize block output**: Edit `PP_Glossary_Blocks::render_glossary_list_block()`
6. **Add more custom fields**: Modify `Meta_Boxes::render_meta_box()` and `save_meta_boxes()`
7. **Change popover positioning**: Modify CSS anchor positioning rules in `assets/css/glossary.css` (see `aside[popover]` and `@position-try` rules)

## Security Considerations

- All output properly escaped:
  - `esc_attr()` for HTML attributes
  - `esc_html()` for text content
  - `esc_url()` for URLs
  - `wp_kses_post()` for WYSIWYG content
  - `sanitize_textarea_field()` for short description
  - `sanitize_text_field()` for synonyms
- No direct file access checks (`if (!defined('WPINC'))`)
- Settings sanitized via `sanitize_settings()` callback
- Block attributes sanitized by WordPress block API
- Nonce verification for meta box saves
- Capability checks before saving

## WordPress Hooks Used

- `plugins_loaded` - Initialize plugin components
- `init` (via post-type) - Register post type and block
- `add_meta_boxes` - Register custom meta boxes
- `save_post_pp_glossary` - Save custom field data
- `admin_enqueue_scripts` - Load admin JavaScript for synonyms
- `the_content` (priority 20) - Filter content for term replacement
- `wp_enqueue_scripts` - Load CSS and JavaScript
- `admin_menu` - Add settings page
- `admin_init` - Register settings
- `wpseo_schema_graph` (priority 10) - Add schema to Yoast SEO graph (when Yoast is active)
- `register_activation_hook` - Flush rewrite rules on activation
- `register_deactivation_hook` - Clean up on deactivation

## Setup Workflow

Important: Users must complete this setup:

1. Create a page and add the Glossary List block
2. Go to Glossary > Settings and select that page
3. Add glossary entries (title + custom fields)
4. Terms will auto-link in content, "Read more" links point to the selected page

Without step 2, "Read more" links won't appear in popovers.

## Synonym Data Structure

Synonyms are stored as a simple array of strings:

```php
['CLS', 'layout shift']
```

## Testing Tips

### General Testing
- Test with overlapping terms (e.g., "CLS" and "Cumulative Layout Shift")
- Check keyboard navigation (Tab, Enter, Space, Escape)
- Test with screen reader to verify ARIA attributes
- Verify terms maintain surrounding text color
- Check that only first occurrence is linked per term
- Test synonym functionality (add/remove in admin)
- Verify glossary page doesn't highlight its own terms
- Check popover positioning near viewport edges (CSS Anchor Positioning should handle this)
- Test case-sensitive matching (enable on entry, verify exact case required)
- Test disable auto-linking (enable on entry, verify it appears in glossary but not linked in content)
- Verify only one popover can be open at a time
- Verify clicking outside popover closes it (light dismiss)
- Test excluded HTML tags setting (add/remove tags, verify terms are not highlighted within those tags)
- Test excluded post types setting (enable for a post type, verify terms are not highlighted in that post type)

### Schema Testing

**With Yoast SEO:**
1. Install and activate Yoast SEO
2. View glossary page source
3. Look for JSON-LD script tag with `@type: "DefinedTermSet"`
4. Verify all entries appear in `hasDefinedTerm` array
5. Test with [Google Rich Results Test](https://search.google.com/test/rich-results)
6. Verify no duplicate schema markup appears

**Without Yoast SEO:**
1. Deactivate Yoast SEO
2. View glossary page source
3. Look for `itemscope itemtype="https://schema.org/DefinedTermSet"` on main div
4. Verify each entry has `itemscope itemtype="https://schema.org/DefinedTerm"`
5. Check all `itemprop` attributes are present (name, description, url)
6. Verify multiple `<meta itemprop="alternateName">` tags exist for each synonym
7. Test with [Google Rich Results Test](https://search.google.com/test/rich-results)
8. Verify Microdata is properly nested and alternateName appears as an array

**Validation:**
- Use [Schema.org Validator](https://validator.schema.org/)
- Use [Google Rich Results Test](https://search.google.com/test/rich-results)
- Check for any warnings or errors
- Verify all required properties are present
