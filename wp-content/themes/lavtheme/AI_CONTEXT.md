# AI_CONTEXT.md — lavtheme

A compact orientation guide. Read this first, then open the relevant `inc/` file.

## Contents
1. [Overview](#1-overview)
2. [Architecture graph](#2-architecture-graph)
3. [How it works](#3-how-it-works)
4. [Critical gotchas](#4-critical-gotchas)
5. [Options & locks](#5-options--locks)
6. [Working safely](#6-working-safely)

---

## 1. Overview

**lavtheme** is a **classic (PHP-template) WordPress theme** running on
**lavzen.com** (Hostinger). It was built from a single-page HTML design; the
markup/CSS/JS were transferred verbatim and split into template parts and
per-section asset files. **Easy Digital Downloads (EDD)** powers the products
(post type `download`); the Products section renders real downloads, with a
static fallback if EDD is off.

Its centerpiece is **Theme Code Studio** (WP Admin → **Code Studio**): a panel to
edit the theme's code per section — HTML/PHP, CSS, JS, Mobile CSS, and a custom
**PHP** tab — for the Front Page **and every published page**, without FTP.

---

## 2. Architecture graph

```mermaid
flowchart TD
    FP[functions.php<br/>bootstrap] --> H[inc/helpers.php<br/>lavtheme_option, kses, css/php helpers]
    FP --> SE[inc/setup.php<br/>supports, menus]
    FP --> EN[inc/enqueue.php<br/>main.css + products.css<br/>main.js NOT enqueued]
    FP --> EDD[inc/edd.php<br/>EDD products grid + bubbles]
    FP --> REG[inc/code-studio-registry.php<br/>front section registry + CRUD]
    FP --> CS[inc/code-studio.php<br/>admin menu + panel UI]
    FP --> SAVE[inc/code-studio-save.php<br/>save / backup / syntax / AJAX]
    FP --> INJ[inc/code-studio-inject.php<br/>render_section + front injection + minify]
    FP --> CTX[inc/code-studio-contexts.php<br/>deprecated; EDD/Woo detection only]
    FP --> PG[inc/code-studio-pages.php<br/>per-PAGE contexts + injection + AJAX]

    subgraph ADMIN[Admin: editing]
      CS -->|wp_localize / AJAX| AJS[assets/admin/code-studio.js + .css]
      AJS -->|save| SAVE
      AJS -->|page load/save| PG
    end

    subgraph DB[(wp_options)]
      OPT[lavtheme_cs_* options<br/>registries + section code + schema]
    end
    SAVE --> OPT
    PG --> OPT

    subgraph FRONT[Front-end: rendering]
      FRONTPAGE[front-page.php<br/>loops content sections] --> INJ
      HDR[header.php / footer.php] --> INJ
      INJ -->|include| TP[template-parts/section-*.php]
      INJ -->|defaults| ASSETS[assets/css|js/sections/*]
      PG -->|the_content filter| PAGEOUT[page output]
    end

    OPT -->|read at render| INJ
    OPT -->|read at render| PG
    TP --> PIX[Pixel-perfect front page]
```

**Flow:** admin edits in Code Studio → AJAX → saved to `wp_options` (or, in File
mode, written to theme files) → on the front end, `inc/code-studio-inject.php`
(front) and `inc/code-studio-pages.php` (pages) read those options and inject
CSS in `wp_head`, JS in `wp_footer`, and section markup into the page.

| File | Role |
|------|------|
| `functions.php` | Bootstrap: constants + `require` the `inc/` modules. |
| `inc/helpers.php` | `lavtheme_option`, kses allowlists, `lavtheme_sanitize_css`, and PHP-section helpers (`lavtheme_cs_php_allowed/check_php/run_php`). |
| `inc/setup.php` | Theme supports, nav menus, image sizes. |
| `inc/enqueue.php` | Built-in CSS: in inline mode (default) attaches `lavtheme_cs_builtin_base_css()` under a src-less `lavtheme-main` handle (composed from the split files); in rollback mode enqueues `assets/css/main.css`. `products.css` on front (depends on `lavtheme-main`). **`main.js` is delivered via JS injection, not enqueued.** |
| `inc/edd.php` | EDD products grid + category bubbles (guarded). |
| `inc/edd-shop.php` | **Shop** (download post-type archive + `download_category`/`download_tag` term archives). Filters the **real** main query via `pre_get_posts` (read-only GET params: `pq` keyword, `pcat[]` categories, `min`/`max` price, `orderby`), and builds the filter sidebar, sort control, product cards + badges. Templates: `archive-download.php`, `taxonomy-download_*.php`, shared layout in `template-parts/shop.php`. Theme-level only — **no `/edd/` template is overridden**. |
| `inc/code-studio-registry.php` | Front-page section registry (`lavtheme_cs_registry`) + add/rename/delete/reorder/placement, Hello-World starter, icon presets. |
| `inc/code-studio.php` | Admin menu, panel render, editor enqueue, helper accessors. |
| `inc/code-studio-save.php` | Save / restore / backups / file-write + syntax check; most front AJAX. |
| `inc/code-studio-inject.php` | `lavtheme_render_section()`, front CSS→`wp_head`, JS→`wp_footer`, schema, and the HTML minifier. |
| `inc/code-studio-pages.php` | Per-page contexts: page index, per-page registry, Page Content read/write, per-page injection + placement, page AJAX. |
| `inc/code-studio-downloads.php` | EDD download context: `dl-template` (the single **Single Download (template)** level, applies to all products). Editable Product schema with `{{product_*}}` tokens, dl AJAX. The `dl-<ID>` per-product layer still exists in code but is **not exposed in the dropdown** (per-product override is disabled). |
| `inc/code-studio-contexts.php` | Deprecated; now only `lavtheme_cs_edd()/woo()` detection badges. |
| `inc/code-studio-export.php` | **Export/Import (JSON).** AJAX `lavtheme_cs_export` (GET) streams a front section's **saved** content as a versioned JSON file (`lavtheme-front-page-<slug>-<date>.json`): `{lavtheme_export, format_version, theme, theme_version, exported_at, context, section:{slug,label}, tabs:{<key>:<code>}}`. **Dynamic by design** — the `tabs` map is built by iterating `lavtheme_cs_fields()` (the same source the UI builds tabs from), so any future tab is included with no code change; keys are the internal keys (`html` markup ≠ `php` custom, kept separate). **Import** is client-side in `code-studio.js`: validates `lavtheme_export`/`format_version`, maps file tabs onto the destination's real tabs (read from the panel DOM), skips unknown tabs with a warning, previews into the editors, confirms, then persists each tab via the existing `lavtheme_cs_save` endpoint — so the PHP-syntax check, `LAVTHEME_ALLOW_PHP_SECTIONS` lock, sanitisation and `_prev` backup all still apply (never bypassed). Export warns on unsaved edits. Constant `LAVTHEME_CS_EXPORT_FORMAT` = format version. |
| `front-page.php` | Loops front content sections (placement-aware, pixel-safe). |
| `header.php` / `footer.php` | Open/close the `.app`/`.main` shell; render sidebar/header/footer sections. |
| `assets/admin/code-studio.{js,css}` | Panel logic (CodeMirror, tabs, sortable, AJAX) + styling. |
| `assets/css\|js/sections/*` | Per-section split of the original `main.css`/`main.js`; editor defaults. |

---

## 3. How it works

- **Dynamic section registry.** Sections aren't hardcoded — they live in
  `wp_options` (`lavtheme_cs_registry` for the front page). Each record:
  `{slug, label, file, zone, builtin, deletable, dynamic, placement}`. Zones:
  `settings` (Global/Schema), `top` (sidebar/header), `content` (front-page loop),
  `bottom` (footer). You can add/rename/delete/reorder sections in the panel.
- **Multi-context (per page).** A context dropdown switches between the Front Page
  and **every published page** (read from the DB). Each page has its own
  namespaced registry (`lavtheme_cs_registry_page_<ID>`) with fixed Global, Schema,
  and **Page Content** (the real `post_content`, edited via `wp_update_post`) plus
  custom sections. Custom page sections render via a `the_content` filter.
- **Shop archive** (`inc/edd-shop.php`). The download archive (`/products/` or the
  download archive slug) and `download_category`/`download_tag` term archives render a
  sidebar (search + category checkboxes + price range) beside a real EDD product grid.
  Filters are plain GET params applied to the **main query** in `pre_get_posts` — they
  work with JS off (it's a `<form method=get>`); JS only adds the mobile filter drawer.
  Keyword search uses a custom `pq` param (not `s`) so WordPress keeps the archive
  template instead of switching to `search.php`. The sort `<select>` in the top bar
  references the sidebar form via the HTML5 `form` attribute, so one request carries
  every filter + the sort. Cards reuse the front-page `.lavp-card` look (products.css
  is enqueued as a dependency of `shop.css`); badges are data-driven (`-NN%` when a
  `_lavtheme_compare_price` meta exceeds the price, else Free, else New ≤14 days).
- **EDD download context** (`inc/code-studio-downloads.php`). The dropdown shows a
  single **"Single Download (template)"** option (`dl-template`, applies to **every**
  product via `is_singular('download')`). There is intentionally **no per-product
  entry** — you edit the template once and it applies to all product pages. (The
  `dl-<ID>` per-product layer is still implemented and the render path still merges
  it when a `dl-<ID>` registry happens to exist, but it is not listed in the UI, so
  per-product overrides are effectively disabled.) The template has the full editor
  (section builder with Add/delete/drag + placement, Global, Schema, PHP); its
  **Product Content** entry is a non-editable draggable anchor (each product's real
  content fills in at render). The JS sends a `context` string and dispatches
  page-vs-download AJAX. **Schema** here defaults
  to an editable **Product/Offer** JSON-LD with `{{product_name}}`,
  `{{product_price}}`, `{{product_image}}`, `{{product_url}}`, `{{product_currency}}`,
  `{{product_id}}` tokens that are replaced with the real product's data at render
  (specific schema > template schema > default). Tokens also work in download
  section CSS/JS/HTML.
- **Placement & order.** Page content-zone sections render in **registry order**,
  and **Page Content is itself a draggable item** in that list — drag a section
  above/below it to place it before/after the real content. A section's
  `placement` only changes *how* it's placed: `inline` (default, in-flow at its
  order position) / `sidebar-left` / `sidebar-right` / `replace` / `wrap`. Sidebars
  use a responsive CSS grid (`.lavcs-pagewrap`). The front page only builds the
  wrapper when a sidebar is used (otherwise output is unchanged). Only Global and
  Schema are fixed (non-draggable); Page Content and custom sections move freely.
- **Two save modes.** *Database* (default, safe): code stored in options and
  injected live — never white-screens. *File*: writes HTML/PHP to the theme file
  on disk (locked behind a constant, with auto-backup + syntax check).
- **PHP tab.** Optional pure-PHP per section, executed at render in an isolated,
  guarded way (see §5).
- **Front injection.** Built-in section CSS ships in `main.css`; edits + custom
  sections inject after it. All section JS is injected in the footer (so `main.js`
  itself is delivered there, editable as Global JS). Schema is per-context.

---

## 4. Critical gotchas

- **Dynamic sections contain raw `<?php`** (Products/EDD, Blog). Never render their
  override as static kses'd HTML — it strips the PHP and kills the section. The
  render layer ignores `<?php` overrides and falls back to the file.
- **Never minify PHP / source files.** Minify only the rendered front-end output.
- **Empty-save semantics (`_empty` marker).** Saving a CSS/JS/Mobile tab while it's
  empty stores `lavtheme_cs_<slug>_<type>_empty = '1'` so render injects **nothing**
  and does **not** fall back to the file default (an intentional clear must stick).
  A non-empty save, a "matches default" save, or **Reset to default** clears the
  marker. `lavtheme_cs_get_value()` and the JS/custom-CSS injectors all check it.
  HTML is exempt — an empty HTML save still falls back to the file (never blanks a
  section; protects dynamic Products/Blog).
- **Inline-CSS mode (built-in CSS is now editable/removable).** `main.css` is **no
  longer enqueued** by default. `enqueue.php` registers a src-less `lavtheme-main`
  handle and attaches `lavtheme_cs_builtin_base_css()` inline — the global
  `:root`/CSS/bg + every built-in section's CSS/Mobile, composed from the editable
  split files in registry order (verified rule-for-rule identical to `main.css`:
  515 = 515 rules, 0 missing/extra, 0 reordered same-selector conflicts). Built-in
  CSS overrides still layer on top via `lavtheme_cs_head_css()` (wp_head pri 100),
  and clearing a built-in CSS tab now truly removes it (the base omits an `_empty`
  tab). Toggle: `lavtheme_cs_inline_css()` (filter `lavtheme_cs_inline_css`).
  **Instant rollback:** `define( 'LAVTHEME_DISABLE_INLINE_CSS', true );` in
  wp-config.php re-enqueues `main.css` exactly as before (file is still on disk).
- **Hostinger `hcdn` cache.** After any change, purge the cache (or hard-refresh /
  cache-bust with `?cb=…`) — otherwise changes look like they didn't apply.
- **Lint with `token_get_all( $code, TOKEN_PARSE )`**, not `php -l` — `shell_exec`
  is disabled on this host. (Brace-balance is NOT a syntax check.)
- **Per-page schema/CSS/JS are namespaced** (`lavtheme_cs_page_<ID>_…`); injection
  reads the current page's keys and only falls back to the site default when a
  page key is empty. If "my edit shows the default", the page key wasn't saved —
  check the save path, not the injector. (On save, the active CodeMirror editor is
  initialised before reading, so its value can't be read as empty.)
- **Page sections render in registry order** (not placement buckets); Page Content
  is a draggable list item that anchors before/after.
- **Real testing is host-only.** No PHP/MySQL CLI locally; verify via a temporary
  token-protected endpoint, then delete it.
- **Elementor canvas pages bypass `the_content`** — custom page sections won't show
  there (CSS/JS/Schema/Page-Content still do).
- **Never override a plugin's internal templates** (no `/edd/` overrides) — restyle
  via CSS / wrap with custom sections instead.
- **EDD also emits its own Product JSON-LD** on download pages, so the theme's
  editable Product schema coexists with it (two Product blocks). Disable EDD's in
  its settings if a single schema block is required — the theme can't reliably
  switch it off in EDD 3.x.
- **FTP DEPLOY PATH (critical, confirmed Jun 2026):** the FTP account
  (`u523965318.lavzen.com` @ `82.29.185.21:21`) **chroots to the domain web root**,
  so the FTP login dir `/` **is** `/home/u523965318/domains/lavzen.com/public_html/`
  (it contains `wp-admin`, `wp-config.php`, `wp-content`). Therefore the live theme
  is **`/wp-content/themes/lavtheme/`** (relative to the FTP root). **Do NOT
  `cd public_html/...`** — there is a stray nested `public_html/` inside the root
  that is NOT served by the web server; uploading there silently changes nothing
  (the live site never updates, and probes 404). The bundled `ftp-upload.txt` /
  `sftp-*.txt` scripts had this bug (`cd public_html/wp-content/themes/lavtheme/`).
  curl form that works: `curl -T <local> -u "$USER:$PASS"
  "ftp://82.29.185.21/wp-content/themes/lavtheme/<relpath>"`. If "my change didn't
  apply", **check the path before suspecting cache** — verify with an FTP listing
  of `/wp-content/themes/lavtheme/` (full theme present) vs the empty nested dir.
- **OPcache** picks up correct-path uploads fine (validate_timestamps is effectively
  on); a parse-error file is uncached and recompiles every request, so a fix lands
  immediately. The `hcdn` CDN, however, caches **static** assets (`.css`/`.js`/`.txt`)
  and ignores `?cb=` for them — purge from hPanel or wait for TTL after changing one.
- **Keep the front page pixel-perfect** — be careful with `main.css` and markup.

---

## 5. Options & locks

**Key `wp_options`** (all prefixed `lavtheme_cs_`):

| Option | Purpose |
|--------|---------|
| `lavtheme_cs_registry` | Front-page section registry. |
| `lavtheme_cs_registry_page_<ID>` | A page's section registry. |
| `lavtheme_cs_<slug>_<type>` | Front section code (`type` = html/css/js/mcss/php; global: root/css/js/bg). |
| `lavtheme_cs_page_<ID>_<slug>_<type>` | A page section's code. |
| `lavtheme_cs_registry_dl_template` / `lavtheme_cs_registry_dl_<ID>` | Download context registries (all-products / one product). |
| `lavtheme_cs_dl_template_<slug>_<type>` / `lavtheme_cs_dl_<ID>_<slug>_<type>` | Download section code. |
| `lavtheme_cs_<key>_php_bak` / `…_prev` | Backups for restore. |
| `lavtheme_cs_schema` | Default JSON-LD schema (per-page schema overrides it). |
| `lavtheme_cs_mode` | Save mode: `db` (default) / `file`. |
| `lavtheme_cs_minify` | Front-end minify toggle. |
| `lavtheme_cs_header_global` | Render Header on all pages (default on). |
| `lavtheme_cs_trash` / `lavtheme_cs_pcbak_<ID>` | Deleted-section trash / page-content backup. |

Section records also carry a `placement` field (`inline` (default) / `sidebar-left` /
`sidebar-right` / `replace` / `wrap`).

**wp-config.php locks** (both off by default):

| Constant | Unlocks |
|----------|---------|
| `define( 'LAVTHEME_ALLOW_FILE_WRITE', true );` | File save mode (writing theme files). |
| `define( 'LAVTHEME_ALLOW_PHP_SECTIONS', true );` | Executing the PHP tab (otherwise PHP is saved but never run). |

PHP-tab guards: lock constant + `manage_options` + nonce, `token_get_all` syntax
check (blocks save on error), isolated `try/catch( \Throwable )` so one section's
fatal can't break the page, and an automatic backup.

---

## 6. Working safely

1. **Read this file + the relevant `inc/` file first.** Don't assume the section
   list — read the registry.
2. **Back up before writing.** File/PHP saves auto-backup; do the same if scripting.
3. **Really lint** every changed PHP file (`token_get_all`, TOKEN_PARSE) and, when
   possible, render the admin page once on the host.
4. **Handle dynamic sections (Products, Blog) carefully** — don't turn their PHP
   into static HTML.
5. **Account for the `hcdn` cache** after every change.
6. **Don't upload files yourself** — the user syncs via FTP (port 21). Only deploy
   for a one-time real test, with permission, and clean up any temp endpoint.
7. **Keep the front page pixel-perfect.**

---

*Update this file in the same change whenever you change behaviour.*
