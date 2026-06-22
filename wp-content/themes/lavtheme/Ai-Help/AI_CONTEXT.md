# AI_CONTEXT.md — lavtheme

A compact orientation guide. Read this first, then open the relevant `inc/` file.
Part of the **[Ai-Help](README.md)** doc set; deep Code Studio internals live in
**[AI_CONTEXT2.md](AI_CONTEXT2.md)**, the "request → file" router in
**[AI_MAP.md](AI_MAP.md)**, deployment in **[DEPLOY.md](DEPLOY.md)**.

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
| `inc/edd-shop.php` + `inc/edd-shop-ui.php` | **Shop** (download archive + `download_category`/`download_tag` archives), shop.html design. `edd-shop.php` = data/query layer: `pre_get_posts` filters the **real** main query from GET params `pq` (search), `pcat[]` (categories), `min`/`max` (price), `flt[]` (sale/new/best), `rating` (reviews-gated), `orderby` (relevance/sales/date/rating/trending/price-asc/desc), `paged`; plus cards, badges, hero stats, price bounds, reviews detection. `edd-shop-ui.php` = builders: hero, filter sidebar (search, icon categories w/ real counts, dual-range slider, rating, tags), toolbar (count + grid/list + sort), removable active chips, paginate_links, quick-view modal. Server-side (no AJAX) — SEO/no-JS safe; `assets/js/shop.js` adds slider UI, grid/list (localStorage), localStorage wishlist, quick view, mobile drawer. All scoped under `.lav-shop`. Templates: `archive-download.php`, `taxonomy-download_*.php`, layout `template-parts/shop.php`. **No `/edd/` override.** |
| `inc/blog.php` + `inc/blog-ui.php` | **Blog** (blog.html design wired to real posts). `blog.php` = engine: `pre_get_posts` filters the real main query on `lavtheme_is_blog()` (= `is_home()&&!is_front_page()` / category / tag / author / date / search — **excludes the front page**, which is `front-page.php` with `show_on_front=posts`) from GET params `bcat`/`bsort`(latest/oldest/popular/readtime/az)/`bdate`/`bauthor`/`bq`/`bpg`; featured = first sticky else latest (excluded from the grid); read time = words/200; `popular` = view-meta (auto-detected) else comment_count; `readtime` sort via a `posts_clauses` CHAR_LENGTH filter. `blog-ui.php` = builders (head+stats, featured, filter bar w/ category pills+dropdown, post cards, sidebar [popular posts, real tags, newsletter, about], pagination). **The site has no posts page**, so the blog index renders on a **dedicated seeded "Blog" page** (`lavtheme_blog_page_id()` → `page_for_posts` else `lavtheme_blog_page_id` option; `lavtheme_blog_seed_page()` admin_init run-once creates it) via `template_include` + a secondary query (`lavtheme_blog_render_page`, like the shop page); plus `home.php`/`archive.php`/`search.php`. Scoped under `.lav-blog` (card class `.bpost` to avoid the front-page `.post`). Blog URL via `lavtheme_blog_url()`. |
| `inc/code-studio-blog.php` | **Blog (archive) Code Studio context** — same pattern as Shop: reuses the dl plumbing (`ctxIsDl()` matches `'blog'`; `'blog'` branches in `code-studio-downloads.php`). Editors: Global **CSS** ← `assets/css/blog.css`, Global **JS** ← `assets/js/blog.js`, **Template** ← `template-parts/blog.php` (override-or-file + `_empty`). Injects blog CSS/JS (single source; enqueue removed). Dropdown: "Blog → Blog (archive)". |
| `inc/code-studio-shop.php` | **Shop (archive) Code Studio context.** Reuses the downloads (dl) AJAX plumbing — the panel's `ctxIsDl()` now also matches `'shop'`, so it dispatches to `lavtheme_cs_dl_*` with `context:'shop'`; `code-studio-downloads.php` has `'shop'` branches in `lavtheme_cs_dl_valid/builtin/default_path`. Editors (never empty, override-or-file + `_empty`): Global **CSS** ← `assets/css/shop.css`, Global **JS** ← `assets/js/shop.js`, **Template (PHP/HTML)** ← `template-parts/shop.php`. This file injects the shop CSS (`wp_head` 7) + JS (`wp_footer` 101) on `lavtheme_is_shop()` (single source; shop enqueue removed) and runs the Template override (PHP-unlock gated) else the file via `lavtheme_cs_shop_render()` in `archive-download.php` / `taxonomy-download_*.php`. Dropdown: "Shop (EDD) → Shop (archive)". |
| `inc/menus.php` | Real WP menus for the shell. Locations (in `setup.php`): `primary` (.topnav), `mobile`, `social_sidebar`, `account`, `shop_categories`. `lavtheme_topnav()` → `wp_nav_menu(primary)` via a bare-`<a>` walker, fallback **identical to the front-page anchor nav** (front page untouched), real links on inner pages. `lavtheme_account_popover()` → login-aware EDD account links (orders/downloads/checkout/profile/logout, or login/register). `lavtheme_shop_categories_nav()` → live `download_category`. Run-once `lavtheme_seed_menus()` (admin_init, guarded by `lavtheme_menus_seeded`) creates starter Main/Account menus — additive only. Wired in `header-topbar.php`. |
| `inc/code-studio-registry.php` | Front-page section registry (`lavtheme_cs_registry`) + add/rename/delete/reorder/placement, Hello-World starter, icon presets. |
| `inc/code-studio.php` | Admin menu, panel render, editor enqueue, helper accessors. |
| `inc/code-studio-save.php` | Save / restore / backups / file-write + syntax check; most front AJAX. |
| `inc/code-studio-inject.php` | `lavtheme_render_section()`, front CSS→`wp_head`, JS→`wp_footer`, schema, and the HTML minifier. |
| `inc/code-studio-pages.php` | Per-page contexts: page index, per-page registry, Page Content read/write, per-page injection + placement, page AJAX. |
| `inc/code-studio-downloads.php` | EDD download context: `dl-template` (the single **Single Download (template)** level, applies to all products). Editable Product schema with `{{product_*}}` tokens, dl AJAX. **The dl-template editors now load the REAL files as defaults** (override-or-file, like the front page): Global **CSS** ← `assets/css/single-product.css`, Global **JS** ← `assets/js/single-product.js`, and a **Template (PHP/HTML)** section ← `template-parts/single-download-body.php`. `lavtheme_cs_dl_get()` falls back to those files (honouring the `_empty` clear marker, now written by the dl save for css/js/bg/php). CSS/JS are injected by `lavtheme_cs_dl_head/_footer` (NOT enqueued — single source); `single-download.php` is a thin loader that runs the Template override via `lavtheme_cs_run_php` (template-style syntax-checked with `lavtheme_cs_dl_check_template`) **only when `LAVTHEME_ALLOW_PHP_SECTIONS` is on**, else includes the body file. The `dl-<ID>` per-product layer still exists but is **not exposed in the dropdown**. |
| `inc/code-studio-contexts.php` | Deprecated; now only `lavtheme_cs_edd()/woo()` detection badges. |
| `inc/code-studio-export.php` | **Export/Import (JSON).** AJAX `lavtheme_cs_export` (GET) streams a front section's **saved** content as a versioned JSON file (`lavtheme-front-page-<slug>-<date>.json`): `{lavtheme_export, format_version, theme, theme_version, exported_at, context, section:{slug,label}, tabs:{<key>:<code>}}`. **Dynamic by design** — the `tabs` map is built by iterating `lavtheme_cs_fields()` (the same source the UI builds tabs from), so any future tab is included with no code change; keys are the internal keys (`html` markup ≠ `php` custom, kept separate). **Import** is client-side in `code-studio.js`: validates `lavtheme_export`/`format_version`, maps file tabs onto the destination's real tabs (read from the panel DOM), skips unknown tabs with a warning, previews into the editors, confirms, then persists each tab via the existing `lavtheme_cs_save` endpoint — so the PHP-syntax check, `LAVTHEME_ALLOW_PHP_SECTIONS` lock, sanitisation and `_prev` backup all still apply (never bypassed). Export warns on unsaved edits. Constant `LAVTHEME_CS_EXPORT_FORMAT` = format version. |
| `inc/backlink-checker.php` | **Standalone admin tool** (Tools → *Backlink Spam Checker*) — fully self-contained, **no front-end hooks**, unique `lavtheme_blc_` prefix, CSS/JS inlined in the page (no cacheable static asset). Live line-by-line results via **SSE** (`admin-ajax` `text/event-stream`, buffering/gzip disabled + `X-Accel-Buffering:no` + 2KB padding) with an **auto AJAX-polling fallback** when the host (hcdn/LiteSpeed) buffers the stream — the client watches for events ≤6s and switches mid-run. 3 endpoints: `lavtheme_blc_start` (POST: sanitise+dedupe list → transient job token), `lavtheme_blc_stream` (GET EventSource: one `result` event/domain + `done` summary), `lavtheme_blc_batch` (POST: one slice → JSON). Checker = TLD/keyword/hyphen/digit/long-label/anchor heuristics + `wp_remote_head`→`get` reachability (5s timeout, `set_time_limit` reset per item so the job never locks). Labels Clean/Suspicious/Spam; client-side Google **disavow** export (`domain:host`). |
| `inc/code-studio-single.php` | **Single Post Code Studio context** — injects single.css/single.js + the `single-article.php` template (override-or-file), like Shop. |
| `inc/code-studio-404.php` | **404 Code Studio context** — the error page as an editable dl-style context. |
| `inc/code-studio-account.php` | **My Account context** — seeds the "My Account" page, routes it via `template_include`, renders `template-parts/account.php` (override-or-file), injects account.css/js. The dashboard (Dashboard/Orders/Downloads/Profile) is wired to EDD's own shortcodes for reliable data. Popover links in `inc/menus.php` point here. |
| `assets/css/checkout.css` | **EDD purchase-flow styling** (checkout/cart/receipt/purchase-history/confirmation/failed). Enqueued only on EDD pages via `lavtheme_is_edd_flow()` in `inc/enqueue.php`. |
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
- **Lint every PHP change.** `php -l` now works **locally** (PHP 8.3 CLI is
  installed). On the **host** `shell_exec` is disabled, so Code Studio lints with
  `token_get_all( $code, TOKEN_PARSE )`. (Brace-balance is NOT a syntax check.)
- **Per-page schema/CSS/JS are namespaced** (`lavtheme_cs_page_<ID>_…`); injection
  reads the current page's keys and only falls back to the site default when a
  page key is empty. If "my edit shows the default", the page key wasn't saved —
  check the save path, not the injector. (On save, the active CodeMirror editor is
  initialised before reading, so its value can't be read as empty.)
- **Page sections render in registry order** (not placement buckets); Page Content
  is a draggable list item that anchors before/after.
- **Full runtime testing is host-only.** `php -l` lints locally, but there is no
  local DB/WP runtime; verify behaviour via a temporary token-protected endpoint on
  the host, then delete it.
- **Design tokens / CTA colour.** Brand yellow `--cta:#f5c518` (+ `--cta-ink`) is
  the **primary-CTA colour only** (buy/checkout/subscribe); indigo `--accent`
  `#7c83ff` is secondary/selected; `--gold` `#e8c547` is stars/PRO; `--accent-3`
  `#8b5cf6` is the violet gradient partner; `--danger`/`--success`/`--menu-bg` are
  semantic. Don't hardcode hex in components — use the tokens defined in
  `assets/css/sections/global.root.css`.
- **Elementor canvas pages bypass `the_content`** — custom page sections won't show
  there (CSS/JS/Schema/Page-Content still do).
- **Never override a plugin's internal templates** (no `/edd/` overrides) — restyle
  via CSS / wrap with custom sections instead.
- **Shop URL/page is dynamic — never hardcode `/downloads/` or `/products/`.**
  EDD 3.x **does** have a "Shop Page" setting (UI label) stored in `edd_settings`
  under the key **`products_page`** (read via `edd_get_option('products_page')` —
  on this site it's page id **41** = `/products/`, which holds the EDD Downloads
  block). The theme reads it through **`lavtheme_shop_page_id()`** (→ `products_page`,
  then `shop_page_id` option, then `lavtheme_shop_page_id` filter). The shop now
  renders on **two** surfaces, both followed dynamically:
  1. The configured **Shop Page** — `lavtheme_cs_shop_page_template()` (`template_include`)
     routes ONLY that page to `template-parts/shop-page-template.php`, which swaps in
     a secondary downloads query (`lavtheme_shop_page_query()` + `lavtheme_shop_filter_vars()`,
     mirrors `pre_get_posts`) and renders the shared layout — **replacing the page's
     [downloads] block (no double render)**. Pagination uses a canonical-safe `pg` arg.
  2. The auto **`download` archive** (`archive-download.php`, any slug) still works.
  All shop links use `lavtheme_shop_url()` (configured page → archive link; filterable).
  `lavtheme_is_shop()` = `is_post_type_archive('download') || is_tax(...)` **or** the
  configured page (global context only; query context stays archive-only so
  `pre_get_posts` never touches the page query). Change the Shop Page in EDD settings
  → everything follows with **zero code change**. (EDD's other pages —
  `purchase_page`/`purchase_history_page`/`success_page`/`failure_page`/`confirmation_page`
  — also live in `edd_settings`.)
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
