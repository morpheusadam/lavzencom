<!-- ╔══════════════════════════════════════════════════════════════════╗ -->
<!-- ║                            lavtheme                                ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════╝ -->

<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:7c83ff,50:8b5cf6,100:22d3ee&height=220&section=header&text=lavtheme&fontSize=80&fontColor=ffffff&animation=fadeIn&fontAlignY=38&desc=A%20pixel-perfect%20glassmorphism%20WordPress%20theme%20with%20a%20live%20Code%20Studio&descAlignY=58&descSize=18" width="100%" alt="lavtheme" />

<a href="https://github.com/morpheusadam/wptheme">
  <img src="https://readme-typing-svg.demolab.com?font=Oswald&weight=600&size=26&pause=900&color=7C83FF&center=true&vCenter=true&width=720&lines=Edit+every+section's+code+live%2C+from+wp-admin.;Easy+Digital+Downloads%2C+shop%2C+blog+%26+single-product.;No+FTP.+No+white-screens.+Pixel-perfect.;Server-Sent-Events+Backlink+Spam+Checker+built+in." alt="typing" />
</a>

<br/>

<!-- ░░░░░░░░░░░░░░░░░░░░  BADGES  ░░░░░░░░░░░░░░░░░░░░ -->
<p>
  <img src="https://img.shields.io/badge/WordPress-Classic%20Theme-21759B?style=for-the-badge&logo=wordpress&logoColor=white" alt="WordPress" />
  <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/Easy%20Digital%20Downloads-Integrated-35495E?style=for-the-badge&logo=wordpress&logoColor=white" alt="EDD" />
  <img src="https://img.shields.io/badge/License-GPL--2.0-4c1?style=for-the-badge&logo=gnu&logoColor=white" alt="License" />
</p>
<p>
  <img src="https://img.shields.io/github/last-commit/morpheusadam/wptheme?style=flat-square&color=7c83ff&logo=git&logoColor=white" alt="last commit" />
  <img src="https://img.shields.io/github/repo-size/morpheusadam/wptheme?style=flat-square&color=22d3ee" alt="repo size" />
  <img src="https://img.shields.io/github/languages/top/morpheusadam/wptheme?style=flat-square&color=8b5cf6" alt="top language" />
  <img src="https://img.shields.io/github/commit-activity/m/morpheusadam/wptheme?style=flat-square&color=a3e635" alt="commit activity" />
  <img src="https://img.shields.io/github/stars/morpheusadam/wptheme?style=flat-square&color=e8c547&logo=github" alt="stars" />
</p>

</div>

---

<div align="center">

### ✦ &nbsp; A landing page you can re-code from the dashboard &nbsp; ✦

</div>

> **lavtheme** is a classic (PHP-template) WordPress theme built from a single-page
> glassmorphism design — transferred *verbatim*, then split into editable sections.
> Its centerpiece is **Theme Code Studio**: edit the HTML/PHP · CSS · JS · Mobile CSS · PHP
> of **every section, page, product, the shop and the blog** — live, from `wp-admin`,
> with database-safe injection (never white-screens) or opt-in file writes.

---

## 📑 Table of Contents

- [✨ Features](#-features)
- [🧠 Architecture](#-architecture)
- [🧩 Theme Code Studio](#-theme-code-studio)
- [🛒 Commerce & Content](#-commerce--content)
- [🛰️ Backlink Spam Checker](#️-backlink-spam-checker)
- [🚀 Install](#-install)
- [🛡️ Safety model](#️-safety-model)
- [🧰 Tech stack](#-tech-stack)
- [📊 Activity](#-activity)

---

## ✨ Features

<table>
  <tr>
    <td width="50%" valign="top">

#### 🎛️ Live Code Studio
Edit **every** section's code in `wp-admin` — HTML/PHP, CSS, JS, Mobile CSS & a guarded PHP tab. Drag to reorder, add/rename/delete, trash & restore.

#### 🧱 Dynamic section registry
Sections aren't hardcoded — they live in `wp_options`. Front page, **every page**, single product, shop & blog each get their own editable context.

#### 🛍️ Easy Digital Downloads
Real product grid, category bubbles, a full **shop archive** (search, multi-category, price slider, tags, sort) and a rich **single-product** template — all server-side & SEO-safe.

    </td>
    <td width="50%" valign="top">

#### 🪄 Database-safe injection
Code is stored in options and injected at render — **the site can never white-screen**. File mode (opt-in, locked behind a constant) writes to disk with auto-backup + syntax check.

#### 📰 Blog engine
`blog.html` design wired to real posts: featured post, category pills, sort (latest/popular/read-time/A–Z), date & author filters, sidebar widgets — pixel-perfect.

#### 🛰️ Backlink Spam Checker
A standalone admin tool that streams results **live** via Server-Sent Events, with an automatic AJAX-polling fallback, and exports a Google **disavow** file.

    </td>
  </tr>
</table>

---

## 🧠 Architecture

```mermaid
flowchart TD
    FP["functions.php<br/>bootstrap"] --> HLP["inc/helpers.php"]
    FP --> SET["inc/setup.php"]
    FP --> ENQ["inc/enqueue.php<br/>inline split CSS"]
    FP --> EDD["inc/edd*.php<br/>products · shop · single"]
    FP --> BLOG["inc/blog*.php<br/>archive engine"]
    FP --> CS["inc/code-studio*.php<br/>registry · save · inject · pages · dl · shop · blog"]
    FP --> BLC["inc/backlink-checker.php<br/>SSE tool"]

    subgraph ADMIN["🖥️ wp-admin"]
      CS -->|CodeMirror + AJAX| PANEL["Code Studio panel"]
    end

    subgraph DB[("wp_options")]
      OPT["lavtheme_cs_*<br/>registries + per-section code"]
    end
    PANEL -->|save| OPT

    subgraph FRONT["🌐 front-end"]
      direction LR
      HEAD["wp_head → CSS"] --- BODY["template-parts/*"] --- FOOT["wp_footer → JS"]
    end
    OPT -->|read at render| FRONT
    BLOG --> FRONT
    EDD --> FRONT

    classDef core fill:#7c83ff,stroke:#fff,color:#fff;
    classDef store fill:#22d3ee,stroke:#fff,color:#06303a;
    class FP,CS,BLC core;
    class OPT store;
```

**Flow:** admin edits in Code Studio → AJAX → saved to `wp_options` (or, in File mode, theme files) → on the front end the injectors read those options and emit CSS in `wp_head`, JS in `wp_footer`, and section markup into the page — keeping the original design **pixel-perfect**.

---

## 🧩 Theme Code Studio

| Capability | What it does |
|---|---|
| **Per-section editors** | HTML/PHP · CSS · JS · Mobile CSS (`@media ≤640`) · guarded PHP |
| **Global & Schema** | `:root` vars, global CSS/JS/background, and JSON-LD per context |
| **Contexts** | Front Page · every published Page · Single Download (template) · Shop (archive) · Blog (archive) |
| **Placement** | `inline` · `sidebar-left/right` · `replace` · `wrap` (responsive grid) |
| **Save modes** | **Database** (safe live inject, default) · **File** (disk write, backup + lint, opt-in) |
| **Import / Export** | Versioned JSON per section — re-imported through the same guarded save path |
| **Reset / Restore / Trash** | One-click reset to file default, one-step undo, restorable section trash |

> Editor: WordPress' bundled **CodeMirror** — dark skin, folding, bracket matching, `Ctrl-Space` autocomplete.

---

## 🛒 Commerce & Content

```mermaid
flowchart LR
    A["EDD download"] --> B["Front grid"]
    A --> C["Shop archive<br/>search · cats · price · tags · sort"]
    A --> D["Single product<br/>gallery · specs · tabs · related"]
    P["WP post"] --> E["Blog archive<br/>featured · filters · sidebar"]
    style A fill:#7c83ff,color:#fff
    style P fill:#a3e635,color:#14210a
```

- **Dynamic shop URL** — read from EDD's `products_page`; never hardcoded.
- **Server-side filtering** — plain `GET` params on the real query → works with JS off, SEO-friendly.
- **No plugin template overrides** — restyle via CSS / wrap with custom sections; update-safe.

---

## 🛰️ Backlink Spam Checker

A self-contained admin tool at **Tools → Backlink Spam Checker** — zero front-end footprint.

```
#12/40  SPAM       cheap-casino-poker-bonus.xyz [0]  — High-risk TLD .xyz, 3 spam keywords, 3 hyphens
#13/40  CLEAN      example.com [200]  — No spam signals detected
#14/40  SUSPICIOUS foo-links.top | buy seo  — High-risk TLD .top, Suspicious anchor text
```

- **Live, line-by-line** output via **Server-Sent Events** (`text/event-stream`, buffering & gzip disabled, `X-Accel-Buffering: no`, 2 KB anti-buffer padding).
- **Auto-fallback to AJAX polling** if the host (CDN/LiteSpeed) buffers the stream — the client detects no events within 6 s and switches mid-run.
- **Heuristics:** risky TLDs, spam keywords, hyphen/digit ratios, long labels, suspicious anchor text + HTTP reachability (5 s timeout).
- **Export** a ready-to-use Google **disavow** `.txt` (`domain:example.com`).

---

## 🚀 Install

```bash
# Into your WordPress themes directory:
cd wp-content/themes
git clone https://github.com/morpheusadam/wptheme.git lavtheme
```

Then **Appearance → Themes → Activate**. Optional power-ups in `wp-config.php`:

```php
define( 'LAVTHEME_ALLOW_FILE_WRITE',   true ); // enable File save mode
define( 'LAVTHEME_ALLOW_PHP_SECTIONS', true ); // run the per-section PHP tab
```

> Requires **WordPress 6.0+** and **PHP 8.1+**. Easy Digital Downloads is optional (the theme degrades gracefully).

---

## 🛡️ Safety model

- 🔒 **Never white-screens** — DB mode injects code; a bad PHP tab is syntax-checked & sandboxed in `try/catch`.
- 🧪 **Real lint** — `token_get_all( …, TOKEN_PARSE )` (shell-free).
- 🧯 **Auto-backups** — every file write & PHP save snapshots the previous value.
- 🧹 **Secrets stay out of git** — credentials, `.vscode/`, `wp-config.php` & `.backups/` are git-ignored.

---

## 🧰 Tech stack

<div align="center">

<img src="https://skillicons.dev/icons?i=wordpress,php,js,css,html,mysql,git,github" alt="stack" />

</div>

---

## 📊 Activity

<div align="center">

<img src="https://github-readme-stats.vercel.app/api?username=morpheusadam&show_icons=true&theme=tokyonight&hide_border=true&bg_color=0b1120&title_color=7c83ff&icon_color=22d3ee&text_color=cdd3e4" height="170" alt="stats" />
<img src="https://github-readme-stats.vercel.app/api/top-langs/?username=morpheusadam&layout=compact&theme=tokyonight&hide_border=true&bg_color=0b1120&title_color=7c83ff&text_color=cdd3e4" height="170" alt="top langs" />

<br/>

<img src="https://github-readme-activity-graph.vercel.app/graph?username=morpheusadam&theme=tokyo-night&bg_color=0b1120&color=7c83ff&line=22d3ee&point=ffffff&hide_border=true&area=true" width="95%" alt="activity graph" />

<br/>

<img src="https://github-profile-trophy.vercel.app/?username=morpheusadam&theme=tokyonight&no-frame=true&column=7&margin-w=8" alt="trophies" />

</div>

---

<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:22d3ee,50:8b5cf6,100:7c83ff&height=120&section=footer" width="100%" alt="footer" />

<sub>Built with 🩵 for <b>lavzen.com</b> · Theme Code Studio · Easy Digital Downloads · GPL-2.0</sub>

</div>
