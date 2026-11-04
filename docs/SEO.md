# سئو – لامرد خودرو

## ۱. URL و ساختار

- پیشوند یکتا: `/vehicle/{slug}/`، `/brand/{slug}/`، `/prices/`، `/price/{id}/`، `/compare/`
- حذف نویسهای فارسی از slug با `sanitize_title` (وردپرس) + `iconv`/transliteration.
- صفحات فیلتر (`?brand=...&min_price=...`): `rel="canonical"` به صفحه پاک + `noindex,follow` (از طریق `wp_robots`).
- صفحات تکراری pagination: `rel=prev/next` + canonical به صفحه ۱.

## ۲. Meta و Schema

- **Title:** الگو `{صفحه} | لامرد خودرو` با سلسله‌مراتب کوتاه؛ صفحه خودرو: `{نام} – مشخصات فنی و قیمت | لامرد خودرو`.
- **Description:** از متا/خلاصه با محدودیت ~۱۵۵ نویسه؛ صفحه قیمت شامل «قیمت روز … به‌روزرسانی {تاریخ شمسی}».
- **Canonical** در همه صفحات.
- **Open Graph + Twitter Card** (تصویر شاخص/محصول).
- **Schema:**
  - `Organization` (لوگو، دامنه، شبکه اجتماعی) در همه صفحات.
  - `Article`/`NewsArticle` در مقالات.
  - `BreadcrumbList` در همه صفحات داخلی.
  - `Vehicle`/`Product` فقط **در صورت وجود قیمت واقعی و مشخصات کامل** (درغیر این صورت `Product` حذف می‌شود – قاعده «انطباق واقعی»).
  - `FAQ` فقط اگر محتوای FAQ واقعی وجود داشته باشد.

## ۳. سازگاری با Rank Math / Yoast

- اگر `defined('WPSEO_VERSION')` یا `class_exists('RankMath')` باشد: خروجی‌های تکراری قالب (`seo.php`) خاموش می‌شوند؛ قالب فقط Breadcrumb/Schema اختصاصی خود را در صورت نبود افزونه سئو تولید می‌کند.
- `wpseo_title`/`rank_math_title` با فیلترهای افزونه قابل override است.

## ۴. محتوا و لینک‌سازی

- هر صفحه خودرو به برند، خودروهای مشابه، قیمت و مقالات مرتبط لینک می‌شود.
- مقالات: «مطالب مرتبط» بر اساس دسته/برچسب (محدودیت ۴).
- `alt` الزامی برای تصاویر خودرو (نام خودرو + برند)؛ تصاویر لوگیتایپ `aria-hidden`.
- Heading: تنها یک `h1` در هر صفحه؛ سلسله‌مراتب بدون پرش.

## ۵. سرعت (ارتباط با سئو)

- CSS تک‌فایل اصلی + کدگذاری شرطی صفحه‌ای؛ JS غیرفعال در صفحات بدون ابزار.
- `loading="lazy"` + `decoding="async"`؛ `fetchpriority="high"` برای تصویر Hero.
- Object cache/Transient برای پرس‌وجوهای پرتکرار؛ حذف queryهای N+1 با `IN` و cache term.
- حذف `emoji-embed`/`oEmbed` غیرضروری؛ `wp_resource_hints` برای دامنه خودی فقط.

## ۶. XML Sitemap

- Sitemap هسته وردپرس فعال می‌ماند؛ CPT `vehicles` با `public=true` و taxonomy `brand` به آن افزوده می‌شوند.
- `post_type_link` استاندارد؛ noindex صفحات فیلتر در sitemap لحاظ می‌شود.

## ۷. محتوای تکراری

- آرشیو دسته‌های اخبار با canonical خودشان.
- صفحه «قیمت روز» تنها یک نسخه دارد (page template)؛ بخش صفحه اصلی به آن لینک می‌شود (بدون محتوای تکراری جدول).
