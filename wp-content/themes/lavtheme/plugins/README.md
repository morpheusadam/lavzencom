# Theme plugins (`lavtheme/plugins/`)

In-theme plugin modules for **lavtheme**. Each subfolder here is a self-contained
feature module (mini-plugin) that the theme loads — kept here instead of
`wp-content/plugins/` so it ships and versions with the theme.

## Convention

```
plugins/
  <plugin-slug>/
    <plugin-slug>.php   ← entry file (guarded with: defined( 'ABSPATH' ) || exit;)
    inc/                ← optional: split logic
    assets/             ← optional: css/js for this module
    README.md           ← what it does
```

Each entry file should start with a header and an `ABSPATH` guard, e.g.:

```php
<?php
/**
 * Plugin: Example Feature
 * Description: What this module adds.
 *
 * @package lavtheme
 */

defined( 'ABSPATH' ) || exit;
```

## Loading

These are **not** auto-discovered yet. To activate a module, require its entry
file from `functions.php` (next to the other `lavtheme_require()` calls), or ask
to add a small autoloader that includes every `plugins/*/<slug>.php` automatically.

> Note: these are theme-scoped modules, **not** WordPress.org plugins — they
> don't appear under Plugins → Installed and have no activation hook.
