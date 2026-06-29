# lavzencom

Theme code for [lavzen.com](https://lavzen.com) — a digital‑products marketplace on WordPress + Easy Digital Downloads.

## Repository layout

```
lavzencom/
├── lavtheme/      # Legacy theme (the version currently live). Baseline / reference.
├── lavzentheme/   # New theme — clean OOP, module-based refactor of lavtheme. Active work.
└── docs/          # Architecture notes & context docs.
```

## lavzentheme — architecture

A modern, professional rebuild of `lavtheme`, modeled on premium-theme architecture
(WoodMart/XTS) and modernized:

- **Namespaced OOP** under `Lavzen\` with **Composer PSR-4** autoloading (optimized
  classmap). A hand-rolled PSR-4 fallback keeps it booting before `vendor/` is built.
- **Module system** — every feature (SEO, Code Studio, EDD integration, Blog, …) is a
  self-contained module implementing `Lavzen\Contracts\Module`, discovered from
  `config/modules.php` and booted by `Lavzen\Module_Manager`. Features toggle on/off
  like sub-plugins.
- **One `Context` class + `config/contexts.php`** replaces the legacy per-context
  template clones (single / 404 / shop / blog / account / auth …).
- `Lavzen\Support\Singleton` trait (XTS-style, `init()`-for-hooks) for cross-cutting services.

### Structure

```
lavzentheme/
├── style.css, functions.php      # theme header + tiny bootstrap
├── composer.json                 # PSR-4: Lavzen\ => src/
├── src/                          # all namespaced OOP
│   ├── Theme.php                 # bootstrap orchestrator
│   ├── Module_Manager.php        # discovers + boots modules
│   ├── Contracts/Module.php
│   ├── Support/Singleton.php
│   ├── Core/{Setup,Assets,Template}.php
│   ├── Context/{Context,Context_Registry}.php
│   └── Modules/                  # feature modules ("sub-plugins")
├── config/{modules,contexts}.php # registries
├── templates/ · template-parts/  # WordPress-native markup
└── assets/                       # css / js / images
```

### Build / deploy

```bash
composer install --no-dev --optimize-autoloader   # generate vendor/ autoloader
```

Requires PHP 8.1+. The theme boots without `vendor/` via the PSR-4 fallback in
`functions.php`, but production should ship the optimized Composer classmap.
