# CHANGELOG-AI.md — lavtheme

Changes implemented in the AI working session (2026‑06‑22). Part of the
**[Ai-Help](README.md)** doc set. Newest first. Each entry lists the files and
the why, so the next dev/AI can extend it safely.

> Brand: the site is **Lavzen Web** (`lavzen.com`, `hello@lavzen.com`). The old
> "ChannelIQ" branding from the original HTML conversion has been removed — see
> entry 8. Do **not** reintroduce ChannelIQ anywhere.

---

## 1. Single post — pro reading experience
- **Files:** `single.php`, `assets/css/single.css`, `assets/js/single.js`,
  enqueue in `inc/enqueue.php` (on `is_singular('post')`).
- Full article layout: breadcrumb, category pills, rich meta (avatar/date/read
  time/comments), hero figure, sticky share rail (X/LinkedIn/Facebook + copy),
  rich prose (dark reading surface, ~70ch, drop-cap), tags, author bio,
  prev/next, related posts (reuses the blog card), comments.
- JS is progressive: reading-progress bar, auto heading anchors, TOC scroll-spy,
  copy-link, AJAX likes/save, comments UX. Works fully without JS.

## 2. Technical SEO + GEO (AI search) — head/headers only, zero visual output
- **File:** `inc/seo.php` (required in `functions.php`).
- No SEO plugin is active, so the theme emits: context-aware **meta description**,
  **Open Graph**, **Twitter cards**, canonical for non-singular views, rich
  **robots** previews (`max-image-preview:large`), and **JSON-LD** (Organization,
  WebSite + SearchAction, BlogPosting, BreadcrumbList). EDD Product schema stays
  in the downloads context (no duplicate).
- Serves a dynamic **`/llms.txt`** and an AI-crawler-friendly **robots.txt**
  (GPTBot / PerplexityBot / ClaudeBot / Google-Extended).
- Filters: `lavtheme_seo_description`, `lavtheme_seo_default_description`,
  `lavtheme_seo_default_image`, `lavtheme_seo_social_profiles`.

## 3. Performance hardening (non-visual)
- **File:** `inc/performance.php`.
- Drops the emoji polyfill + legacy `<head>` links (RSD/WLW/shortlink/generator),
  defers theme-owned scripts, and sends **security headers** (HSTS, COOP,
  X-Frame-Options, X-Content-Type-Options, Referrer/Permissions-Policy) on
  front-end HTML only. Filter: `lavtheme_security_headers`.

## 4. Standalone 404 / error page (no header/footer)
- **Files:** `404.php` (standalone document), `template-parts/404.php` (editable
  body), `assets/css/404.css`, `assets/js/404.js`, `inc/code-studio-404.php`.
- Chrome-free immersive error page; still calls `wp_head`/`wp_footer`.
- Editable in **Code Studio → Error pages → 404 / Error page**.
- **Gotcha fixed:** `lavtheme_cs_head_css` + `lavtheme_cs_footer_js` now early-return
  on `is_404()` so the front-page global JS (which needs the header DOM) never
  runs on the chrome-less 404.

## 5. In-theme plugin framework (`plugins/`)
- **Files:** `plugins/loader.php` (+ `functions.php` require).
- Auto-discovers `plugins/<slug>/<slug>.php` — drop a folder in, it loads. Shared
  helpers: `lavtheme_plugins_register_menu()`, `lavtheme_plugins_placeholder()`,
  `lavtheme_plugins_parent_slug()`, `lavtheme_plugins_cap()`.
- Modules register a submenu under **Code Studio**.
- Current modules: `wp-dash` (built), `caching` / `security` / `shorts` (stubs).
  `user-dashboard` was created then removed on request.

## 6. WP Dash — animated analytics page + glass dashboard skin
- **Files:** `plugins/wp-dash/wp-dash.php`, `assets/dashboard.{css,js}` (analytics
  page), `template.php` + `assets/dash-skin.{css,js}` (native dashboard skin).
- **Analytics page** (Code Studio → WP Dash): library-free SVG/CSS charts —
  count-up stat cards + sparklines, gradient area chart, radial rings, bar chart,
  and a **pixel-art activity heatmap**. Assets enqueued only on its screen.
- **Native WordPress Dashboard skin** (Dashboard → Home): a **glassmorphism /
  liquid-glass** default — animated gradient backdrop, glass widgets, and a
  welcome hero with count-up quick stats. **Scoped strictly to `body.index-php`**;
  cosmetic only; `prefers-reduced-motion` safe. Applied via
  `admin_head` / `admin_notices` / `admin_footer` as override-or-file.
- Editable as the Code Studio **"WP Dash (dashboard)"** context (HTML/CSS/JS/
  Mobile-CSS). Wiring lives in the usual 5 spots (see entry 9).

## 7. EDD checkout — professional flow + empty state
- **Files:** `inc/edd-checkout.php` (required in `functions.php`),
  `assets/css/checkout.css`.
- Via EDD hooks (no core markup touched): a designed **empty-cart** state (icon,
  copy, shop CTA, popular products), a **"Secure checkout"** header with 3 steps,
  and **trust badges**. All kses-safe (icons are CSS masks, not inline SVG).
- Layout upgraded to a centered, focused single column.

## 8. Brand unification — ChannelIQ → Lavzen Web  ✅
- **Files:** `template-parts/header-topbar.php`, `template-parts/footer-content.php`
  (logo SVG text, email, copyright, social aria-labels), `style.css` (theme URIs).
- **Safety net for DB overrides:** `lavtheme_brand_normalize()` in
  `inc/code-studio-inject.php` rewrites legacy brand tokens in any stored Code
  Studio HTML override at render time. Filter: `lavtheme_brand_replacements`.
- **Still TODO outside the theme:** the EDD transactional-email "from name/email"
  lives in **EDD → Settings → Emails** (database, not theme) — set it to
  Lavzen Web / hello@lavzen.com there. Also confirm Settings → General site title.

## 9. How a new Code Studio page-type context is wired (reference)
Mirror the `404` / `wp-dash` pattern — 5 edits:
1. `inc/code-studio-downloads.php` → `lavtheme_cs_dl_valid()` returns true; add to
   the archive list in `lavtheme_cs_dl_builtin()`.
2. `inc/code-studio-source-reader.php` → `resolve_template()` (HTML default) +
   `source_path()` map (CSS/JS defaults).
3. `inc/code-studio.php` → an `<option>` in the context dropdown (with `data-view`).
4. `assets/admin/code-studio.js` → add the slug to `ctxIsDl()`.
5. A renderer that reads override-or-file (`lavtheme_cs_dl_get` /
   `lavtheme_cs_dl_compose_body`) and injects CSS/JS/HTML on the matching screen.

## 10. Dead-code report (no code removed)
Of 374 functions, 3 were unreferenced:
- `lavtheme_cs_is_inline_placement()` (`inc/code-studio-pages.php`) — safe to remove.
- `lavtheme_shop_categories_nav()` (`inc/menus.php`) — public helper; verify Code
  Studio DB overrides first (AI_CONTEXT.md's "wired in header-topbar" note is stale).
- `lavtheme_svg()` (`inc/helpers.php`) — public helper; its pair
  `lavtheme_svg_allowed_html()` IS used. Verify overrides before removing.
- **Note:** `assets/css/sections/*.css` and `assets/js/sections/*.js` with 0 literal
  references are NOT dead — they load via dynamic paths (`code-studio.php` →
  `'assets/css/sections/' . $section . '.css'`).
