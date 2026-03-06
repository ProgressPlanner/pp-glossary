# Phase 1: Nested Term Linking - Research

**Researched:** 2026-02-04
**Domain:** WordPress PHP plugin enhancement (brownfield)
**Confidence:** HIGH

## Summary

This phase adds nested term linking to the PP Glossary plugin, allowing terms mentioned within glossary definitions (both short and long descriptions) to link to their respective entries on the glossary page. This is a brownfield enhancement to an existing, well-structured WordPress plugin.

The implementation is straightforward because the codebase already contains all necessary components:
- Term matching logic in `Content_Filter::replace_first_occurrence()`
- Glossary entries retrieval in `Content_Filter::get_glossary_entries()` and `Blocks::get_grouped_entries()`
- Styling for glossary terms in `assets/css/glossary.css`

The primary work involves creating a new method that transforms descriptions by replacing glossary terms with anchor links, then calling this method in two locations: the block renderer and the popover creator. The key constraint is preventing self-links (a term should not link to itself within its own definition).

**Primary recommendation:** Create a single `link_terms_in_text()` utility method that both the block renderer and popover creator can call, passing the current entry's ID to exclude self-links.

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| WordPress Core | 6.0+ | Plugin framework | Target platform |
| PHP | 7.4+ | Server-side language | WordPress minimum requirement |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `preg_replace_callback` | PHP core | Regex replacement with callback | Term matching with self-link prevention |
| `wp_kses_post()` | WP core | HTML sanitization | Sanitizing linked output |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| New utility class | Add to Content_Filter | Content_Filter is for content filtering; a helper function or new utility class keeps concerns separated |

**Installation:**
No additional dependencies required. This is pure PHP/WordPress development.

## Architecture Patterns

### Recommended Project Structure
```
includes/
├── class-content-filter.php    # Modify create_popover() to call link_terms_in_text()
├── class-blocks.php            # Modify render_glossary_list_block() to call link_terms_in_text()
├── class-term-linker.php       # NEW: Utility class for term linking
assets/
└── css/glossary.css            # Add styles for nested links (minimal changes)
```

### Pattern 1: Dedicated Term Linker Class
**What:** A new static utility class `PP_Glossary\Term_Linker` that handles term-to-link transformation
**When to use:** Always - keeps the linking logic centralized and testable
**Example:**
```php
// Source: Pattern derived from existing Content_Filter implementation
namespace PP_Glossary;

class Term_Linker {
    /**
     * Link glossary terms in text content.
     *
     * @param string $text        The text to process.
     * @param int    $exclude_id  Post ID to exclude (prevents self-links).
     * @return string Text with terms linked.
     */
    public static function link_terms_in_text( string $text, int $exclude_id = 0 ): string {
        // Get glossary page URL
        $glossary_url = Settings::get_glossary_page_url();
        if ( empty( $glossary_url ) ) {
            return $text;
        }

        // Get all glossary entries, sorted by term length (longest first)
        $entries = self::get_linkable_entries( $exclude_id );

        // Process each entry
        foreach ( $entries as $entry ) {
            $text = self::replace_terms_with_links( $text, $entry, $glossary_url );
        }

        return $text;
    }
}
```

### Pattern 2: Term Matching with Self-Exclusion
**What:** Pass current entry ID to exclude when processing definitions
**When to use:** Always - prevents recursive self-links
**Example:**
```php
// Source: Derived from Content_Filter::get_glossary_entries()
private static function get_linkable_entries( int $exclude_id ): array {
    $entries = [];

    $query = new \WP_Query([
        'post_type'      => 'pp_glossary',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'post__not_in'   => $exclude_id > 0 ? [ $exclude_id ] : [],
    ]);

    // ... process entries
    return $entries;
}
```

### Pattern 3: Link HTML Generation
**What:** Generate simple anchor links (not popovers) for nested terms
**When to use:** For all nested term links
**Example:**
```php
// Source: Follows existing plugin HTML patterns
private static function create_term_link( string $term, string $slug, string $glossary_url ): string {
    return sprintf(
        '<a href="%s#%s" class="pp-glossary-link">%s</a>',
        esc_url( $glossary_url ),
        esc_attr( $slug ),
        esc_html( $term )
    );
}
```

### Anti-Patterns to Avoid
- **Nested popovers:** Creating popover-within-popover creates accessibility nightmares and complex state management. Links to anchors are the correct approach (already decided).
- **Recursive term linking:** Linking within linked text creates exponential complexity. One level only (already decided).
- **Processing in-place without ID exclusion:** Always pass the current entry ID to prevent self-links.
- **Duplicating matching logic:** Use a single method called from both locations rather than implementing twice.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Term matching regex | Custom pattern per location | Existing pattern from `Content_Filter::replace_first_occurrence()` | Already tested, handles edge cases |
| HTML tag exclusion | New tag-skipping logic | Existing excluded tags system from Settings | Consistency with main content filter |
| Case sensitivity handling | New case logic | Existing `$entry['case_sensitive']` flag | Already implemented in codebase |
| Synonym matching | Separate synonym logic | Existing terms array structure with title + synonyms | Already handles synonyms in `get_glossary_entries()` |

**Key insight:** The codebase already has all the term-matching complexity solved. The nested linking just needs to reuse this logic with output changed from popover to anchor link.

## Common Pitfalls

### Pitfall 1: Self-Linking
**What goes wrong:** The term "API" in the "API" entry's description gets linked back to itself, creating a useless circular link.
**Why it happens:** Processing all entries without exclusion.
**How to avoid:** Pass `$exclude_id` parameter to `get_linkable_entries()` and use `post__not_in` in the WP_Query.
**Warning signs:** During testing, check that each entry's definition does not contain a link to itself.

### Pitfall 2: Double-Linking in Content Filter
**What goes wrong:** A term in regular content gets a popover, then the short description in that popover also creates a nested link, then the nested term in content also gets processed.
**Why it happens:** Multiple passes of term replacement.
**How to avoid:** Nested links in popovers are processed once. The content filter processes the_content which doesn't include the glossary page definitions.
**Warning signs:** Links inside popover HTML getting converted to popovers.

### Pitfall 3: Breaking Existing HTML
**What goes wrong:** Term matching replaces text inside HTML tags or existing links.
**Why it happens:** Regex pattern doesn't properly exclude HTML contexts.
**How to avoid:** Use the same negative lookahead pattern from `Content_Filter`: `/\b({term})\b(?![^<]*>)/`
**Warning signs:** Broken HTML output, links inside href attributes.

### Pitfall 4: Link Styling Inconsistency
**What goes wrong:** Nested links look different from inline term triggers.
**Why it happens:** Using standard link styling instead of dotted underline.
**How to avoid:** Add CSS class `pp-glossary-link` with same dotted underline styling as `dfn.pp-glossary-term span`.
**Warning signs:** Visual inspection shows different underline styles.

### Pitfall 5: Missing Glossary Page URL
**What goes wrong:** Links are generated with empty href or just `#slug`.
**Why it happens:** Glossary page not configured in settings.
**How to avoid:** Check `Settings::get_glossary_page_url()` early and skip linking if empty.
**Warning signs:** Links with malformed URLs in output.

### Pitfall 6: Performance on Large Glossaries
**What goes wrong:** Slow page loads when glossary has many entries.
**Why it happens:** Multiple WP_Query calls and regex operations per entry.
**How to avoid:** Cache glossary entries within request using static property (already done in existing code).
**Warning signs:** Increased page generation time after implementation.

## Code Examples

### Entry Point 1: Block Renderer
```php
// Source: class-blocks.php modification
// In render_glossary_list_block(), when outputting description:

$description = ! empty( $entry['long_description'] )
    ? $entry['long_description']
    : $entry['short_description'];

if ( ! empty( $description ) ) {
    // Link terms in the description, excluding self
    $description = Term_Linker::link_terms_in_text( $description, $entry['id'] );
    ?>
    <div class="glossary-long-description" <?php echo Schema::get_itemprop( 'description' ); ?>>
        <?php echo wp_kses_post( $description ); ?>
    </div>
    <?php
}
```

### Entry Point 2: Popover Creator
```php
// Source: class-content-filter.php modification
// In create_popover(), when outputting short description:

if ( ! empty( $entry['short_description'] ) ) {
    $short_desc = Term_Linker::link_terms_in_text(
        $entry['short_description'],
        $entry['id']
    );
    $popover_html .= sprintf( '<p>%s</p>', esc_html( $short_desc ) );
}
```

**Note:** For short description, we need to be careful about escaping. Since the linked text contains HTML (`<a>` tags), we should use `wp_kses_post()` instead of `esc_html()`.

### CSS for Nested Links
```css
/* Source: Derived from existing dfn.pp-glossary-term span styles */
.pp-glossary-link {
    color: inherit;
    text-decoration: underline;
    text-decoration-style: dotted;
    text-decoration-thickness: 1px;
    text-decoration-color: var(--glossary-underline-color, rgba(0, 0, 0, 0.4));
    text-underline-offset: 3px;
}

.pp-glossary-link:hover,
.pp-glossary-link:focus {
    text-decoration-color: var(--glossary-underline-hover-color, rgba(0, 0, 0, 0.7));
}
```

### Complete Term Linker Class Structure
```php
<?php
namespace PP_Glossary;

class Term_Linker {
    /**
     * Cache for glossary entries (avoids repeated queries)
     *
     * @var array<int, array<string, mixed>>|null
     */
    private static $entries_cache = null;

    /**
     * Link glossary terms in text content.
     *
     * @param string $text        The text to process.
     * @param int    $exclude_id  Post ID to exclude (prevents self-links).
     * @return string Text with terms linked.
     */
    public static function link_terms_in_text( string $text, int $exclude_id = 0 ): string {
        $glossary_url = Settings::get_glossary_page_url();
        if ( empty( $glossary_url ) || empty( $text ) ) {
            return $text;
        }

        $entries = self::get_entries();

        foreach ( $entries as $entry ) {
            // Skip self
            if ( $entry['id'] === $exclude_id ) {
                continue;
            }

            $text = self::replace_first_term_occurrence( $text, $entry, $glossary_url );
        }

        return $text;
    }

    /**
     * Get glossary entries (cached).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function get_entries(): array {
        if ( self::$entries_cache !== null ) {
            return self::$entries_cache;
        }

        // Reuse existing entry retrieval logic pattern
        // Returns entries sorted by longest term first
        // ...

        return self::$entries_cache;
    }

    /**
     * Replace first occurrence of term with link.
     *
     * @param string $text         The text.
     * @param array  $entry        The glossary entry.
     * @param string $glossary_url The glossary page URL.
     * @return string Modified text.
     */
    private static function replace_first_term_occurrence(
        string $text,
        array $entry,
        string $glossary_url
    ): string {
        foreach ( $entry['terms'] as $term ) {
            $flags   = $entry['case_sensitive'] ? 'u' : 'iu';
            $pattern = '/\b(' . preg_quote( $term, '/' ) . ')\b(?![^<]*>)/' . $flags;

            if ( preg_match( $pattern, $text, $matches, PREG_OFFSET_CAPTURE ) ) {
                $matched_term = $matches[1][0];
                $offset       = $matches[1][1];

                $link = sprintf(
                    '<a href="%s#%s" class="pp-glossary-link">%s</a>',
                    esc_url( $glossary_url ),
                    esc_attr( $entry['slug'] ),
                    esc_html( $matched_term )
                );

                return substr_replace( $text, $link, $offset, strlen( $matched_term ) );
            }
        }

        return $text;
    }
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Static glossary pages | Dynamic term linking | Current best practice | Better user experience |
| Manual cross-references | Automatic term detection | Standard in glossary plugins | Reduces maintenance burden |

**Deprecated/outdated:**
- None applicable - this is greenfield functionality being added to an existing plugin.

## Open Questions

1. **Should nested links be limited to first occurrence per definition?**
   - What we know: The content filter only links first occurrence per term per content piece.
   - What's unclear: Should definitions follow the same rule?
   - Recommendation: Yes, match existing behavior for consistency. Implementation already handles this.

2. **Should excluded tags setting apply to nested links?**
   - What we know: Settings has `excluded_tags` like `['a', 'h1', ...]`.
   - What's unclear: Should the same exclusions apply within definitions?
   - Recommendation: Yes, use same exclusions for consistency. Terms inside headings/links in definitions should not be double-linked.

3. **How to handle wp_kses_post in short descriptions?**
   - What we know: Short descriptions are currently escaped with `esc_html()` in `create_popover()`.
   - What's unclear: After adding links, the HTML needs to be preserved.
   - Recommendation: Change to `wp_kses_post()` to allow anchor tags. This is safe because the link HTML is generated by our code, not user input.

## Sources

### Primary (HIGH confidence)
- `/Users/joostdevalk/Code/pp-glossary/includes/class-content-filter.php` - Existing term matching implementation
- `/Users/joostdevalk/Code/pp-glossary/includes/class-blocks.php` - Block rendering implementation
- `/Users/joostdevalk/Code/pp-glossary/includes/class-settings.php` - Settings retrieval methods
- `/Users/joostdevalk/Code/pp-glossary/assets/css/glossary.css` - Existing term styling

### Secondary (MEDIUM confidence)
- `/Users/joostdevalk/Code/pp-glossary/CLAUDE.md` - Project documentation and architecture

### Tertiary (LOW confidence)
- None - all research is based on direct codebase analysis

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - This is a WordPress plugin using only core WordPress/PHP functionality
- Architecture: HIGH - Follows existing codebase patterns exactly
- Pitfalls: HIGH - Derived from analyzing actual code paths and edge cases

**Research date:** 2026-02-04
**Valid until:** 90 days (stable WordPress plugin, no external dependencies)
