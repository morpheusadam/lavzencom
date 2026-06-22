# Ai-Help — lavtheme documentation hub

> **Start here.** This folder is the documentation set for the **lavtheme**
> WordPress theme (live at `https://lavzen.com`, Hostinger). Read the file that
> matches your task; every doc links back here and to its siblings.

lavtheme in one line: a **classic PHP-template WordPress theme** built from a
single-page HTML design, with **Easy Digital Downloads (EDD)** for products and a
custom **Theme Code Studio** admin panel that makes (almost) every surface
editable — HTML/PHP · CSS · JS · Mobile CSS — without FTP.

---

## The doc set (what to read, when)

| Doc | Role | Read it when… |
|-----|------|---------------|
| **[AI_MAP.md](AI_MAP.md)** | **WHERE** — "request → file" router | You know *what* you want to change and need the file. Start here for almost everything. |
| **[AI_CONTEXT.md](AI_CONTEXT.md)** | **WHAT / HOW / WHY** — architecture, data flow, gotchas, deploy | You need to understand how a subsystem actually works before editing. |
| **[AI_CONTEXT2.md](AI_CONTEXT2.md)** | **DEEP DIVE** — Code Studio internals | You're extending Code Studio itself (contexts, registry, storage, placement, PHP tab). |
| **[AI_SKILLS.md](AI_SKILLS.md)** | **WHICH skills** to activate per task | Every request — pick the right assistant skills (wordpress-pro, frontend-design, …). |
| **[SINGLE-PRODUCT-GUIDE.md](SINGLE-PRODUCT-GUIDE.md)** | Feature guide — the single Download page | Working on the product page (`dl-template` context). |
| **[DEPLOY.md](DEPLOY.md)** | **SHIP** — push to the host safely (FTP + lint + cache) | You're about to upload changes to the live site. |
| **[ui-ux-pro-max/](ui-ux-pro-max/)** | Vendored UI/UX skill (read-only reference) | You want the offline UI/UX catalogs. **Don't edit** — it's a third-party copy. |

**Suggested reading order for a newcomer:** this README → `AI_MAP.md` →
`AI_CONTEXT.md` → (only if touching Code Studio) `AI_CONTEXT2.md`.

---

## The one mental model

```
Admin edits in Code Studio  ──AJAX──▶  wp_options (or theme file in File mode)
                                            │
                          (read at render)  ▼
Front end:  inc/code-studio-inject.php (front page) and the per-context
            injectors (pages / dl-template / shop / blog / single / 404 /
            account) read those options and inject CSS → wp_head, JS →
            wp_footer, and section markup into the page.
```

Everything else is detail. See [AI_CONTEXT.md](AI_CONTEXT.md) §"How it works".

---

## Editable contexts (the Code Studio dropdown)

Each context is a self-contained editable surface. All but the Front Page reuse
the same "dl" plumbing (`lavtheme_cs_dl_*`).

| Context | Edits | Owner file |
|---------|-------|-----------|
| **Front Page** | full section builder (add/reorder/delete) | `inc/code-studio-registry.php` + `-inject.php` |
| **Every published Page** | Global · Schema · Page Content · custom sections | `inc/code-studio-pages.php` |
| **Single Download (template)** | applies to every product | `inc/code-studio-downloads.php` |
| **Shop (archive)** | the download archive + taxonomies | `inc/code-studio-shop.php` |
| **Blog (archive)** | the blog index | `inc/code-studio-blog.php` |
| **Single Post (template)** | every blog post | `inc/code-studio-single.php` |
| **404 / Error page** | the error page | `inc/code-studio-404.php` |
| **My Account (dashboard)** | orders / downloads / profile | `inc/code-studio-account.php` |

---

## Non-negotiable rules (the short list)

1. **Front page stays pixel-perfect** unless a change is clearly approved.
2. **Dynamic sections (Products, Blog) contain raw `<?php`** — never turn their
   override into static kses'd HTML; the render layer falls back to the file.
3. **Never minify source/PHP** — only the rendered front-end output.
4. **Design tokens are the source of truth** — brand yellow `--cta:#f5c518` is the
   primary-CTA colour only; indigo `--accent` is secondary. See
   [AI_CONTEXT.md](AI_CONTEXT.md) §"Design tokens".
5. **Lint every PHP change** (`php -l`, now available locally — PHP 8.3) before
   shipping. See [DEPLOY.md](DEPLOY.md).
6. **Don't FTP-upload without the owner's go-ahead**; after any upload, **purge the
   hcdn cache**.

> Keep these docs honest: **when you change behaviour, update the matching doc in
> the same change.**
