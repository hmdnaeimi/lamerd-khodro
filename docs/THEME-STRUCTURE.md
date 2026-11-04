# ساختار قالب اختصاصی لامرد خودرو

مسیر: `theme/lamerd-khodro/` — قالب کاملاً اختصاصی، بدون صفحه‌ساز، بدون وابستگی به قالب آماده.

## ۱. فایل‌های ریشه

| فایل | نقش |
|---|---|
| `style.css` | هدر استاندارد قالب + ارجاع به `assets/css/main.css` |
| `functions.php` | فقط require فایل‌های `inc/` (بدون منطق) |
| `index.php` | fallback آرشیو |
| `front-page.php` | صفحه اصلی با بخش‌های قابل مدیریت |
| `header.php` / `footer.php` | خروجی `header.php`/`footer.php` واقعی + hook |
| `single.php` | مقاله/خبر (خوانایی بالا، مطالب مرتبط) |
| `archive.php` / `search.php` / `404.php` / `page.php` | استاندارد |
| `single-vehicles.php` | صفحه خودرو: مشخصات + باکس قیمت + نمودار + گالری + مشابه‌ها |
| `taxonomy-brand.php` | صفحه برند |
| `taxonomy-brand.php` و `archive-vehicles.php` | آرشیو بانک خودرو (فیلترها) |
| `single-listing.php` | صفحه آگهی فروش: تصویر، قیمت، مشخصات، دکمه تماس، تایمر فروش ویژه |
| `archive-listing.php` | آرشیو آگهی‌های فروش با شبکه کارت |

## ۲. `page-templates/`

| فایل | Template Name | صفحه |
|---|---|---|
| `page-prices.php` | «قیمت روز خودرو» | جدول/کارت قیمت + آمار + فیلتر |
| `page-compare.php` | «مقایسه خودرو» | مقایسه ۲–۴ خودرو |
| `page-vehicles.php` | «بانک اطلاعات خودرو» | لیست با فیلتر ترکیبی |
| `page-price-detail.php` | «جزئیات قیمت» | قالب رندر `/price/{id}/` (rewrite در Price Engine) |

## ۳. `inc/`

| فایل | نقش |
|---|---|
| `setup.php` | `add_theme_support` (title-tag, post-thumbnails, html5, responsive-embeds, align-wide)، register_nav_menus (primary, footer-*, mobile)، register image sizes |
| `enqueue.php` | بارگذاری CSS/JS با **شرط**: فقط فونت+main.css همیشه؛ `prices.js` در صفحه قیمت؛ `compare.js` در مقایسه؛ `chart.min.js` فقط در جزئیات قیمت/خودرو؛ حذف jQuery اضافه در صفحات عمومی |
| `helpers.php` | `lkp_fa_number()`، `lkp_price_format()` (تومان + جداکننده)، `lkp_fa_date()` (تقویم شمسی)، `lkp_change_badge()`، `lkp_rel_time()`، `lkp_breadcrumb()` |
| `seo.php` | Meta Title/Description، Canonical، OG/Twitter، BreadcrumbList + Organization + Article + Vehicle/Product (فقط در صورت وجود قیمت واقعی) Schema؛ غیرفعال‌سازی خروجی تکراری هنگام فعال بودن Rank Math/Yoast |
| `ajax.php` | Endpoint های جستجوی سراسری و لیست قیمت (فقط READ از دیتابیس داخلی) |
| `homepage.php` | ثبت Section Settings (گزینه‌های صفحه اصلی در پیشخوان Core) |

## ۴. `template-parts/`

```text
template-parts/
├── header/
│   ├── site-header.php        لوگو + منو + جستجو + CTA
│   ├── mobile-nav.php         کشویی موبایل (دکمه لمسی 44px)
│   └── breadcrumbs.php
├── footer/
│   ├── site-footer.php        ۴ ستون: معرفی/لینک‌ها/برندها/آخرین اخبار + شبکه اجتماعی + تماس
│   └── social-icons.php
├── home/
│   ├── hero.php               تصویر + عنوان + جستجو + دسترسی سریع
│   ├── price-strip.php        قیمت روز (۶ خودرو)
│   ├── brands.php             لوگوی برندها
│   ├── new-cars.php           جدیدترین خودروها
│   ├── popular-cars.php       پربازدید
│   ├── news.php               آخرین اخبار
│   ├── reviews.php            بررسی‌ها (دسته بررسی خودرو)
│   ├── compare-cta.php        CTA مقایسه
│   └── sales.php              شرایط فروش (دسته شرایط فروش)
├── vehicle/
│   ├── card.php               کارت خودرو (لیست‌ها)
│   ├── spec-group.php         گروه مشخصات (جدول توضیحی)
│   ├── gallery.php            گالری با نقش تصاویر
│   └── price-box.php          باکس قیمت (از Price Engine)
├── listing/
│   └── card-listing.php       کارت آگهی فروش (قیمت، مشخصات، نشان و تایمر فروش ویژه)
├── price/
│   ├── stats-cards.php        ۴ کارت آماری
│   ├── table-desktop.php      جدول دسکتاپ
│   ├── cards-mobile.php       کارت موبایل (نام+بازار+تغییر اول؛ جزئیات بازشونده)
│   ├── filters.php            جستجو/فیلتر/مرتب‌سازی
│   └── chart.php              ظرف Canvas نمودار + کنترل بازه
├── news/
│   ├── card.php               کارت خبر (تصویر + دسته + تاریخ شمسی)
│   ├── list.php
│   └── related.php            مطالب مرتبط
└── components/
    ├── section-title.php      عنوان بخش + لینک «مشاهده همه»
    ├── badge-change.php       نشان افزایش/کاهش/بدون تغییر
    ├── pagination.php
    ├── empty-state.php        پیام خالی (استاندارد)
    └── attribution.php        ذکر منبع کافه خودرو (الزامی)
```

## ۵. Assets

```text
assets/
├── css/
│   ├── main.css               طراحی سیستم: متغیرها، layout، کامپوننت‌ها (RTL، Mobile First)
│   ├── pages.css              صفحات خاص (قیمت، مقایسه، خودرو)
│   └── print.css              چاپ/خروجی
├── js/
│   ├── main.js                منوی موبایل، sticky header، جستجوی هدر
│   ├── search.js              جستجوی سراسری AJAX با نرمال‌سازی فارسی
│   ├── prices.js              فیلتر/مرتب‌سازی/صفحه‌بندی/کارت موبایل
│   ├── compare.js             انتخاب خودرو و رندر جدول مقایسه
│   └── price-chart.js         پیکربندی Chart.js (بازه‌ها، Tooltip فارسی، شمسی)
├── fonts/                     Vazirmatn – WOFF2 محلی (400,500,600,700,800) + @font-face در main.css
├── images/                    لوگو، placeholder خودرو، الگوهای پس‌زمینه
└── icons/                     SVG sprites
```

### کتابخانه‌های Vendor (Local، بدون CDN)

- `assets/js/vendor/chart.umd.min.js` — Chart.js (فقط صفحات نمودار)
- فونت وزیرمتن: فایل‌های WOFF2 رسمی در `assets/fonts/`

> هیچ فراخوانی به Google Fonts / CDN خارجی در قالب نیست.

## ۶. قرارداد خروجی از افزونه‌ها

قالب فقط از توابع زیر استفاده می‌کند (با `function_exists`):

```php
// از Price Engine:
lamerd_khodro_price_engine()->prices()->get_current( $vehicle_id )
lamerd_khodro_price_engine()->prices()->get_history( $vehicle_id, $range )
lamerd_khodro_price_engine()->stats()->summary()
lamerd_khodro_price_engine()->charts()->range_points( $vehicle_id, $range )

// از Core:
lamerd_khodro_core()->vehicles()->get_price_vehicle_id( $post_id )
lamerd_khodro_core()->brands()->logo_url( $term_id )
```

اگر افزونه‌ای فعال نباشد، قالب با گارد `function_exists` بخش مربوطه را با پیام «در دسترس نیست» یا بدون آن بخش رندر می‌کند (بدون خطای PHP).

## ۷. منوها

- `primary` — منوی اصلی هدر
- `mobile` — منوی اختصاصی موبایل (در صورت خالی بودن از primary کپی می‌شود)
- `footer-1` … `footer-4` — ستون‌های فوتر
