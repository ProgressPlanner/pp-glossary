# WordPress 7.2 compatibility validation

Validated on 18 November 2026 against WordPress **7.2-RC1-65566**, official
[WordPress source commit ed58a2902b47d6bf9de8d817f75cbe851782dc36](https://github.com/WordPress/WordPress/commit/ed58a2902b47d6bf9de8d817f75cbe851782dc36).
The `Tested up to: 7.2` readme header is based on this release candidate.
Repeat these checks against the final 7.2 release when it becomes available.

## Environment

- Disposable WordPress installation with a real MariaDB database, isolated from other local sites.
- PHP 8.3.36, MariaDB 12.3.3, Chrome controlled with Playwright.
- Plugin PHP and assets from develop commit `f5d650ce709497f04f7635c2567405f8242bec8c`.
  This change modifies build workflows and metadata, not that runtime code.
- Plugin and core files mounted read-only; external HTTP requests disabled.
- `WP_DEBUG` enabled, PHP errors logged, error display disabled.

## Passing checks

1. Installed WordPress and activated the plugin; confirmed the glossary post type,
   glossary block and REST type endpoint are registered.
2. Created three published entries through the real meta-box save handler with a
   valid nonce: a term with a synonym, a case-sensitive term and a term with
   automatic linking disabled. Verified persisted metadata.
3. Rendered a post through the real content filter: only the first occurrence of
   each enabled term becomes a popover, synonyms match, case sensitivity works,
   and excluded links and headings remain unchanged.
4. Rendered the glossary block with all three entries, description cross-links,
   DefinedTermSet and DefinedTerm microdata, and working glossary anchor URLs.
5. Called the schema graph callback with a glossary-page query and verified the
   three entries in its output. This is a callback check, not a live Yoast SEO integration test.
6. Requested the post through REST and RSS and confirmed popovers are omitted.
7. Opened a popover with the mouse and keyboard in Chrome and checked its text
   and `aria-expanded` state.
8. Edited and saved an entry in the classic editor, then verified the changed
   description in the public post response.
9. Loaded the glossary page in the block editor, saved a changed page title and
   verified the saved title and unchanged glossary block content in the database.
10. Saved glossary settings and verified the selected page and default excluded
    tags persisted; loaded the public glossary page with all three entries and
    no self-linking popovers.

No plugin-origin PHP errors were logged. Core update checks produced expected
network warnings because external HTTP requests were blocked.

## Limits and repeat procedure

This validates the main glossary flows on one PHP version and browser. It does
not cover multisite, every theme/browser, the full Yoast SEO plugin, or the final
WordPress 7.2 release. The separately reported security and correctness findings
are tracked in their own pull requests; this compatibility check does not
supersede those fixes.

For a repeat, install the specified core revision in a disposable database,
activate the plugin, create the three entry variants described above, select a
page containing the Glossary List block in settings, and exercise the checks in
order. Inspect PHP logs and browser errors, and run `composer check-cs`,
`composer lint` and `composer phpstan` before updating this record.
