# lavtheme

A premium custom WordPress theme for **[lavzen.com](https://lavzen.com/)** — a modern
**Liquid-Glass** design system with an in-dashboard code editor (Theme Code Studio),
Easy Digital Downloads integration, a technical SEO/GEO layer, and a small
in-theme plugin framework.

---

## ✨ Highlights

| Area | What ships |
|------|-----------|
| **Design** | Liquid-Glass UI, dark reading surfaces, RTL-aware, `prefers-reduced-motion` safe |
| **Code Studio** | Per-section HTML/PHP · CSS · JS · Mobile-CSS editor in WP Admin |
| **Pages** | Front page, Blog (archive + single article), Shop (EDD), Single download, **standalone 404**, search |
| **SEO / GEO** | Meta + Open Graph + Twitter, JSON-LD, canonical, `/llms.txt`, AI-bot-friendly robots |
| **Performance** | Conditional asset loading, emoji/head cleanup, deferred JS, security headers |
| **Theme plugins** | Auto-loaded feature modules under `plugins/` (incl. an animated **WP Dash**) |

---

## 🧩 Theme plugins (`plugins/`)

Self-contained feature modules that ship and version **with the theme**.
`plugins/loader.php` auto-discovers every `plugins/<slug>/<slug>.php` — drop a
folder in and it loads (no `functions.php` edit). Each registers a submenu under
**WP Admin → Code Studio**.

| Module | Menu | Status |
|--------|------|--------|
| `wp-dash` | **WP Dash** | ✅ Animated analytics dashboard (live counts + SVG charts + pixel heatmap) |
| `caching` | **Caching** | 🚧 stub |
| `security` | **Security** | 🚧 stub |
| `user-dashboard` | **User Dashboard** | 🚧 stub |
| `shorts` | **Shorts** | 🚧 stub |

**WP Dash** renders professional, library-free charts (pure SVG + CSS): count-up
stat cards with sparklines, an animated gradient area chart, radial health rings,
a grow-in bar chart, and a **pixel-art activity heatmap** — all
`prefers-reduced-motion` safe and loaded only on their own screen.

Module convention:

```
plugins/<slug>/
  <slug>.php      ← entry (header + `defined( 'ABSPATH' ) || exit;`)
  assets/         ← optional css/js (enqueue scoped to the screen)
```

Shared helpers from the loader: `lavtheme_plugins_register_menu()` and
`lavtheme_plugins_placeholder()`.

---

## 🔍 SEO / GEO layer (`inc/seo.php`)

No SEO plugin is required. The theme emits, head/headers only (zero visual output):

- Context-aware **meta description**, **Open Graph**, **Twitter cards**, canonical
- **JSON-LD**: Organization, WebSite + SearchAction, BlogPosting, BreadcrumbList
  (EDD Product schema is emitted separately by the downloads context)
- Rich robots previews (`max-image-preview:large`)
- A dynamic **`/llms.txt`** and an AI-crawler-friendly **robots.txt**

## ⚡ Performance (`inc/performance.php`)

- Drops the emoji polyfill and legacy `<head>` links (RSD/WLW/shortlink/generator)
- Defers theme-owned scripts
- Sends hardening **security headers** (HSTS, COOP, X-Frame-Options, Referrer/Permissions-Policy)

## 🧱 Standalone 404 (`404.php`)

A chrome-free, full-viewport error page (no header/footer) with a creative
Liquid-Glass design, editable in Code Studio under **Error pages → 404 / Error page**.

---

## 🛠️ Theme Code Studio

A per-section code editor in the WordPress dashboard. Edit each section's
**HTML/PHP**, **CSS**, **JS** and **Mobile CSS** with CodeMirror, plus a
**Global** tab for `:root` variables, global CSS/JS and the page background.

Open it at **WP Admin → Code Studio**. Editable contexts include the front-page
sections plus page types: **Blog, Single article, Shop, Single download, 404**.

| Section  | File |
|----------|------|
| sidebar  | `template-parts/sidebar-rail.php` |
| header   | `template-parts/header-topbar.php` |
| hero     | `template-parts/section-hero.php` |
| services | `template-parts/section-services.php` |
| products | `template-parts/section-products.php` |
| work     | `template-parts/section-cases.php` |
| blog     | `template-parts/section-blog.php` |
| cta      | `template-parts/section-cta.php` |
| footer   | `template-parts/footer-content.php` |

### Save modes

**1. Database mode (default, safe)** — HTML/PHP, CSS, JS, Mobile CSS and Global
are stored in `wp_options` (`lavtheme_cs_<section>_<type>`). CSS is printed in
`wp_head`, JS in `wp_footer`. A saved HTML override replaces section markup as
**sanitised HTML** (no raw PHP); empty → the original template part runs, so
dynamic sections (Products/EDD, Blog) keep working. The site can never
white-screen, and nothing is lost on theme update.

**2. File mode (powerful, opt-in)** — writes a section's HTML/PHP straight to its
theme file. Locked until you add to `wp-config.php`:

```php
define( 'LAVTHEME_ALLOW_FILE_WRITE', true );
```

Guards on every file write: automatic timestamped **backup** to `.backups/`, a
**PHP syntax check** before saving, a one-click **Restore**, and a writable
allowlist (`functions.php`, `header.php`, `footer.php`, `style.css` are **not**
writable from the studio).

> Executable PHP sections are a separate opt-in:
> `define( 'LAVTHEME_ALLOW_PHP_SECTIONS', true );` (off by default).

### Recovery — if the site breaks

1. The syntax check blocks invalid PHP saves. If a problem still occurs:
2. Open `.backups/` over SFTP — each timestamped folder mirrors the theme path;
   copy the last good file back over the broken one.
3. Or remove the `define()` from `wp-config.php` and clear the offending option
   (`lavtheme_cs_<section>_html`).

### Security

- All saves require `manage_options` + a valid nonce.
- CSS is tag-stripped and dangerous tokens removed; JS closing tags neutralised;
  HTML overrides pass an extended `wp_kses` allowlist (markup + inline SVG + `data-*`).
- Raw PHP runs only behind the wp-config constants above.

---

## 📁 Layout

```
lavtheme/
├── *.php                 ← template hierarchy (front-page, single, archive, 404 …)
├── inc/                  ← setup, enqueue, blog, EDD, SEO, performance, Code Studio
├── template-parts/       ← section + body templates
├── assets/{css,js}/      ← styles & scripts (incl. per-section splits)
├── plugins/              ← auto-loaded in-theme feature modules
└── Ai-Help/              ← project docs + offline ui-ux-pro-max reference
```

> These are theme-scoped modules — **not** WordPress.org plugins; they don't
> appear under Plugins → Installed.
