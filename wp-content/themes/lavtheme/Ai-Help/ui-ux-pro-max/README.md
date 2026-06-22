# 🧠 UI/UX Pro Max — مرجع آفلاین (داخل پروژه)

این پوشه یک **کپی کامل و همیشه‌در‌دسترس** از دیتای اسکیل `ui-ux-pro-max@ui-ux-pro-max-skill` (نسخه `2.5.0`) است.
هدف: حتی اگر پلاگین در یک session فعال نبود، همه‌ی دانش طراحی همین‌جا به‌صورت فایل MD/CSV در دسترس باشد.

> **منبع اصلی:** `nextlevelbuilder/ui-ux-pro-max-skill` — commit `fb1fc58`
> **شامل:** ۵۰+ استایل · ۱۶۱ پالت رنگ · ۷۳ جفت‌فونت · ۱۶۱ نوع محصول · ۹۹ گایدلاین UX · ۲۵ نوع نمودار · ۱۶ استک

---

## 📂 ساختار

```
ui-ux-pro-max/
├── README.md            ← همین فایل (فهرست + راهنما)
├── docs/                ← اسناد curated (خواندنی)
│   ├── SKILL.md             راهنمای اصلی اسکیل + دستورات CLI
│   ├── quick-reference.md   چیت‌شیت ۱۰ دسته قانون UX (۱→۱۰ اولویت)
│   └── skill-content.md     محتوای تکمیلی اسکیل
├── catalogs/            ← جدول‌های MD خوانا (خلاصه‌ی ستون‌های مهم)
│   ├── styles.md            🎨 ۸۴ استایل بصری
│   ├── colors.md            🌈 ۱۶۱ پالت رنگ (توکن semantic)
│   ├── typography.md        🔤 ۷۳ جفت‌فونت
│   ├── products.md          📦 ۱۶۱ نوع محصول + پیشنهاد طراحی
│   ├── ux-guidelines.md     ✅ ۹۹ قانون UX (Do/Don't + شدت)
│   ├── charts.md            📊 ۲۵ نوع نمودار
│   ├── landing.md           🚀 ۳۴ الگوی لندینگ
│   ├── app-interface.md     📱 ۳۰ راهنمای رابط اپ موبایل
│   └── icons.md             🔣 ۱۰۵ آیکون
├── data/                ← دیتابیس کامل CSV (منبع حقیقت، همه‌ی ستون‌ها)
│   ├── *.csv                ۲۰ فایل اصلی (styles, colors, design, google-fonts ...)
│   └── stacks/*.csv         ۱۶ استک (react, nextjs, vue, svelte, swiftui, flutter ...)
└── scripts/             ← CLI سرچ پایتون (search.py, core.py, design_system.py)
```

> **چرا هم MD و هم CSV؟** فایل‌های `catalogs/*.md` برای مرور سریع و خواناییِ انسان ساخته شده‌اند (فقط ستون‌های مهم).
> فایل‌های `data/*.csv` همه‌ی ستون‌ها را دارند (منبع کامل) و ورودیِ CLI سرچ هستند.

---

## 🚀 نحوه‌ی استفاده

### روش ۱ — مرور دستی (سریع‌ترین)
مستقیم فایل MD مرتبط را باز کن:
- می‌خوای استایل انتخاب کنی؟ → [`catalogs/styles.md`](catalogs/styles.md)
- پالت رنگ برای نوع محصول؟ → [`catalogs/colors.md`](catalogs/colors.md)
- جفت‌فونت؟ → [`catalogs/typography.md`](catalogs/typography.md)
- قانون UX / چک‌لیست کیفیت؟ → [`catalogs/ux-guidelines.md`](catalogs/ux-guidelines.md) + [`docs/quick-reference.md`](docs/quick-reference.md)

### روش ۲ — CLI سرچ (دقیق، نیازمند Python)
از داخل همین پوشه:

```bash
# دیزاین‌سیستم کامل برای یک محصول (همیشه از این شروع کن):
python scripts/search.py "<product_type> <industry> <keywords>" --design-system -p "Project Name"

# سرچ در یک دامنه‌ی خاص:
python scripts/search.py "<keyword>" --domain <domain> -n 5

# راهنمای یک استک:
python scripts/search.py "<keyword>" --stack react
```

> روی ویندوز `python` (نه `python3`) استفاده کن.

**دامنه‌های موجود (`--domain`):**
`style` · `color` · `typography` · `product` · `ux` · `chart` · `landing` · `google-fonts` · `react` · `web` · `prompt`

**استک‌های موجود (`--stack`):**
`react` · `nextjs` · `vue` · `nuxtjs` · `nuxt-ui` · `svelte` · `angular` · `astro` · `react-native` · `swiftui` · `jetpack-compose` · `flutter` · `html-tailwind` · `shadcn` · `threejs` · `laravel`

---

## 🎯 جریان کار پیشنهادی (Workflow)

1. **تحلیل نیاز** — نوع محصول، صنعت، حال‌و‌هوای موردنظر را مشخص کن.
2. **تولید دیزاین‌سیستم** — از [`catalogs/products.md`](catalogs/products.md) نوع محصول را پیدا کن → استایل + پالت + الگوی لندینگ پیشنهادی را بگیر.
3. **انتخاب جزئیات** — استایل از [`styles.md`](catalogs/styles.md)، رنگ از [`colors.md`](catalogs/colors.md)، فونت از [`typography.md`](catalogs/typography.md).
4. **کنترل کیفیت** — قبل از تحویل، با [`ux-guidelines.md`](catalogs/ux-guidelines.md) و چیت‌شیت [`quick-reference.md`](docs/quick-reference.md) (اولویت ۱→۱۰) چک کن.

---

## 🔟 خلاصه‌ی اولویت قوانین UX (از quick-reference)

| اولویت | دسته | اهمیت |
|---|---|---|
| 1 | Accessibility | 🔴 CRITICAL |
| 2 | Touch & Interaction | 🔴 CRITICAL |
| 3 | Performance | 🟠 HIGH |
| 4 | Style Selection | 🟠 HIGH |
| 5 | Layout & Responsive | 🟠 HIGH |
| 6 | Typography & Color | 🟡 MEDIUM |
| 7 | Animation | 🟡 MEDIUM |
| 8 | Forms & Feedback | 🟡 MEDIUM |
| 9 | Navigation Patterns | 🟠 HIGH |
| 10 | Charts & Data | 🟢 LOW |

---

## 🔄 به‌روزرسانی

این یک snapshot از نسخه `2.5.0` است. برای گرفتن آخرین نسخه، پلاگین را از طریق `/plugin` آپدیت کن، سپس
فایل‌های `data/`, `docs/`, و `catalogs/` را دوباره از مسیر cache پلاگین بازتولید کن.

مسیر cache پلاگین:
`~/.claude/plugins/cache/ui-ux-pro-max-skill/ui-ux-pro-max/<version>/`
