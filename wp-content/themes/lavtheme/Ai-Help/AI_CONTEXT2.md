# AI_CONTEXT2.md — Theme Code Studio internals (deep dive)

> Part of the **[Ai-Help](README.md)** doc set. This is the **deep reference for
> Code Studio itself** — how contexts, the registry, code storage, placement and
> the PHP tab actually work. For orientation/architecture read
> **[AI_CONTEXT.md](AI_CONTEXT.md)** first; for "which file" read
> **[AI_MAP.md](AI_MAP.md)**. Where this used to contradict the current build it
> has been corrected (this theme now runs **inline-CSS mode** and has the
> shop/blog/single/404/account contexts — the old "main.css is enqueued / no extra
> contexts" text was stale).

---

## 1. What Code Studio is

A custom admin panel at **WP Admin → Code Studio** (top-level menu, slug
`lavtheme-code-studio`, capability `manage_options`). It edits the theme's code
per **context** and per **section**, live, without FTP — using **CodeMirror** (the
copy WordPress ships via `wp_enqueue_code_editor`; no Monaco, no minimap).

Per editable "Template" section there are up to **five uniform tabs**
(`Lav_CS_Source_Reader::tabs()`):

| Tab key | Label | Default source |
|---------|-------|----------------|
| `html` | HTML / PHP | the resolved template file |
| `css` | CSS | the context stylesheet |
| `js` | JS | the context script |
| `mcss` | Mobile CSS | the `@media (max-width:640px)` layer, extracted from the CSS |
| `php` | PHP | context-specific server logic (override; empty default) |

The **Global** section exposes `css` / `js` / `bg` (background CSS); **Schema**
exposes `json` (JSON-LD). Those are the only "special" sections — there are **no
locked decorative sections**.

**Two save modes** (`lavtheme_cs_mode`):
1. **Database** (default, safe) — code stored in `wp_options`, injected at render.
   The site can never white-screen from an edit.
2. **File** (opt-in) — writes HTML/PHP to the theme file. Locked behind
   `define( 'LAVTHEME_ALLOW_FILE_WRITE', true )`; auto-backs up + syntax-checks
   first.

---

## 2. Contexts (the dropdown)

The context dropdown (built in `inc/code-studio.php`, ~line 443) switches what
you're editing. There are two families:

### a) Front Page + Pages
- **Front Page** — the original section builder: a registry of sections in
  `lavtheme_cs_registry`, drag-reorder, add/rename/delete/trash, zones
  (`settings`/`top`/`content`/`bottom`). Owned by
  `inc/code-studio-registry.php` + `inc/code-studio-inject.php`.
- **Every published Page** — read live from the DB (`lavtheme_cs_pages()`); each
  has a namespaced registry `lavtheme_cs_registry_page_<ID>` with **Global**,
  **Schema**, **Page Content** (the real `post_content`) + custom sections.
  Owned by `inc/code-studio-pages.php`. Custom sections render via a `the_content`
  filter (**Elementor canvas pages bypass this** — CSS/JS/Schema still apply).

### b) "dl-style" contexts (one shared plumbing)
These all reuse the **download (dl) plumbing** in
`inc/code-studio-downloads.php`. The panel dispatches them to `lavtheme_cs_dl_*`
AJAX because the client's `ctxIsDl()` (`assets/admin/code-studio.js`) matches
them:

```js
function ctxIsDl() {
  return currentCtx.indexOf('dl-') === 0 || currentCtx === 'shop'
      || currentCtx === 'blog' || currentCtx === '404'
      || currentCtx === 'single' || currentCtx === 'account';
}
```

| Context | Applies to | Render/inject file | Default template |
|---------|-----------|--------------------|------------------|
| `dl-template` | every `download` (single product) | `inc/code-studio-downloads.php` | `template-parts/single-download-body.php` |
| `shop` | download archive + `download_category/tag` | `inc/code-studio-shop.php` | `template-parts/shop.php` |
| `blog` | the blog index | `inc/code-studio-blog.php` | `template-parts/blog.php` |
| `single` | every blog post | `inc/code-studio-single.php` | `template-parts/single-article.php` |
| `404` | the error page | `inc/code-studio-404.php` | `template-parts/404.php` |
| `account` | the My Account dashboard | `inc/code-studio-account.php` | `template-parts/account.php` |

Each "dl-style" context exposes a clean **Global** + **Template** pair (no Schema,
no Page-Content anchor) — except `dl-template`, which also has an editable
**Schema (Product)** with `{{product_*}}` tokens and a non-editable **Product
Content** anchor.

### Adding a new dl-style context (the 7 touch-points)
This is exactly how `account` was added — follow it for any future context:

1. `assets/admin/code-studio.js` → add the key to **`ctxIsDl()`**.
2. `inc/code-studio.php` → add an `<option>` to the **context dropdown**.
3. `inc/code-studio-downloads.php` → add the key to **`lavtheme_cs_dl_valid()`**
   and to the archive branch of **`lavtheme_cs_dl_builtin()`** (Global + Template).
4. `inc/code-studio-source-reader.php` → add the key to **`resolve_template()`**
   (the HTML/PHP default) and to the **`source_path()` `$map`** (the css/js files).
5. Create `inc/code-studio-<key>.php` (render + `wp_head`/`wp_footer` injectors +
   any routing), modelled on `code-studio-shop.php`.
6. `functions.php` → `lavtheme_require( 'code-studio-<key>.php' );`.
7. Create the real default files: `template-parts/<key>.php`,
   `assets/css/<key>.css`, `assets/js/<key>.js`.

Export/Import works **automatically** for any context once `ctxIsDl()` +
`lavtheme_cs_dl_valid()` accept it (the tab list is built generically).

---

## 3. The dl plumbing (key functions, `code-studio-downloads.php`)

| Function | Purpose |
|----------|---------|
| `lavtheme_cs_dl_valid($ctx)` | Whether a context key is allowed. |
| `lavtheme_cs_dl_regopt($ctx)` | Registry option name (`lavtheme_cs_registry_<ctx>`). |
| `lavtheme_cs_dl_key($ctx,$slug,$type)` | Field option key. |
| `lavtheme_cs_dl_builtin($ctx)` | Default section registry for the context. |
| `lavtheme_cs_dl_fields($rec,$ctx)` | Tabs a section exposes (Global→css/js/bg; Template→`Source_Reader::tabs()`). |
| `lavtheme_cs_dl_get($ctx,$slug,$type)` | **Override-or-file** value (honours the `_empty` clear marker). |
| `lavtheme_cs_dl_compose_body($ctx,$file)` | Runs the Template override (PHP-gated) **or** includes the file. |

`Lav_CS_Source_Reader` (`inc/code-studio-source-reader.php`) is the single source
of truth for "what real file renders a context" and extracts the five tab
defaults from it.

---

## 4. How code is stored (`wp_options`)

Prefix is always **`lavtheme_cs_`** (never `lavtheme_code_…`).

| Key pattern | Holds |
|-------------|-------|
| `lavtheme_cs_registry` | Front-page section registry (ordered records). |
| `lavtheme_cs_registry_page_<ID>` | A page's registry. |
| `lavtheme_cs_registry_<ctx>` | A dl-style context registry (`shop`/`blog`/`single`/`404`/`account`/`dl_template`). |
| `lavtheme_cs_<slug>_<type>` | Front section code (`type` = `html`/`css`/`js`/`mcss`/`php`; global: `root`/`css`/`js`/`bg`). |
| `lavtheme_cs_page_<ID>_<slug>_<type>` | A page section's code. |
| `lavtheme_cs_<ctx>_<slug>_<type>` | A dl-style context's code (e.g. `lavtheme_cs_account_design_css`). |
| `lavtheme_cs_<key>_empty` | **Clear marker** — an intentional empty CSS/JS/Mobile save (inject nothing, don't fall back to the file). HTML is exempt. |
| `lavtheme_cs_<key>_prev` / `…_php_bak` | One-step backups (undo / restore). |
| `lavtheme_cs_schema` | Default JSON-LD (per-context schema overrides it). |
| `lavtheme_cs_mode` / `_minify` / `_header_global` | Save mode / minify toggle / "Header on all pages". |
| `lavtheme_account_page_id` / `lavtheme_blog_page_id` | Seeded page ids (My Account / Blog). |

**Override-or-file resolution:** a non-empty saved value wins; otherwise the real
file is used (so editors are never blank); a saved `_empty` marker means "inject
nothing" (an intentional clear must stick). "Reset to default" clears the marker.

**Section-storage layout on disk** (the editor defaults / fallback):
```
template-parts/section-<slug>.php      assets/css/sections/<slug>.css
assets/js/sections/<slug>.js           assets/css/sections/<slug>.mobile.css
```
Global is split into `global.root.css` (`:root` tokens), `global.bg.css`,
`global.css`, `assets/js/sections/global.js`.

---

## 5. Inline-CSS mode (current default)

`main.css` is **no longer enqueued** by default. `inc/enqueue.php` registers a
src-less `lavtheme-main` handle and attaches `lavtheme_cs_builtin_base_css()`
inline — the global `:root`/CSS/bg + every built-in section's CSS/Mobile, composed
from the editable split files in registry order (verified rule-for-rule identical
to the old `main.css`). Built-in CSS overrides layer on top via
`lavtheme_cs_head_css()` (wp_head pri 100).

- **Per-context CSS/JS** (shop/blog/single/dl/account) is **injected by that
  context's `_head`/`_footer`**, not enqueued — single source, edits apply live.
- **`products.css`** (front) and **`checkout.css`** (EDD flow) are the only real
  stylesheets still `wp_enqueue_style`'d, in `inc/enqueue.php`.
- **Rollback:** `define( 'LAVTHEME_DISABLE_INLINE_CSS', true )` re-enqueues
  `assets/css/main.css` exactly as before. `main.css` is therefore a
  **rollback-only artifact**; editing the split files is what matters live.

---

## 6. Placement & the PHP tab

### Placement (front + pages)
Content sections carry a `placement` field: `inline`/`before`/`after` (in-flow),
`sidebar-left`/`sidebar-right` (become grid columns in `.lavcs-pagewrap`),
`replace`, `wrap`. The front page only builds the grid wrapper **if** a sidebar
placement is used (otherwise output is byte-identical — zero pixel risk).

### Custom PHP tab
Optional pure-PHP per section, executed at render. **Guards (all enforced):**
1. **Lock** — runs only if `define( 'LAVTHEME_ALLOW_PHP_SECTIONS', true )`;
   otherwise saved but never executed.
2. `manage_options` + nonce on save.
3. **Syntax check** via `token_get_all( "<?php\n".$code, TOKEN_PARSE )` (blocks the
   save on a parse error).
4. **Isolation** — run in a closure with `try { } catch ( \Throwable )`, output
   buffered per section, so one section's fatal can't break the page.
5. **Backup** to `…_php_bak` before each save.

Helpers: `lavtheme_cs_php_allowed()`, `lavtheme_cs_check_php()`,
`lavtheme_cs_run_php()` (in `inc/helpers.php`).

---

## 7. Export / Import

`inc/code-studio-export.php` streams a section's **saved** code as versioned JSON
(`lavtheme-…-<date>.json`). The `tabs` map is built generically from the same
field source the UI uses, so any context/tab is covered with no code change.
Import is client-side (`assets/admin/code-studio.js`): it validates the envelope,
maps file tabs onto the destination's real tabs, then persists each tab through
the normal `lavtheme_cs_save` endpoint — so syntax checks, the PHP lock,
sanitisation and `_prev` backups all still apply.

---

## 8. Safeguards to preserve (lessons from real bugs)

1. **Dynamic sections (Products/Blog) contain raw `<?php`** — the render layer
   ignores a `<?php` HTML override and falls back to the file; saving HTML equal to
   the default deletes it. Keep both.
2. **Never minify source/PHP** — only rendered output; the JS minify pass is
   ASI-safe (keeps newlines).
3. **Section JS execution** — one outer IIFE; Global JS first; **each section's JS
   in its own inner IIFE** (prevents variable collisions). Preserve it.
4. **Lint for real** — `token_get_all(TOKEN_PARSE)`, or `php -l` (PHP 8.3 is now
   available locally). Brace-balance is **not** a syntax check.
5. **Per-context schema/CSS/JS are namespaced** — "my edit shows the default"
   almost always means the namespaced key wasn't saved, not an injector bug.

---

*This document is the deep reference for Code Studio. If you change how contexts,
storage, placement or the PHP tab behave, update it in the same change. See also:
[README.md](README.md) · [AI_MAP.md](AI_MAP.md) · [AI_CONTEXT.md](AI_CONTEXT.md).*
