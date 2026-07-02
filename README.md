<div align="center">

# 🪟 Lavzen — Digital Marketplace WordPress Theme

### A premium, OOP, module-based WordPress theme powering the [lavzen.com](https://lavzen.com) digital-products marketplace — Liquid-Glass design system, Easy Digital Downloads integration, and a built-in Code Studio.

<p>
  <img src="https://img.shields.io/github/license/morpheusadam/lavzencom?style=for-the-badge&color=4c1" alt="License" />
  <img src="https://img.shields.io/github/stars/morpheusadam/lavzencom?style=for-the-badge&color=ffca28" alt="Stars" />
  <img src="https://img.shields.io/github/forks/morpheusadam/lavzencom?style=for-the-badge&color=42a5f5" alt="Forks" />
  <img src="https://img.shields.io/github/last-commit/morpheusadam/lavzencom?style=for-the-badge&color=8e44ad" alt="Last commit" />
  <img src="https://img.shields.io/github/repo-size/morpheusadam/lavzencom?style=for-the-badge&color=e67e22" alt="Repo size" />
</p>

<p>
  <img src="https://img.shields.io/badge/WordPress-6.4%2B-21759B?style=for-the-badge&logo=wordpress&logoColor=white" alt="WordPress" />
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/Easy%20Digital%20Downloads-EDD-35495E?style=for-the-badge&logo=wordpress&logoColor=white" alt="Easy Digital Downloads" />
  <img src="https://img.shields.io/badge/Composer-PSR--4-885630?style=for-the-badge&logo=composer&logoColor=white" alt="Composer" />
  <img src="https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript" />
  <img src="https://img.shields.io/badge/CSS3-Glassmorphism-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3" />
</p>

</div>

---

## 📖 Overview

**Lavzen** is a custom **WordPress theme** that powers [lavzen.com](https://lavzen.com), a **digital-products marketplace** built on **Easy Digital Downloads (EDD)**. It pairs a distinctive **Liquid-Glass (glassmorphism) design system** with a developer-focused **Code Studio** options panel, delivering a fast, modern storefront for selling downloadable products, themes, plugins, and assets.

The repository tracks **two generations of the theme**. `lavtheme/` is the original, production theme — a fully featured EDD storefront with section-based templates, an in-dashboard Code Studio editor, and bundled mini-plugins (caching, security, dashboard skin, shortcodes). `lavzentheme/` is a clean **object-oriented, module-based rebuild** modeled on premium-theme architecture (WoodMart/XTS style): namespaced `Lavzen\` classes, **Composer PSR-4 autoloading**, a pluggable **Module Manager**, and a single `Context` engine that replaces per-template clones.

This theme is ideal for **WordPress developers, digital-product sellers, and agencies** who want a maintainable, scalable EDD marketplace front-end with a strong visual identity and a clean separation of concerns.

> 🔎 **Keywords:** WordPress theme, digital marketplace theme, Easy Digital Downloads theme, EDD storefront, glassmorphism WordPress, Liquid-Glass design, OOP WordPress theme, PSR-4 WordPress, module-based theme, Code Studio, lavzen.com.

---

## ✨ Features

- 🛒 **Easy Digital Downloads storefront** — shop, single-download, checkout, account, and category/tag archive templates wired to EDD.
- 🪟 **Liquid-Glass design system** — a cohesive glassmorphism UI (`lavzen-glass.css`, `lavzen-bg.css`, `lavzen-ui.css`) with per-section desktop and mobile stylesheets.
- 🧩 **Code Studio options panel** — an in-dashboard editor (front + per-context) with source reader, registry, history, export, and live injection of CSS/markup into pages.
- 🧱 **Section-based templates** — hero, products, services, collections, CTA, social proof, trust, blog, and more, assembled as reusable template parts.
- 🧰 **Bundled mini-plugins** — caching, security hardening, shortcodes, and a custom WordPress dashboard skin loaded via a lightweight plugin loader.
- 📝 **Blog + SEO modules** — dedicated blog UI, single-article layouts, backlink checker, and on-theme SEO handling.
- 🏗️ **Clean OOP rebuild (`lavzentheme/`)** — namespaced `Lavzen\` code, Composer PSR-4 autoloading, a Module Manager that toggles features like sub-plugins, and a single `Context` engine driven by `config/contexts.php`.
- ⚡ **Performance-aware** — dedicated performance include and split, section-scoped asset enqueuing.

---

## 🛠️ Tech Stack

| Layer | Technology |
| --- | --- |
| CMS | WordPress 6.4+ |
| Language | PHP 8.1+ |
| Commerce | Easy Digital Downloads (EDD) |
| Architecture | Namespaced OOP, PSR-4 (Composer), Module system, Context engine |
| Front-end | Vanilla JavaScript, modular CSS3 (glassmorphism), responsive mobile stylesheets |

<p align="center">
  <img src="https://skillicons.dev/icons?i=wordpress,php,js,css" alt="Tech stack" />
</p>

---

## 🚀 Getting Started

### Prerequisites

- **WordPress 6.4+**
- **PHP 8.1+**
- **Easy Digital Downloads** plugin installed and active
- **Composer** (only required for the `lavzentheme/` OOP build)

### Installation

```bash
git clone https://github.com/morpheusadam/lavzencom.git
```

**Legacy theme (`lavtheme/`)** — copy the folder into your WordPress themes directory and activate it:

```bash
cp -r lavzencom/lavtheme /path/to/wp-content/themes/lavtheme
# then activate "lavtheme" from Appearance → Themes
```

**OOP theme (`lavzentheme/`)** — generate the optimized Composer autoloader, then install:

```bash
cd lavzencom/lavzentheme
composer install --no-dev --optimize-autoloader
cp -r ../lavzentheme /path/to/wp-content/themes/lavzentheme
# then activate "Lavzen" from Appearance → Themes
```

> ℹ️ `lavzentheme` boots without `vendor/` via a PSR-4 fallback in `functions.php`, but production should ship the optimized Composer classmap.

---

## 🗂️ Project Structure

```text
lavzencom/
├── lavtheme/        # Legacy production theme (EDD storefront, Code Studio, mini-plugins)
│   ├── inc/         # code-studio, edd, seo, performance, blog, setup includes
│   ├── template-parts/  # section-* and context page templates
│   ├── plugins/     # caching · security · shorts · wp-dash mini-plugins
│   ├── assets/      # css · js · admin (Code Studio) assets
│   └── style.css    # theme header
├── lavzentheme/     # New OOP, module-based rebuild
│   ├── src/         # Lavzen\ namespaced classes (Theme, Module_Manager, Core, Context)
│   ├── config/      # modules.php · contexts.php registries
│   ├── composer.json    # PSR-4: Lavzen\ => src/
│   └── style.css    # theme header (Lavzen)
└── docs/            # architecture & AI context notes
```

---

## 🤝 Contributing

Contributions are welcome! Open an [issue](https://github.com/morpheusadam/lavzencom/issues) or submit a pull request with improvements to templates, modules, design tokens, or the Code Studio.

## 📜 License

Distributed under the **GNU General Public License v2 or later** (per the theme header). See [`LICENSE`](LICENSE) for details.

---

<div align="center">

### 👤 Author — Morpheus Adam

Web developer & cheerful hacker · PHP · Laravel · Go

<p>
  <a href="https://github.com/morpheusadam"><img src="https://img.shields.io/badge/GitHub-morpheusadam-181717?style=for-the-badge&logo=github&logoColor=white" alt="GitHub" /></a>
  <a href="https://sam.zeonic.me"><img src="https://img.shields.io/badge/Website-sam.zeonic.me-4c1?style=for-the-badge&logo=googlechrome&logoColor=white" alt="Website" /></a>
  <a href="mailto:morpheusadam95@gmail.com"><img src="https://img.shields.io/badge/Email-Contact-D14836?style=for-the-badge&logo=gmail&logoColor=white" alt="Email" /></a>
</p>

⭐ **If this theme inspired your next WordPress marketplace, consider giving it a star!** ⭐

</div>


---

## ⭐ Star History

<a href="https://star-history.com/#morpheusadam/lavzencom&Date">
  <img src="https://api.star-history.com/svg?repos=morpheusadam/lavzencom&type=Date" alt="lavzencom — Star History Chart" width="70%" />
</a>

<div align="center">

### If this project helps you, please give it a ⭐

A star helps other developers discover **lavzencom** and supports continued development.

</div>
