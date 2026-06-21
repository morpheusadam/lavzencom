# AI_CONTEXT.md — lavtheme

> **Read this entire file before touching anything.** It is the single source of
> truth for what this theme is, how it is built, and the rules you must follow so
> you don't break a live production site. Everything here was extracted from the
> actual code in this folder. Where something is uncertain it is marked
> **[VERIFY]**.

---

## Table of contents

1. [Overview](#1-overview)
2. [Theme Code Studio — the heart of the project](#2-theme-code-studio--the-heart-of-the-project)
3. [Architecture (files & roles)](#3-architecture-files--roles)
4. [Section registry](#4-section-registry)
5. [How sections store their code](#5-how-sections-store-their-code)
6. [Critical gotchas (read twice)](#6-critical-gotchas-read-twice)
7. [Deployment](#7-deployment)
8. [Options reference (wp_options)](#8-options-reference-wp_options)
9. [How to work with this safely](#9-how-to-work-with-this-safely)
10. [Page contexts (EDD/Woo multi-context editing)](#10-page-contexts-eddwoo-multi-context-editing)
11. [Placement system & custom PHP tab](#11-placement-system--custom-php-tab)

---

## 1. Overview

- **lavtheme** is a **classic (PHP-template) WordPress theme** — *not* a block/FSE theme.
- It was built from a single-page HTML design (`home.html` / earlier `clean-code (4).html`).
  The original markup, CSS and JS were transferred **verbatim** and split into
  template parts and per-section asset files (see §5).
- **Live site:** `https://lavzen.com` (Hostinger shared hosting). The local folder
  `C:\Users\Morpheus\Documents\lavzencom\public_html` is a working copy; the live
  DB lives on the host.
- **Easy Digital Downloads (EDD)** powers the digital products. The Products
  section renders real EDD downloads (post type `download`, taxonomy
  `download_category`). All EDD calls are guarded with `function_exists` /
  `post_type_exists`, so the theme never fatals if EDD is disabled (it falls back
  to static demo bubbles).
- **Design language:** glassmorphism, CSS custom properties in `:root`
  (`--accent: #7c83ff`, `--bg: #0b1120`, …), fonts Oswald + Inter.
- The front page must stay **pixel-identical** to the original design. Treat any
  change to `main.css` / markup structure as high risk.

---

## 2. Theme Code Studio — the heart of the project

A custom admin panel at **WP Admin → Code Studio** (top-level menu, slug
`lavtheme-code-studio`, capability `manage_options`). It lets an admin edit the
theme's code per section, live, without opening files over FTP.

**Per section, four editors (CodeMirror):**
- **HTML / PHP** — the section's markup/template.
- **CSS** — the section's styles.
- **JS** — the section's script.
- **Mobile CSS** — wrapped automatically in `@media (max-width:640px)`.

**Special left-nav items (not normal sections):**
- **Global** — `:root` variables, global CSS, global JS, background CSS.
- **Schema** — a JSON-LD editor (Schema.org structured data) injected into
  `<head>` of every page. JSON is validated before saving.

**Editor:** enhanced **CodeMirror** (the copy bundled with WordPress via
`wp_enqueue_code_editor`) — dark VS Code-style skin, code folding, bracket
matching, auto-close brackets, active-line, `Ctrl-Space` autocomplete, 2-space
tabs. (Monaco was deliberately **not** used: it needs a build/CDN and a bigger
rewrite; there is therefore **no minimap**.)

**Two save modes** (switch in the panel top bar; stored in `lavtheme_cs_mode`):
1. **Database (default, safe)** — code is stored in `wp_options` and injected at
   render time. The site can never white-screen and nothing is lost on update.
2. **File (powerful, opt-in)** — writes the section's HTML/PHP straight to the
   theme file on disk. **Locked** unless `wp-config.php` defines
   `define( 'LAVTHEME_ALLOW_FILE_WRITE', true );`. Every file write first makes a
   **backup** under `wp-content/themes/lavtheme/.backups/<timestamp>/` and runs a
   **PHP syntax check** (`token_get_all( …, TOKEN_PARSE )`, with `php -l` only if
   shell access exists — it doesn't on this host, see §6). A **Restore** button
   lists backups. CSS/JS always come from the DB/section files in both modes; only
   HTML/PHP is file-written.

**Section manager** (top of the left column): Add Section, drag-and-drop reorder
(jQuery UI Sortable), Rename, Delete (with confirm; a second confirm for dynamic
sections), and a restorable **Trash**.

**Top-bar toggles:** *Minify front-end* and *Header on all pages* (see §8).

---

## 3. Architecture (files & roles)

All bootstrapping is in `functions.php`, which `require`s the `inc/` modules in
this order: `helpers → setup → enqueue → edd → code-studio-registry →
code-studio → code-studio-save → code-studio-inject`.

| File | Role |
|------|------|
| `functions.php` | Bootstrap only. Defines constants (`LAVTHEME_DIR/URI/VERSION`) and requires the `inc/` files. |
| `inc/helpers.php` | `lavtheme_option()`, `lavtheme_part()`, SVG kses allowlist (`lavtheme_svg_allowed_html`), `lavtheme_kses_extended()` (markup + inline SVG + `data-*`), `lavtheme_sanitize_css()`. |
| `inc/setup.php` | `after_setup_theme`: theme supports, nav menus, image size `lavtheme-card` (640×400). |
| `inc/enqueue.php` | Enqueues `assets/css/main.css` (the full monolithic stylesheet) + `assets/css/products.css` (front page only). **`main.js` is intentionally NOT enqueued** — it is delivered via the Code Studio JS injection (see §6). `lavtheme_asset_ver()` = `filemtime` cache-busting. |
| `inc/edd.php` | EDD integration: products grid (`lavtheme_products_grid_html`), category bubbles (`lavtheme_category_bubbles_html`), all guarded; admin notice if EDD inactive. |
| `inc/code-studio-registry.php` | **Dynamic section registry** (§4): seed/get/save, `lavtheme_cs_sections()` (registry-backed, replaces the old hardcoded map), `lavtheme_cs_content_slugs()`, Hello-World starter generator, file materialiser, and the add/rename/reorder/delete/trash/restore AJAX handlers. |
| `inc/code-studio.php` | Panel UI: menu, `admin_enqueue_scripts` (CodeMirror settings + `wp_localize_script` data), `lavtheme_cs_render_page()`, plus helpers `lavtheme_cs_key/mode/file_allowed/minify_on/header_global/get_schema/schema_default/default_path/default_value/get_value/fields/mode_for`. |
| `inc/code-studio-save.php` | AJAX save/restore/backups/setmode/toggle. Sanitisers (`lavtheme_cs_sanitize`, `lavtheme_cs_sanitize_code`), backup (`lavtheme_cs_backup_file`), file write + syntax check (`lavtheme_cs_write_file`, `lavtheme_cs_php_syntax_ok`). Also the **Schema save** (JSON-validated) is special-cased here. |
| `inc/code-studio-inject.php` | Front-end output: `lavtheme_render_section()`, section CSS → `wp_head` (pri 100), section JS → `wp_footer` (pri 100), JSON-LD schema → `wp_head` (pri 5), and the **minifier** (`template_redirect` pri 0). |
| `inc/code-studio-contexts.php` | **Deprecated / superseded** by code-studio-pages.php. Still defines `lavtheme_cs_edd()`/`lavtheme_cs_woo()` (used for the "detected" badges) and harmless empty front-end hooks. Old EDD-type context layer (`lavtheme_cs_ctx_*`) is no longer in the UI. |
| `inc/code-studio-pages.php` | **Per-PAGE contexts** (§10): real published-page index, per-page namespaced section registry, Page Content read/write, per-page injection (CSS/JS/Schema + the_content section stack), and all per-page AJAX. Fully separate from the Front Page system. |
| `front-page.php` | Loops `lavtheme_cs_content_slugs()` (registry order, zone `content`) and calls `lavtheme_render_section()` for each — **no hardcoded section list**. |
| `header.php` | Opens `.app` → sidebar → `.main`; renders the Header section conditionally (front page always, inner pages only if "Header on all pages" is on). |
| `footer.php` | Renders the Footer section, closes `.main`/`.app`, `wp_footer()`. |
| `assets/admin/code-studio.{css,js}` | The panel's styling (dark editor skin, sortable nav, modal) and logic (CodeMirror init, tabs, save/restore AJAX, sortable, toggles, JSON validation). |

Other standard templates exist (`index/page/single/archive/404/searchform.php`)
and a generic `template-parts/content.php` post card.

---

## 4. Section registry

Sections are **no longer hardcoded**. They live in the `lavtheme_cs_registry`
option as an **ordered list of records**:

```php
array(
  'slug'      => 'hero',                          // unique key
  'label'     => 'Hero',                          // display name (rename changes this)
  'file'      => 'template-parts/section-hero.php', // '' for global / DB-backed customs
  'html'      => true,                            // has editable markup?
  'zone'      => 'content',                       // settings | top | content | bottom
  'builtin'   => true,                            // shipped vs user-created
  'deletable' => true,                            // global is false
  'dynamic'   => false,                           // products/blog are true (EDD/loop)
)
```

**Zones:**
- `settings` — Global only (not rendered as a section; it's the settings tab).
- `top` — rendered by `header.php` (sidebar, header). Fixed position.
- `content` — rendered by `front-page.php` in registry order. **This is what
  drag-reorder actually moves on the front end.**
- `bottom` — rendered by `footer.php` (footer). Fixed position.

**Built-in sections (initial seed):**

| slug | label | zone | dynamic | file |
|------|-------|------|---------|------|
| `global` | Global | settings | – | (none) |
| `sidebar` | Sidebar (icon rail) | top | no | `template-parts/sidebar-rail.php` |
| `header` | Header / Topbar | top | no | `template-parts/header-topbar.php` |
| `hero` | Hero | content | no | `template-parts/section-hero.php` |
| `services` | Services | content | no | `template-parts/section-services.php` |
| `products` | Products (EDD) | content | **yes** | `template-parts/section-products.php` |
| `work` | Case Studies | content | no | `template-parts/section-cases.php` |
| `blog` | Blog | content | **yes** | `template-parts/section-blog.php` |
| `cta` | Call To Action | content | no | `template-parts/section-cta.php` |
| `footer` | Footer | bottom | no | `template-parts/footer-content.php` |

> Note the built-in file names don't all follow `section-<slug>.php`
> (e.g. `work` → `section-cases.php`, `sidebar` → `sidebar-rail.php`,
> `footer` → `footer-content.php`). New **custom** sections do follow the
> convention `template-parts/section-<slug>.php`.

**Deleting a built-in** only removes its registry record + DB overrides; its
files are **kept** (restore re-adds the record). Deleting a **custom** section
backs up and removes its files. `global` can never be deleted. Deleted sections
go to the `lavtheme_cs_trash` option and are restorable.

---

## 5. How sections store their code

Each section maps to four assets:

```
template-parts/section-<slug>.php       (HTML/PHP)
assets/css/sections/<slug>.css          (CSS)
assets/css/sections/<slug>.mobile.css   (Mobile CSS, injected inside @media 640)
assets/js/sections/<slug>.js            (JS)
```

Global is special: `assets/css/sections/global.root.css` (`:root` vars),
`global.bg.css` (body/background), `global.css` (shared CSS), and
`assets/js/sections/global.js` (global JS).

**Important history:** the design originally shipped as **one** `main.css` and
**one** `main.js`. Those were **split per section** into the files above (a Node
parser classified each CSS rule / JS block by selector/feature). The editors read
their **default** content from these split files via
`lavtheme_cs_default_value()`. A saved value (override) in `wp_options` takes
precedence over the file default.

**Delivery nuance (do not get this wrong):**
- `main.css` is still **enqueued** and delivers all *built-in* section CSS. The
  per-section `.css` files exist mainly to populate the editors; when you edit a
  built-in section's CSS and save, the override is **injected after main.css** and
  wins by cascade.
- **Custom** (non-builtin) section CSS is **not** in `main.css`, so it is injected
  from its file/override in `wp_head`.
- All section **JS** (built-in and custom) is delivered by the JS injection in
  `wp_footer` (because `main.js` is not enqueued). The Global JS default is
  `assets/js/sections/global.js`. `assets/js/main.js` still exists on disk but is
  **orphaned / unused**. **[VERIFY]** before deleting it.

---

## 6. Critical gotchas (read twice)

These are lessons from real bugs that already happened on this project. Repeating
them will break the live site.

1. **Dynamic sections contain raw `<?php` — never render their override as static
   HTML.** Products (EDD) and Blog templates contain PHP. If a DB HTML override is
   rendered through `wp_kses`, the PHP is stripped and the EDD grid / blog
   disappears. `lavtheme_render_section()` therefore **ignores any DB html
   override containing `<?php`/`<?=` and falls back to the file**. Also, on save,
   if the submitted HTML equals the file default it is **deleted** (not stored) so
   an accidental "Save" can't wipe a dynamic section. Keep both safeguards.

2. **Never minify PHP / source files.** Minification applies **only to the
   rendered front-end output** (HTML/CSS, and a conservative JS pass). Source
   files and the editors always show the full, readable code.

3. **Minify JS conservatively.** The minifier keeps newlines (ASI-safe) and only
   strips indentation/blank lines for `<script>`; it protects
   `<pre>/<textarea>/<script>/<style>` and collapses only newline+indent
   whitespace (inline text spacing is preserved). Don't switch to aggressive JS
   minification — it can break the blog carousel, popovers, EDD grid.

4. **Hostinger hcdn cache.** After any change the front end may look unchanged
   because `hcdn` serves cached HTML. **Purge the cache** (hPanel / LiteSpeed) or
   hard-refresh. When testing with curl, bust with a unique query string
   (`?cb=<timestamp>`); a `DYNAMIC` `x-hcdn-cache-status` means you bypassed it.

5. **`shell_exec` is disabled on this host** → `php -l` is unavailable. Lint with
   `token_get_all( $code, TOKEN_PARSE )` (throws `ParseError` on bad syntax). This
   is exactly what the file-write syntax check uses.

6. **Brace-balance is NOT a syntax check.** A real fatal (`count(null)` on an
   undefined `$trash`) once passed brace-balance because it was an undefined
   variable, not a brace problem. Always do a **real lint** (token_get_all) and,
   ideally, actually render the admin page once. Note: naive brace counters also
   give **false positives** from braces inside regex strings like `[{}:;,>]`.

7. **Section JS execution model.** The footer injection emits **one outer IIFE**;
   the **Global** JS runs first in that outer scope (so `go()` and shared vars are
   defined), and **each section's JS is wrapped in its own inner IIFE**. The inner
   IIFEs still close over `go()` but isolate their own locals — this prevents
   variable collisions when several sections (e.g. multiple "Hello World"
   sections) declare the same variable. Preserve this structure.

8. **No local PHP.** There is no PHP/MySQL CLI on the dev machine. Real testing is
   only possible **on the host** via a temporary, token-protected endpoint that
   bootstraps `wp-load.php`, lints with `token_get_all`, and/or renders
   `lavtheme_cs_render_page()` as an admin user — then **delete the endpoint**.
   (Reminder: `set_current_screen()` is not available from a front-side wp-load
   bootstrap.)

---

## 7. Deployment

- The user syncs files to the host **themselves** using the VS Code "SFTP"
  extension, which is configured for **plain FTP (port 21)** — `.vscode/sftp.json`
  has `"protocol": "ftp"`, `"openSsh": false`. It is **not** real SFTP/SSH (no
  port-22 credentials exist).
- `"uploadOnSave"` is **false**, so files are **not** auto-uploaded — the user
  uploads manually. If you create/edit files, they won't be live until synced.
- **Do not FTP-upload yourself** unless the user explicitly asks (e.g. for a
  one-time real test). The FTP root maps to the WordPress root; the theme lives at
  `/wp-content/themes/lavtheme/`.

---

## 8. Options reference (wp_options)

| Option | Type | Purpose |
|--------|------|---------|
| `lavtheme_cs_registry` | array | Ordered list of section records (§4). Seeds from built-ins on first read. |
| `lavtheme_cs_trash` | array | Deleted sections (record + content snapshot) for restore. |
| `lavtheme_cs_mode` | string | Save mode: `db` (default) or `file`. |
| `lavtheme_cs_minify` | `'1'`/`'0'` | Minify front-end output toggle (default off). |
| `lavtheme_cs_header_global` | `'1'`/`'0'` | Render Header on all pages (default **on**); off = front page only. |
| `lavtheme_cs_schema` | string | JSON-LD schema injected into `<head>`. Falls back to a generated WebSite+Organization default when empty. |
| `lavtheme_cs_ctx_<context>` | array | **Deprecated** EDD-type context layer (superseded by per-page, §10). |
| `lavtheme_cs_registry_page_<ID>` | array | Per-page section registry (§10). Seeds global/schema/content. |
| `lavtheme_cs_page_<ID>_<slug>_<type>` | string | Per-page section field (css/js/bg/json/html). |
| `lavtheme_cs_pcbak_<ID>` | string | Backup of a page's previous `post_content` (for Restore). |
| `lavtheme_cs_<slug>_php` / `lavtheme_cs_page_<ID>_<slug>_php` | string | A section's custom PHP (front / page). `…_php_bak` holds the previous version. Runs only if `LAVTHEME_ALLOW_PHP_SECTIONS` is defined. |

Registry records also carry a `placement` field (`before/after/sidebar-left/sidebar-right/replace/wrap`, default `after`).

Constant: `LAVTHEME_ALLOW_PHP_SECTIONS` (wp-config) unlocks PHP-tab execution; `LAVTHEME_ALLOW_FILE_WRITE` unlocks File mode.
| `lavtheme_cs_<section>_<type>` | string | **Per-section code override.** `type` ∈ `html`/`css`/`js`/`mcss` (and for `global`: `root`/`css`/`js`/`bg`). Built by `lavtheme_cs_key()`. |
| `lavtheme_cs_<section>_<type>_prev` | string | One-step previous value (DB-mode undo / Restore). |

> ⚠️ The option prefix is **`lavtheme_cs_`** (e.g. `lavtheme_cs_hero_css`). It is
> **not** `lavtheme_code_…`. Anything referring to `lavtheme_code_<section>_<type>`
> is outdated — use `lavtheme_cs_<section>_<type>`.

AJAX actions (all require `manage_options` + nonce `lavtheme_cs`):
`lavtheme_cs_save`, `_setmode`, `_toggle`, `_backups`, `_restore`, `_add`,
`_rename`, `_reorder`, `_delete`, `_trash`, `_restore_section`.

---

## 9. How to work with this safely

A short checklist for the next AI/developer:

1. **Read this file and the relevant `inc/` file first.** Don't assume the section
   list — read `lavtheme_cs_registry()`.
2. **Back up before file writes.** File mode already auto-backs up to `.backups/`;
   if you script changes, do the same.
3. **Really lint** every changed PHP file with `token_get_all(TOKEN_PARSE)` (not
   just brace-balance), and prefer rendering the admin page once on the host.
4. **Handle dynamic sections (Products, Blog) with care** — never turn their
   `<?php` markup into a static kses'd override.
5. **Account for the hcdn cache** after every change (purge / cache-bust).
6. **Don't upload files** — the user syncs via FTP. Only deploy for a one-time
   real test, with the user's permission, and clean up the temporary endpoint.
7. **Keep the front page pixel-perfect** — be very careful with `main.css` and
   markup structure.
8. **Preserve the safeguards** in §6 (override PHP guard, delete-on-default, single
   outer IIFE + per-section inner IIFEs, conservative minify).

---

---

## 10. Page contexts (EDD/Woo multi-context editing)

Code Studio edits the Front Page **and every published WordPress page**. The
**context dropdown** at the top switches what you're editing:

- **Front Page** — the existing template-part section system (registry,
  drag-reorder, file/DB modes). **Unchanged.**
- **Every published page** — read live from the DB via `lavtheme_cs_pages()`
  (`get_posts` post_type=page, status=publish; the static front page is excluded
  to avoid duplication). EDD/Woo pages get a label (e.g. "— EDD Checkout") via
  `lavtheme_cs_edd_page_label()` matching `edd_get_option('purchase_page'/…)`.
  Context value = `page-<ID>`.

### Per-page model (`inc/code-studio-pages.php`)

Each page has its **own namespaced registry** `lavtheme_cs_registry_page_<ID>`,
seeded with three **non-deletable** sections plus any custom ones:

| Section | Zone | What it is |
|---------|------|-----------|
| `global` | settings | per-page CSS / JS / Background |
| `schema` | settings | per-page JSON-LD (falls back to the site default) |
| `content` | content | the **real `post_content`** (read live; saved with `wp_update_post`) |
| custom… | content | DB-backed Hello-World blocks (add / rename / delete / reorder) |

- **Option keys:** `lavtheme_cs_page_<ID>_<slug>_<type>`. Page-content backup:
  `lavtheme_cs_pcbak_<ID>`.
- **Page Content** edits the actual stored shortcodes/blocks (e.g.
  `[download_checkout]`) — **not** the plugin's rendered HTML. Saving backs up the
  previous `post_content` first; a warning shows if it contains plugin shortcodes
  (`has_shortcode`); Restore reverts.
- **Rendering (only on that page):** Global CSS/Background + Schema → `wp_head`
  (guard `is_page() && get_queried_object_id()===ID`); Global JS → `wp_footer`;
  custom sections are interleaved around the real content via a **`the_content`
  filter** (the `content` section = the incoming `$content`). If a page has no
  custom sections, `the_content` is returned untouched (zero risk).
- **Schema is now per-context:** the site-wide schema (`lavtheme_cs_schema_head`)
  skips `is_page()`; pages output their own page-schema (or the default).
- **AJAX:** `lavtheme_cs_page_load / _save / _pcrestore / _addsection / _rename /
  _reorder / _delsection`. The admin UI for a page is **built dynamically in JS**
  from the `_load` payload (reusing the editor/tab/fullscreen machinery via
  delegated handlers; editor data is injected into `LavthemeCS.content['p<ID>-<slug>']`).

**Why only this (no plugin template override):** EDD/Woo pages are rendered by
the plugin. We never touch `/edd/` templates. Per-page CSS/JS/Schema/Page-Content
are fully safe and update-proof.

**[IMPORTANT LIMITATION]** Custom visual sections render via `the_content`, which
**Elementor canvas pages bypass** — so custom sections won't show on
Elementor-built pages (this site has Elementor active). Per-page CSS/JS/Schema and
Page-Content still work everywhere.

**[VERIFY]** On the live host: the dropdown lists all real published pages; the
EDD Checkout page's real `post_content` loads; a test section added to one page
appears only on that page; Front Page + EDD grid still work.

> `inc/code-studio-contexts.php` (the earlier EDD-type `lavtheme_cs_ctx_*` layer)
> is **superseded** by this per-page system and no longer in the UI. Its data is
> left in place (unused); its front-end hooks are inert when those options are
> empty.

> **Per-page injection covers ALL sections** (not just `global`): `lavtheme_cs_page_head`
> loops every section's CSS, `lavtheme_cs_page_footer` every section's JS. (A
> prior bug injected only the global section, so custom sections rendered unstyled.)

---

## 11. Placement system & custom PHP tab

### Placement (per content section, front + pages)

Every content section has a **Placement** setting (registry record field
`placement`, default `after`): `before` / `after` / `sidebar-left` /
`sidebar-right` / `replace` (warned) / `wrap` (content goes where
`[lavtheme_content]` is, else inside the section).

- **Pages:** assembled in `lavtheme_cs_page_the_content()` — `before`+content+`after`
  in the main column; `sidebar-*` sections become grid columns. The grid is
  `.lavcs-pagewrap` (`.lavcs-col-main` + `.lavcs-side-left/right`), responsive
  (single column under 782px, sidebars below content). Base CSS =
  `lavtheme_cs_page_layout_css()`.
- **Front page:** `front-page.php` only builds the grid wrapper **if** a section
  uses a sidebar placement; otherwise it renders sections in order, byte-identical
  to before (zero pixel risk). Layout CSS is echoed inline when the wrapper is used.
- Sidebar width / gap: CSS vars `--lavcs-side-w` (300px) / `--lavcs-gap` (28px) —
  override in a section's CSS.
- AJAX: `lavtheme_cs_setplacement` (front), `lavtheme_cs_page_setplacement` (pages).
- **Elementor caveat applies** (the_content), same as §10.

### Custom PHP tab (every section, front + pages)

Each section has a **PHP** tab. The PHP runs at that section's render position
and its `echo` output is inserted there.

- **It is PURE PHP** (use `echo` to output HTML; you may also embed `<?php … ?>`
  islands — `lavtheme_cs_run_php()` detects that and runs in template mode).
- **Guards (all enforced):**
  1. **Lock:** runs only if `define( 'LAVTHEME_ALLOW_PHP_SECTIONS', true )` is in
     wp-config.php. Otherwise it is **saved but never executed** (UI says so).
  2. **manage_options** + nonce on save.
  3. **Syntax check** before saving via `token_get_all( "<?php\n".$code, TOKEN_PARSE )`
     (shell-free); a parse error blocks the save and shows the line.
  4. **Isolation:** executed in a closure with `try { } catch ( \Throwable )`, so a
     PHP `Error` (undefined function, type error, …) in one section is swallowed
     and **does not break the page** (output buffered per section).
  5. **Backup:** previous code saved to `…_php_bak` before each save.
  6. Red warning banner above the editor.
- Helpers live in `inc/helpers.php`: `lavtheme_cs_php_allowed()`,
  `lavtheme_cs_check_php()`, `lavtheme_cs_run_php()`. Front execution is in
  `lavtheme_render_section()`; page execution in `lavtheme_cs_page_render_section()`.

**Verified live (2026):** custom section CSS now injects on pages; a section with
`placement=sidebar-right` wraps in `.lavcs-side-right` inside `.lavcs-pagewrap`;
front page + EDD grid unchanged; PHP runs only when unlocked, bad syntax is
rejected, and a fatal in one PHP section leaves the rest of the page intact.

---

*This document describes the state of the theme as built. If you change behaviour,
update this file in the same change so it stays the source of truth.*
