# AI_MAP.md — نقشهٔ مسیریابی سریع lavtheme

> هدف این فایل: **بدون خواندن همهٔ فایل‌ها**، با یک نگاه بفهمم برای هر درخواست باید
> سراغ کدام فایل بروم. جزئیاتِ «چرا/چطور» در `AI_CONTEXT.md` و `AI_CONTEXT2.md` است؛
> این فایل فقط «کجا» را می‌گوید. اگر رفتاری عوض شد، این جدول‌ها را هم به‌روز کن.

نقشهٔ ذهنی پروژه در یک خط: **تم کلاسیک وردپرس (lavzen.com)** که از یک طراحی HTML
ساخته شده، محصولاتش با **EDD** است، و قلبش **Theme Code Studio** (ویرایش کد هر سکشن
از پنل ادمین، ذخیره در `wp_options` یا فایل). بوت‌استرپ در `functions.php`، منطق در
`inc/`، خروجی در `template-parts/` و `assets/`.

---

## ۱) جدول «درخواست → فایل» (مهم‌ترین بخش)

| اگر درخواست دربارهٔ… | برو سراغ |
|---|---|
| **راه‌اندازی/ثبت ماژول‌ها، ثابت‌ها** | `functions.php` |
| **theme supports، منوها، image sizes** | `inc/setup.php` |
| **لوکیشن منوها، topnav، اکانت پاپ‌اور، seed منو** | `inc/menus.php` + `template-parts/header-topbar.php` |
| **enqueue کردن CSS/JS، حالت inline-css، cache-bust** | `inc/enqueue.php` |
| **helperها: `lavtheme_option`, kses, sanitize css, اجرای PHP سکشن** | `inc/helpers.php` |
| **گرید محصولات/حباب دسته‌ها در صفحهٔ اصلی (EDD)** | `inc/edd.php` + `template-parts/section-products.php` |
| **آرشیو/فروشگاه: فیلتر، مرتب‌سازی، قیمت، کوئری اصلی** | `inc/edd-shop.php` (داده/کوئری) |
| **UI فروشگاه: هیرو، سایدبار فیلتر، تول‌بار، چیپ‌ها، quick view** | `inc/edd-shop-ui.php` + `assets/css/shop.css` + `assets/js/shop.js` |
| **تمپلیت چیدمان فروشگاه** | `template-parts/shop.php`، `template-parts/shop-page-template.php` |
| **صفحات آرشیو فروشگاه** | `archive-download.php`, `taxonomy-download_category.php`, `taxonomy-download_tag.php` |
| **تک‌محصول (single download): قالب/CSS/JS** | `single-download.php` + `template-parts/single-download-body.php` + `assets/css/single-product.css` + `assets/js/single-product.js` |
| **متادیتای محصول EDD، توکن‌های `{{product_*}}`** | `inc/edd-product-meta.php`, `inc/edd-single-product-hooks.php`, `inc/edd.php` |
| **بلاگ: کوئری، فیلتر `bcat/bsort/...`، featured، read time** | `inc/blog.php` (موتور) |
| **UI بلاگ: کارت‌ها، فیلتر بار، سایدبار، pagination** | `inc/blog-ui.php` + `assets/css/blog.css` + `assets/js/blog.js` |
| **تمپلیت آرشیو بلاگ / صفحهٔ بلاگ** | `template-parts/blog.php`, `template-parts/blog-page-template.php`, `home.php`, `archive.php`, `search.php` |
| **سکشن بلاگ صفحهٔ اصلی** | `template-parts/section-blog.php` |

### Code Studio (پنل ادمین ویرایش کد)
| اگر درخواست دربارهٔ… | برو سراغ |
|---|---|
| **رجیستری سکشن‌های صفحهٔ اصلی، add/rename/delete/reorder/placement** | `inc/code-studio-registry.php` |
| **منوی ادمین، رندر پنل، enqueue ادیتور، accessorها** | `inc/code-studio.php` |
| **ذخیره/بازیابی/بکاپ/نوشتن فایل/چک سینتکس + اکثر AJAXها** | `inc/code-studio-save.php` |
| **رندر سکشن جلو، تزریق CSS به head/JS به footer، schema، minifier** | `inc/code-studio-inject.php` |
| **کانتکست هر صفحهٔ منتشرشده (per-page registry, Page Content)** | `inc/code-studio-pages.php` |
| **کانتکست تک‌محصول EDD (dl-template, توکن‌ها)** | `inc/code-studio-downloads.php` |
| **کانتکست فروشگاه (آرشیو) در Code Studio** | `inc/code-studio-shop.php` |
| **کانتکست بلاگ (آرشیو) در Code Studio** | `inc/code-studio-blog.php` |
| **Export/Import کد سکشن (JSON)** | `inc/code-studio-export.php` |
| **تشخیص EDD/Woo (deprecated)** | `inc/code-studio-contexts.php` |
| **UI/منطق پنل: CodeMirror, tabs, sortable, AJAX کلاینت** | `assets/admin/code-studio.js` + `assets/admin/code-studio.css` |

### پوستهٔ صفحه و سکشن‌های صفحهٔ اصلی
| اگر درخواست دربارهٔ… | برو سراغ |
|---|---|
| **چیدمان کلی، باز کردن `.app`/`.main`، سایدبار/هدر** | `header.php` |
| **بستن شل، فوتر، `wp_footer`** | `footer.php` |
| **حلقهٔ سکشن‌های محتوای صفحهٔ اصلی** | `front-page.php` |
| **هیرو / سرویس‌ها / کیس‌استادی / CTA / سایدبار ریل / فوتر** | `template-parts/section-hero.php` · `section-services.php` · `section-cases.php` · `section-cta.php` · `sidebar-rail.php` · `footer-content.php` |
| **استایل/اسکریپت یک سکشن built-in** | `assets/css/sections/<slug>.css` (+`.mobile.css`) · `assets/js/sections/<slug>.js` |
| **متغیرهای `:root`، بک‌گراند، CSS/JS سراسری** | `assets/css/sections/global.root.css` · `global.bg.css` · `global.css` · `assets/js/sections/global.js` |

### ابزارها و سایر
| اگر درخواست دربارهٔ… | برو سراغ |
|---|---|
| **Backlink Spam Checker (Tools → ...)، SSE/polling** | `inc/backlink-checker.php` (خودکفا، بدون هوک فرانت) |
| **404 / جستجو / فرم جستجو / صفحهٔ عمومی** | `404.php` · `search.php` · `searchform.php` · `page.php` · `index.php` |
| **استایل کلی مونولیت (rollback)** | `assets/css/main.css` · `assets/js/main.js` (orphaned) |

---

## ۲) قواعدی که قبل از هر تغییر باید یادم باشد (خلاصهٔ gotchaها)

- سکشن‌های **dynamic** (Products/Blog) PHP خام دارند → override آن‌ها به‌صورت HTML
  استاتیک kses نشود؛ render خودش به فایل fallback می‌کند.
- **هرگز PHP/فایل منبع را minify نکن**؛ فقط خروجی رندرشده.
- **`_empty` marker**: ذخیرهٔ خالی CSS/JS یعنی «هیچی تزریق نکن» (به فایل برنگرد). HTML مستثناست.
- **inline-css mode**: `main.css` دیگر enqueue نمی‌شود؛ rollback با
  `define('LAVTHEME_DISABLE_INLINE_CSS', true)`.
- **lint** فقط با `token_get_all($code, TOKEN_PARSE)` (نه `php -l` — `shell_exec` بسته است).
- **کش hcdn** بعد از هر تغییر purge شود؛ static assetها به `?cb=` گوش نمی‌دهند.
- **مسیر FTP**: ریشهٔ FTP = وب‌روت دامنه؛ تم در `/wp-content/themes/lavtheme/`. هرگز
  `cd public_html/...` نکن (دایرکتوری nested تقلبی).
- **خودم آپلود نکنم** — کاربر با FTP سینک می‌کند. فقط برای تست یک‌باره با اجازه.
- **صفحهٔ اصلی pixel-perfect بماند.**
- قفل‌ها (wp-config): `LAVTHEME_ALLOW_FILE_WRITE` (حالت File)، `LAVTHEME_ALLOW_PHP_SECTIONS` (اجرای تب PHP).

---

## ۳) روند داده (یک نگاه)
ادمین در Code Studio ویرایش می‌کند → AJAX → ذخیره در `wp_options` (یا فایل) → فرانت:
`code-studio-inject.php` (صفحهٔ اصلی) و `code-studio-pages.php` (صفحات) آن options را
می‌خوانند و CSS را در `wp_head`، JS را در `wp_footer`، و مارک‌آپ سکشن را تزریق می‌کنند.

کلیدهای مهم `wp_options`: `lavtheme_cs_registry`, `lavtheme_cs_registry_page_<ID>`,
`lavtheme_cs_<slug>_<type>`, `lavtheme_cs_page_<ID>_<slug>_<type>`,
`lavtheme_cs_dl_template_<slug>_<type>`, `lavtheme_cs_mode`, `lavtheme_cs_minify`,
`lavtheme_cs_schema`.

---
*برای جزئیات کامل: `AI_CONTEXT.md` (راهنمای فشرده) و `AI_CONTEXT2.md` (مرجع کامل).*
