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
│   └── blocks.php               # PP_Glossary_Blocks class - Block registration
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

## Data Storage

All custom field data is stored as WordPress post meta:

- `_pp_glossary_short_description` (string)
- `_pp_glossary_long_description` (string, HTML allowed)
- `_pp_glossary_synonyms` (array of strings)

Retrieved using `get_post_meta()` and saved using `update_post_meta()`.

## HTML Structure Pattern

The plugin generates highly semantic, accessible HTML with hover triggers:

```html
<dfn id="dfn-{term}-{counter}" class="pp-glossary-term">
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
       aria-labelledby="dfn-{term}-{counter}">
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
- Popover positioned with JavaScript using `position: fixed`

## JavaScript Hover Implementation

The plugin uses manual popover control with hover events (`assets/js/glossary.js`):

- **Hover**: Shows popover with 0ms delay, hides with 300ms delay
- **Focus**: Shows popover immediately for keyboard users
- **Popover hover**: Clears hide timeout so users can click "Read more" link
- **Positioning**: Custom JavaScript positions popover 4px below trigger element
- **Keyboard**: Enter/Space toggles, Escape closes and returns focus

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

Requires Popover API support:
- Chrome/Edge 114+
- Safari 17+
- Firefox (experimental)

JavaScript checks for support and logs warning if unavailable. Recommend [Popover API polyfill](https://github.com/oddbird/popover-polyfill) for older browsers.

## Common Modification Points

1. **Change hover delay**: Edit `HIDE_DELAY` constant in `assets/js/glossary.js`
2. **Change term matching behavior**: Edit `PP_Glossary_Content_Filter::replace_first_occurrence()`
3. **Modify popover HTML**: Edit `PP_Glossary_Content_Filter::create_popover()`
4. **Adjust which content types are processed**: Add conditional logic in `PP_Glossary_Content_Filter::filter_content()`
5. **Customize block output**: Edit `PP_Glossary_Blocks::render_glossary_list_block()`
6. **Add more custom fields**: Modify `PP_Glossary_Meta_Boxes::render_meta_box()` and `save_meta_boxes()`
7. **Change popover positioning**: Edit `positionPopover()` in `assets/js/glossary.js`

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

- Test with overlapping terms (e.g., "CLS" and "Cumulative Layout Shift")
- Verify hover delay doesn't interfere with "Read more" clicks
- Check keyboard navigation (Tab, Enter, Space, Escape)
- Test with screen reader to verify ARIA attributes
- Verify terms maintain surrounding text color
- Check that only first occurrence is linked per term
- Test synonym functionality (add/remove in admin)
- Verify glossary page doesn't highlight its own terms
- Check popover positioning near viewport edges
