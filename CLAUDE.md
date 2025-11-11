# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Glossary by Progress Planner (pp-glossary)** is a WordPress plugin that automatically links glossary terms to accessible, semantic popovers that appear on hover/focus. Uses native WordPress custom fields for field management and includes a Gutenberg block for displaying the full glossary.

### Core Functionality

- Registers a custom post type (`pp_glossary`) for glossary entries (title + custom fields only, no editor)
- Uses native WordPress meta boxes for field management (short description, long description, synonyms)
- Automatically transforms first mentions of glossary terms in content into hover-triggered popovers
- Provides a Gutenberg block to display the full glossary with alphabetical navigation
- Settings page to configure which page displays the glossary

## Architecture

### File Structure

```
pp-glossary/
├── pp-glossary.php              # Main plugin file, initialization, hooks
├── includes/
│   ├── post-type.php            # PP_Glossary_Post_Type class - CPT registration
│   ├── meta-boxes.php           # PP_Glossary_Meta_Boxes class - Custom meta boxes
│   ├── content-filter.php       # PP_Glossary_Content_Filter class - Term replacement
│   ├── settings.php             # PP_Glossary_Settings class - Settings page
│   ├── blocks.php               # PP_Glossary_Blocks class - Block registration
│   └── schema.php               # PP_Glossary_Schema class - Schema.org integration
├── blocks/
│   └── glossary-list/
│       ├── block.json           # Block metadata
│       └── editor.js            # Block editor interface (vanilla JS)
└── assets/
    ├── css/glossary.css         # All plugin styles
    └── js/glossary.js           # Hover behavior and accessibility
```

### Key Components

1. **Post Type Registration** (`includes/post-type.php`)
   - Post type slug: `pp_glossary`
   - Supports: `title`, `revisions` (NO editor support)
   - `has_archive` set to `false` (uses block instead)
   - `publicly_queryable` set to `false` (no individual entry pages)

2. **Meta Boxes** (`includes/meta-boxes.php`)
   - Native WordPress meta boxes (no external dependencies)
   - Fields stored as post meta with `_pp_glossary_` prefix
   - Fields:
     - `_pp_glossary_short_description` (textarea, required)
     - `_pp_glossary_long_description` (wp_editor)
     - `_pp_glossary_synonyms` (array of strings)
   - JavaScript inline for adding/removing synonyms dynamically

3. **Content Filter** (`includes/content-filter.php`)
   - Hooks into `the_content` at priority 20
   - Finds first occurrence of each term (case-insensitive, whole word)
   - Generates unique IDs for each popover instance
   - Appends popovers and helper text at end of content
   - Uses settings page URL for "Read more" links (not individual permalinks)
   - Skips glossary page itself to prevent self-linking

4. **Settings Page** (`includes/settings.php`)
   - Submenu under Glossary CPT menu
   - Stores glossary page ID in `pp_glossary_settings` option
   - Methods: `get_glossary_page_id()`, `get_glossary_page_url()`

5. **Block System** (`includes/blocks.php`, `blocks/glossary-list/`)
   - Server-side rendered block using `render_callback`
   - No block attributes (simplified interface)
   - Displays all entries grouped alphabetically with navigation
   - Supports wide and full alignment
   - Shows only long description (short description only in popovers)

6. **Schema.org Integration** (`includes/schema.php`)
   - Detects if Yoast SEO is active using `defined('WPSEO_VERSION')`
   - With Yoast SEO: Hooks into `wpseo_schema_graph` filter to add JSON-LD
   - Without Yoast SEO: Outputs Microdata markup in HTML
   - Implements DefinedTermSet (glossary) and DefinedTerm (entries) schemas
   - Methods: `add_to_yoast_schema_graph()`, `get_microdata_attributes()`, `get_entry_microdata_attributes()`, `get_itemprop()`

## Data Storage

All custom field data is stored as WordPress post meta:

- `_pp_glossary_short_description` (string)
- `_pp_glossary_long_description` (string, HTML allowed)
- `_pp_glossary_synonyms` (array of strings)

Retrieved using `get_post_meta()` and saved using `update_post_meta()`.

## HTML Structure Pattern

The plugin generates highly semantic, accessible HTML with hover triggers and CSS Anchor Positioning:

```html
<dfn id="dfn-{term}-{counter}"
     class="pp-glossary-term"
     style="anchor-name: --dfn-{term}-{counter};">
  <span data-glossary-popover="pop-{term}-{counter}"
        aria-describedby="help-def"
        tabindex="0"
        role="button"
        aria-expanded="false">
    {matched term}
  </span>
</dfn>

<aside id="pop-{term}-{counter}"
       popover="manual"
       role="tooltip"
       aria-labelledby="dfn-{term}-{counter}"
       style="position-anchor: --dfn-{term}-{counter};">
  <strong>{Entry Title}</strong>
  <p>{Short description}</p>
  <p><a href="{glossary_page_url}#{slug}">Read more about {term}</a></p>
</aside>

<p id="help-def" hidden>Hover or focus to see the definition of the term.</p>
```

Key differences from typical popover implementations:
- Uses `popover="manual"` for programmatic control
- Uses `<span>` with `data-glossary-popover` attribute (not button)
- Role is `tooltip` not `note`
- Triggered by hover/focus, not click
- Popover positioned using CSS Anchor Positioning API
- Each dfn defines an `anchor-name` that the popover references with `position-anchor`

## JavaScript Hover Implementation

The plugin uses manual popover control with hover events (`assets/js/glossary.js`):

- **Hover**: Shows popover with 0ms delay, hides with 500ms delay
- **Focus**: Shows popover immediately for keyboard users
- **Popover hover**: Clears hide timeout so users can click "Read more" link
- **Positioning**: Uses CSS Anchor Positioning API for automatic positioning
- **Keyboard**: Enter/Space toggles, Escape closes and returns focus

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
      "description": "Short description",
      "text": "Long description (stripped of HTML)",
      "url": "https://example.com/glossary/#term-slug",
      "termCode": "Synonym1, Synonym2",
      "inDefinedTermSet": "https://example.com/glossary/#glossary"
    }
  ]
}
```

### Microdata Integration

When Yoast SEO is NOT active:
- Adds `itemscope`, `itemtype`, `itemprop` attributes to HTML elements
- Applied directly to the glossary block container and each entry
- All schema helper methods return empty strings when Yoast is active

**HTML Output (Microdata):**
```html
<div class="pp-glossary-block" itemscope itemtype="https://schema.org/DefinedTermSet" itemid="...">
  <meta itemprop="name" content="Glossary Title">

  <article itemscope itemtype="https://schema.org/DefinedTerm" itemprop="hasDefinedTerm">
    <link itemprop="url" href="...">
    <h4 itemprop="name">Term Title</h4>
    <meta itemprop="description" content="Short description">
    <span itemprop="termCode">Synonyms</span>
    <div itemprop="text">Long description</div>
  </article>
</div>
```

### Schema Properties Mapping

| Schema Property | WordPress Data | Location |
|----------------|----------------|----------|
| `name` | Entry title | `get_the_title()` |
| `description` | Short description | `_pp_glossary_short_description` meta |
| `text` | Long description (HTML stripped for JSON-LD) | `_pp_glossary_long_description` meta |
| `url` | Anchor link | `{glossary_page_url}#{slug}` |
| `termCode` | Synonyms (comma-separated) | `_pp_glossary_synonyms` meta |
| `inDefinedTermSet` | Parent glossary reference | `{glossary_page_url}#glossary` |

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
- Uses regex pattern: `/\b({term})\b(?![^<]*>)/iu`
  - `\b` = word boundaries
  - `(?![^<]*>)` = negative lookahead to avoid matching inside HTML tags
  - `i` flag = case-insensitive
  - `u` flag = Unicode support
- Only replaces first occurrence per entry per content piece

### State Management

`PP_Glossary_Content_Filter` uses static properties during filtering:
- `$popover_counter` - ensures unique IDs
- `$popovers` - stores popover HTML for end-of-content appending
- `$helper_added` - tracks if helper text was added
- These reset on each `filter_content()` call

### Settings Integration

- `PP_Glossary_Settings::get_glossary_page_url()` returns the URL of the page containing the glossary block
- "Read more" links use this URL + `#{slug}` anchor (slug-based, not ID-based)
- If no glossary page is set, "Read more" link is omitted

### Block Registration

- Block registered using `register_block_type()` with JSON file
- `render_callback` points to `PP_Glossary_Blocks::render_glossary_list_block()`
- Editor script uses vanilla JavaScript (no JSX, no build)
- Block shows: entry title (H4), synonyms, long description only
- No H2 title at top of block

### Accessibility Features

- Dotted underline (not solid) to indicate definitions without looking like regular links
- `cursor: help` to indicate interactive glossary terms
- `tabindex="0"` makes spans keyboard-focusable
- `aria-expanded` updated via JavaScript when popovers show/hide
- `role="tooltip"` for popovers (appropriate for hover-triggered content)
- 300ms delay on hide prevents accidental dismissal
- Focus returns to trigger when Escape is pressed
- Popover positioning accounts for viewport boundaries

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

1. **Change hover delay**: Edit `HIDE_DELAY` constant in `assets/js/glossary.js`
2. **Change term matching behavior**: Edit `PP_Glossary_Content_Filter::replace_first_occurrence()`
3. **Modify popover HTML**: Edit `PP_Glossary_Content_Filter::create_popover()`
4. **Adjust which content types are processed**: Add conditional logic in `PP_Glossary_Content_Filter::filter_content()`
5. **Customize block output**: Edit `PP_Glossary_Blocks::render_glossary_list_block()`
6. **Add more custom fields**: Modify `PP_Glossary_Meta_Boxes::render_meta_box()` and `save_meta_boxes()`
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

Synonyms changed from ACF repeater format to simple array:

**Old (ACF)**: `[['term' => 'CLS'], ['term' => 'layout shift']]`
**New (Native)**: `['CLS', 'layout shift']`

Code updated throughout to handle simple string array instead of nested associative arrays.

## Testing Tips

### General Testing
- Test with overlapping terms (e.g., "CLS" and "Cumulative Layout Shift")
- Verify hover delay doesn't interfere with "Read more" clicks
- Check keyboard navigation (Tab, Enter, Space, Escape)
- Test with screen reader to verify ARIA attributes
- Verify terms maintain surrounding text color
- Check that only first occurrence is linked per term
- Test synonym functionality (add/remove in admin)
- Verify glossary page doesn't highlight its own terms
- Check popover positioning near viewport edges (CSS Anchor Positioning should handle this)

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
5. Check all `itemprop` attributes are present (name, description, text, url, termCode)
6. Test with [Google Rich Results Test](https://search.google.com/test/rich-results)
7. Verify Microdata is properly nested

**Validation:**
- Use [Schema.org Validator](https://validator.schema.org/)
- Use [Google Rich Results Test](https://search.google.com/test/rich-results)
- Check for any warnings or errors
- Verify all required properties are present
