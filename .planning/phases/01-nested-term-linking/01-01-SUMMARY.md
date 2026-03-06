---
phase: 01-nested-term-linking
plan: 01
subsystem: utility
tags: [term-linking, utilities, css]
requires: []
provides: [term-linker-utility, nested-link-styling]
affects: [01-02, 01-03]
tech-stack:
  added: []
  patterns: [static-utility-class, request-caching]
key-files:
  created: [includes/class-term-linker.php]
  modified: [assets/css/glossary.css]
decisions:
  - id: term-linker-static
    choice: "Implemented Term_Linker as static utility class"
    rationale: "Matches existing plugin architecture (Content_Filter, Settings all use static methods), no instance state needed"
  - id: request-level-cache
    choice: "Cache entries for request duration only"
    rationale: "Prevents repeated queries during single page render, avoids stale data across requests"
  - id: link-not-popover
    choice: "Generated links point to glossary page anchors, not popovers"
    rationale: "Consistent with Phase 1 decision: one level of nesting, links to anchors not nested popovers"
metrics:
  duration: 1 minute
  tasks-completed: 2
  commits: 2
  files-changed: 2
completed: 2026-02-04
---

# Phase 01 Plan 01: Term Linker Foundation Summary

**One-liner:** Static Term_Linker utility with request caching that transforms text by linking glossary terms to anchor URLs

## What Was Built

Created the foundational `Term_Linker` utility class that enables nested term linking throughout the plugin. This class provides a single static method `link_terms_in_text()` that processes text and replaces glossary terms with anchor links to the glossary page.

The class implements request-level caching to prevent repeated database queries and follows the same architectural patterns as existing plugin classes (static methods, no instance state).

## Technical Implementation

### Term_Linker Class (`includes/class-term-linker.php`)

**Core method signature:**
```php
public static function link_terms_in_text( string $text, int $exclude_id = 0 ): string
```

**Key features:**
- **Request-level caching:** `$entries_cache` static property prevents repeated queries
- **Entry exclusion:** `$exclude_id` parameter prevents self-links (e.g., "SEO" entry won't link to itself)
- **Case sensitivity support:** Respects per-entry `case_sensitive` flag
- **Synonym matching:** Processes both primary titles and synonym terms
- **Longest-first sorting:** Handles overlapping terms correctly (same logic as Content_Filter)
- **Auto-link respect:** Skips entries with `disable_autolink` enabled

**Entry data structure:**
```php
[
    'id'             => $post_id,
    'slug'           => sanitize_title( get_the_title() ),
    'title'          => get_the_title(),
    'terms'          => [ 'Primary Title', 'Synonym1', 'Synonym2' ],
    'case_sensitive' => false,
]
```

**Generated HTML:**
```html
<a href="{glossary_url}#{slug}" class="pp-glossary-link">{matched_term}</a>
```

**Regex pattern:**
- Case-insensitive: `/\b({term})\b(?![^<]*>)/iu`
- Case-sensitive: `/\b({term})\b(?![^<]*>)/u`
- Word boundaries ensure whole-word matches only
- Negative lookahead prevents matching inside HTML tags

### CSS Styling (`assets/css/glossary.css`)

Added `.pp-glossary-link` class that mirrors existing `dfn.pp-glossary-term span` styling:
- Dotted underline using CSS custom properties
- Color inherited from surrounding content
- Hover and focus states defined
- Focus-visible outline for keyboard navigation
- No `cursor: help` (these are regular links, not popover triggers)

## Integration Points

**Dependencies:**
- `PP_Glossary\Settings::get_glossary_page_url()` - Gets base URL for links
- `PP_Glossary\Meta_Boxes::get_entry_data()` - Retrieves entry metadata
- WordPress `WP_Query` - Fetches published glossary entries

**Used by (future plans):**
- Plan 01-02: Content_Filter will use this to link terms in popover descriptions
- Plan 01-03: Blocks class will use this to link terms in glossary list block

## Deviations from Plan

None - plan executed exactly as written.

## Testing Performed

**Code Quality:**
- ✅ `composer lint` - No PHP syntax errors
- ✅ `composer phpstan` - No type errors
- ✅ `composer check-cs` - WordPress coding standards pass

**Verification:**
- ✅ Term_Linker class exists at `includes/class-term-linker.php`
- ✅ CSS contains `.pp-glossary-link` selector
- ✅ Class autoloadable (follows existing namespace pattern)

## Known Limitations

- **No HTML tag exclusion:** Unlike Content_Filter, this doesn't skip excluded tags (e.g., `<a>`, `<h1>`-`<h6>`). This is intentional - nested linking is controlled by context (popover descriptions, glossary entries) rather than tag-based exclusion.
- **Replaces first occurrence only:** Like Content_Filter, only the first match of each term is linked per text chunk.
- **No post type filtering:** Unlike Content_Filter, this doesn't respect excluded post types setting. The utility operates on raw text, not post context.

## Next Phase Readiness

**Blockers:** None

**Prerequisites met for Plan 01-02:**
- ✅ Term_Linker class available for Content_Filter integration
- ✅ Styling ready for nested links

**Prerequisites met for Plan 01-03:**
- ✅ Term_Linker class available for Blocks integration
- ✅ Styling ready for nested links

## Files Changed

**Created:**
- `includes/class-term-linker.php` (180 lines)

**Modified:**
- `assets/css/glossary.css` (+24 lines)

## Commits

1. **df516dd** - `feat(01-01): create Term_Linker utility class`
   - Created Term_Linker class with link_terms_in_text() method
   - Implemented request-level caching
   - Added entry exclusion, case sensitivity, synonym support

2. **d86f81d** - `style(01-01): add CSS for nested glossary links`
   - Added .pp-glossary-link class styling
   - Matched existing term trigger styles
   - Removed cursor: help (regular links, not popovers)

## Decisions Made

1. **Static utility class architecture**
   - Matches existing plugin patterns (Settings, Content_Filter)
   - No instance state needed
   - Simple to use: `Term_Linker::link_terms_in_text($text, $exclude_id)`

2. **Request-level caching only**
   - Cache cleared between page requests (no persistent cache)
   - Prevents stale data issues
   - Performance benefit within single request (multiple calls to link_terms_in_text)

3. **Anchor links, not nested popovers**
   - Follows Phase 1 decision: one level of nesting only
   - Links point to `{glossary_url}#{slug}` anchors
   - Simpler implementation, better performance

4. **No HTML tag/post type filtering in utility**
   - Content_Filter has context-aware filtering (excluded tags, post types)
   - Term_Linker operates on raw text without context
   - Calling code controls what text gets processed

## Performance Notes

**Request-level caching:**
- First call to `link_terms_in_text()`: 1 database query (all entries)
- Subsequent calls in same request: 0 queries (cached)
- Cache automatically cleared between requests

**Query efficiency:**
- Single query fetches all entries at once
- Results sorted once per request
- No N+1 query problems

## Architecture Notes

**Why static methods?**
This utility follows the existing plugin architecture where most classes use static methods (Settings, Content_Filter, Meta_Boxes, etc.). No instance state is needed since the cache is request-scoped.

**Why request-level cache instead of object cache?**
- Simpler implementation (no cache invalidation logic needed)
- Prevents stale data across requests
- Sufficient performance benefit (prevents repeated queries during single page render)
- Matches WordPress patterns (similar to how `get_posts()` caching works)

**Why reuse Content_Filter logic?**
The entry retrieval and sorting logic intentionally mirrors Content_Filter to ensure consistent term matching behavior across the plugin.
