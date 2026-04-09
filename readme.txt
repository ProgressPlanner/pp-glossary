=== Glossary ===
Contributors: joostdevalk, aristath, filipi, progressplanner
Tags: glossary, definitions, popover, accessibility, schema
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Create an accessible WordPress glossary that auto-links terms and shows click-to-open definitions across your site.

== Description ==

Glossary helps you publish a cleaner, more useful WordPress glossary. Add glossary terms once, automatically link their first mention in your content, show readers a short definition in a click-triggered popover, and send them to a full glossary page when they want more context.

It is built for sites that want to explain terminology without interrupting the reading experience. That makes it useful for documentation sites, product teams, publishers, educational content, and any website that uses specialist language.

= What you can do with Glossary =

* Create glossary entries with a short definition, long description, and synonyms.
* Automatically link the first matching mention of a glossary term in your content.
* Show definitions in accessible click-to-open popovers instead of hover-only tooltips.
* Publish a full WordPress glossary page with alphabetical navigation using the included Glossary List block.
* Add Schema.org `DefinedTerm` and `DefinedTermSet` markup to glossary content.
* Fine-tune matching with case-sensitive terms and per-entry auto-linking controls.

= Why site owners choose it =

* Helps readers understand important terms without leaving the page.
* Keeps content cleaner than repeating the same explanation everywhere.
* Improves usability with semantic HTML, keyboard support, and screen-reader friendly interactions.
* Works with native WordPress functionality and does not require an external dependency.

= How it works =

1. Add a glossary entry for each term you want to explain.
2. Write a short description for the popover and an optional longer description for the glossary page.
3. Add synonyms if people use different names for the same term.
4. Create a page and insert the **Glossary List** block.
5. Select that page in **Glossary > Settings**.
6. Glossary automatically links the first mention of matching terms in supported content.

= Built for accessibility and SEO =

Glossary uses semantic HTML like `<dfn>` and `<aside>`, supports keyboard interaction, and avoids hover-only behavior. It also adds structured data for glossary entries.

* **With Yoast SEO active:** glossary entities are added to Yoast's schema graph.
* **Without Yoast SEO:** Glossary outputs equivalent Microdata markup directly in the front end.

= Browser support =

Glossary uses modern web platform features for the best popover experience.

* Chrome / Edge 114+
* Safari 17+
* Firefox support is still limited because Popover API and CSS Anchor Positioning support is evolving

Older browsers may still display glossary content, but popover behavior and positioning can be less polished.

== Installation ==

= Automatic installation =

1. Log in to your WordPress admin area.
2. Go to **Plugins > Add New**.
3. Search for **Glossary**.
4. Click **Install Now**.
5. Click **Activate**.

= Manual installation =

1. Download the plugin ZIP or this repository.
2. Upload the plugin to the `/wp-content/plugins/` directory.
3. Activate the plugin through the **Plugins** screen in WordPress.

= After activation =

1. Go to **Glossary > Add new entry** and create your first glossary term.
2. Add a short description, optional long description, and any synonyms.
3. Create a page for your glossary.
4. Insert the **Glossary List** block on that page.
5. Go to **Glossary > Settings** and select the page you want to use as the glossary page.

== Frequently Asked Questions ==

= What is this WordPress glossary plugin best for? =

Glossary is ideal when your site uses terms, acronyms, or concepts that readers may not know yet. It gives you one place to manage definitions and automatically surfaces them inside your content.

= Does Glossary require any other plugins? =

No. Glossary uses native WordPress functionality and works on its own.

= How does glossary term linking work? =

Glossary scans supported content and links the first matching mention of each glossary term. When a visitor clicks that linked term, the plugin opens a popover with the short definition and a link to read more on the glossary page.

= Can I create a full glossary page in WordPress? =

Yes. The plugin includes a **Glossary List** block that lets you publish a full glossary page with alphabetical navigation and all your glossary entries.

= Can I add synonyms or alternate names? =

Yes. Each glossary entry can include synonyms, so alternate spellings, abbreviations, or related names can trigger the same definition.

= Can I stop specific terms from auto-linking? =

Yes. You can disable auto-linking per glossary entry if you want a term to appear in the glossary page without being linked in content.

= Does it work with Yoast SEO? =

Yes. Glossary integrates with Yoast SEO and adds glossary structured data to Yoast's schema graph when Yoast is active.

= Can I customize the styling? =

Yes. Glossary uses CSS custom properties, so you can adapt colors and visual styling in your theme or custom CSS.

== Screenshots ==

1. Create a WordPress glossary entry with a short definition, long description, and synonyms.
2. Add the Glossary List block to publish your glossary page in the block editor.
3. Show a click-triggered glossary definition popover directly inside your content.
4. Browse the full glossary page with alphabetical navigation and term descriptions.
5. Choose the glossary page and control where glossary term linking appears.

== Changelog ==

= 1.4.0 =

* Plugin renamed to Your Glossary with new slug (your-glossary).
* Renamed all internal identifiers (namespace, constants, functions, text domain).
* Added migration to rename database identifiers (post type, meta key, option, block name).
* Deprecated `pp_glossary_excluded_tags` and `pp_glossary_disabled_post_types` filters — use `your_glossary_excluded_tags` and `your_glossary_disabled_post_types` instead. Old filters still work but trigger a `_doing_it_wrong` notice.
* Plugin reactivation required after update due to file/directory rename.

= 1.3.1 =

* Added uninstall.php to clean up all plugin data (glossary posts, meta, and options) on deletion.

= 1.3.0 =

* Added nested term linking inside glossary descriptions and popovers.
* Consolidated glossary entry queries into shared helper functions for better performance.
* Improved accessibility with added screen reader text and a better popover reading flow.
* Changed the cursor to `help` for glossary terms to better indicate interactive definitions.
* Updated banners and optimized images.
* Added FAIR verification with hourly verification of PLC DID and FAIR metadata.

= 1.2.0 =

* Excluded glossary entries from Yoast SEO indexables and XML sitemaps (entries have no public pages).
* Excluded glossary entries from WordPress search results.
* Removed revision support because glossary data is stored in post meta.
* Added a setting to configure excluded HTML tags where glossary terms should not be highlighted.
* Added a setting to exclude specific post types from glossary term highlighting.
* Disabled glossary term highlighting during feeds and REST requests.

= 1.1.0 =

* Added case sensitive matching for glossary entries. ([GH issue #23](https://github.com/ProgressPlanner/pp-glossary/issues/23))
* Added the option to disable auto-linking for specific glossary entries. ([GH issue #19](https://github.com/ProgressPlanner/pp-glossary/issues/19))
* Consolidated glossary entry metadata into a single post meta field for better performance.
* Added an automatic migration system for seamless upgrades.
* Improved the Glossary List block fallback and editor experience.
* Delivered major accessibility fixes with help from [@joedolson](https://github.com/joedolson).

= 1.0.3 =

* Fixed a non-bumped version number.

= 1.0.2 =

* Asset fixes.

= 1.0.1 =

* Minor bug fixes.

= 1.0.0 =

* Initial release.
* Custom post type for glossary entries.
* Native WordPress custom fields for short descriptions, long descriptions, and synonyms.
* Automatic term linking for the first matching occurrence.
* Glossary List block.
* Settings page for glossary page configuration.
* Schema.org structured data support.
* Semantic, accessible HTML.
* Responsive design with CSS custom properties.
* Full keyboard and screen reader support.

== Upgrade Notice ==

= 1.3.1 =

Updates plugin cleanup on deletion so plugin data is properly removed when you uninstall Glossary.
