# lavtheme — Theme Code Studio

A per-section code editor in the WordPress dashboard. Edit each section's
**HTML/PHP**, **CSS**, **JS** and **Mobile CSS** with CodeMirror (syntax
highlighting, line numbers), plus a **Global** tab for `:root` variables,
global CSS/JS and the page background.

Open it at **WP Admin → Code Studio**.

## Sections

`Global · Sidebar · Header · Hero · Services · Products (EDD) · Case Studies ·
Blog · CTA · Footer`

Each section maps to a template part:

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

## Save modes

### 1. Database mode (default, safe)

- HTML/PHP, CSS, JS, Mobile CSS and the Global tab are stored in `wp_options`
  (`lavtheme_cs_<section>_<type>`).
- On the front end:
  - CSS (section + mobile + global + background) is printed in `wp_head`.
  - JS (section + global) is printed in `wp_footer`.
  - A saved **HTML override** replaces the section markup as **sanitised HTML**
    (no raw PHP is executed). If empty, the original template part runs —
    so dynamic sections (Products/EDD, Blog) keep working by default.
- The site can never white-screen, and nothing is lost on theme update.

### 2. File mode (powerful, opt-in)

Writes the **HTML/PHP** of a section directly to its theme file on disk.
CSS/JS continue to be injected from the database in both modes.

File mode is **locked** until you add this line to `wp-config.php`
(above `/* That's all, stop editing! */`):

```php
define( 'LAVTHEME_ALLOW_FILE_WRITE', true );
```

Safety guards on every file write:

1. **Automatic backup** of the current file to
   `wp-content/themes/lavtheme/.backups/<timestamp>/<path>` before writing.
2. **PHP syntax check** before saving (`php -l` when shell access is allowed,
   otherwise `token_get_all(..., TOKEN_PARSE)`). A syntax error blocks the save.
3. **Restore** button: lists the backups for that file and restores any of them
   in one click (the current version is backed up first).
4. Only the section template parts above can be written — `functions.php`,
   `header.php`, `footer.php` and `style.css` are **not** writable from the studio.

## Recovery — if the site breaks

1. **Switch back to Database mode** is not needed for white-screens caused by a
   bad file write, because the syntax check prevents saving invalid PHP. If a
   problem still occurs:
2. Open `wp-content/themes/lavtheme/.backups/` over FTP/SFTP. Each timestamped
   folder mirrors the theme path. Copy the last good file back over the broken
   one (e.g. `.backups/20260619-101500/template-parts/section-hero.php` →
   `template-parts/section-hero.php`).
3. Or remove `define( 'LAVTHEME_ALLOW_FILE_WRITE', true );` from `wp-config.php`
   to relock file mode, then clear the offending option in the database
   (`lavtheme_cs_<section>_html`).

## Security

- All saves require `manage_options` and a valid nonce.
- CSS is tag-stripped and dangerous tokens removed; JS closing tags neutralised;
  HTML overrides pass through an extended `wp_kses` allowlist (markup + inline
  SVG + `data-*`).
- Raw PHP is executed only in File mode, behind the wp-config constant.
