# معماری پروژه – لامرد خودرو

## ۱. نمای کلی اجزا

```text
┌────────────────────────────────────────────────────────┐
│                      WordPress                          │
│                                                        │
│  ┌──────────────────────┐   ┌───────────────────────┐  │
│  │  Theme: lamerd-khodro │   │ Plugins               │  │
│  │  - پیش‌نمایش/قالب‌ها  │   │ ┌───────────────────┐ │  │
│  │  - RTL/Mobile First  │   │ │ lamerd-khodro-core│ │  │
│  │  - بخش‌های صفحه اصلی │◄──┤ │ CPT, Taxonomy,    │ │  │
│  │  - بدون منطق کسب‌وکار│   │ │ Meta, Search,     │ │  │
│  └──────────┬───────────┘   │ │ REST, Settings    │ │  │
│             │ template_tags │ └────────┬──────────┘ │  │
│             ▼               │          │ قرارداد     │  │
│  ┌──────────────────────┐   │ ┌────────▼──────────┐ │  │
│  │ REST: /lamerd/v1/*   │◄──┤ │ Price Engine      │ │  │
│  │ (public read)        │   │ │ Provider→Sync→DB  │ │  │
│  └──────────────────────┘   │ │ Reports/Exports   │ │  │
│                             │ └────────┬──────────┘ │  │
│                             └──────────┴────────────┘  │
│                                        │               │
│                             MySQL: wp_vehicles (CPT)   │
│                                   wp_lkp_* (قیمت)      │
└────────────────────────────────────────────────────────┘
              ▲
              │ GET (فقط درون همگام‌سازی، سمت سرور)
      https://cofekhodro.ir/wp-json/cafekhodro/v1/*
```

## ۲. مرزبندی مسئولیت

| جزء | می‌کند | نمی‌کند |
|---|---|---|
| **قالب** | خروجی HTML، CSS/JS، قالب صفحات، تگ‌های خروجی از سرویس‌ها | ذخیره/دریافت قیمت، منطق اعتبارسنجی، جداول اختصاصی |
| **Core** | CPT `vehicles`، taxonomy `brand`، متا فیلدها، صفحه برند، جستجوی سراسری، تنظیمات صفحه اصلی، REST خودرو/برند/خبر | دریافت قیمت، جدول‌های `lkp_*` |
| **Price Engine** | Provider، همگام‌سازی، جدول‌های `lkp_*`، تاریخچه، نمودار داده، گزارش، خروجی، پنل مدیریت قیمت | HTML صفحات عمومی، CPT خودرو |

## ۳. قرارداد سرویس بین افزونه‌ها

```php
// هر افزونه یک ورودی سراسری دارد؛ طرف مقابل قبل از فراخوانی بررسی می‌کند:
if ( function_exists( 'lamerd_khodro_core' ) ) { ... }
if ( function_exists( 'lamerd_khodro_price_engine' ) { ... }

// Core ارائه می‌دهد:
lamerd_khodro_core()->vehicles()->find_by_api_id( int $external_id ): ?WP_Post
lamerd_khodro_core()->vehicles()->normalize_name( string $name ): string
lamerd_khodro_core()->brands()->get_all(): array

// Price Engine ارائه می‌دهد:
lamerd_khodro_price_engine()->prices()->get_current( int $vehicle_id ): ?array
lamerd_khodro_price_engine()->prices()->get_history( int $vehicle_id, string $range ): array
lamerd_khodro_price_engine()->stats()->summary(): array
```

هیچ `include` مستقیم فایل بین دو افزونه وجود ندارد؛ فقط توابع عمومی با نام‌پیشوند `lamerd_khodro_`.

## ۴. جریان داده قیمت (یک چرخه همگام‌سازی)

```text
WP-Cron (یا دکمه دستی)
   │  قفل: option lkp_sync_lock (ttl 10 دقیقه)
   ▼
SyncService::run()
   │  GET tables (فهرست) ──429/5xx/Timeout──► Retry: 5s, 15s, 45s (حداکثر ۳)
   ▼
CafeKhodroProvider::fetch_table(1)
   │  فشرده‌سازی، timeout=15s، User-Agent اختصاصی
   ▼
Validator::sanitize()  ← حذف قیمت منفی، ابطال percent غیرمنطقی، حفظ NULL
   │
   ├──► upsert lkp_vehicles  (external_id یکتا؛ نام/گروه/وضعیت تولید)
   ├──► مقایسه با lkp_current_prices
   │       ├── تغییر factory_price / market_price ──► INSERT lkp_price_history
   │       └── بدون تغییر ──► بدون رکورد جدید
   ├──► upsert lkp_current_prices (fetched_at = اکنون، source_updated_at = updated_at API)
   └──► UPDATE lkp_price_sources.last_success_at
   │
   ▼
INSERT lkp_sync_logs (آمار + خطا) → آزادسازی قفل
   │
   ▼
صفحات عمومی فقط از MySQL می‌خوانند (کش object-cache + transient)
```

## ۵. معماری داده و اتصال قیمت به خودرو

```text
wp_posts (post_type=vehicles)  ◄──── lkp_vehicles.post_id (ستون اتصال، قابل NULL)
        │                                   │
        │ wp_postmeta (مشخصات فنی)          ├── lkp_current_prices (1:1)
        │                                   ├── lkp_price_history (N:1)
        │                                   └── lkp_price_snapshots (N:1)
taxonomy brand ── wp_term_relationships
```

- خودروی API **بدون** CPT: قیمت ذخیره و نمایش داده می‌شود؛ صفحه جزئیات با داده قیمتی + نام/گروه رندر می‌شود.
- خودروی CPT **بدون** قیمت: صفحه خودرو بدون باکس قیمت (پیام «در انتظار دریافت قیمت»).
- اتصال: تطبیق نرمال‌شده نام + تأیید دستی در «مدیریت قیمت ← خودروها».

## ۶. REST API (نسخه ۱)

Namespace: `lamerd/v1` — عمومی فقط‌خواندنی، با `per_page` (حداکثر ۵۰)، کش ۵ دقیقه، Rate Limit (۶۰ درخواست/دقیقه/IP از طریق شمارنده transient).

| Route | خروجی |
|---|---|
| `GET /vehicles` | فهرست خودروها با فیلتر (brand, body, year, price range) |
| `GET /vehicles/{id}` | مشخصات کامل + آخرین قیمت |
| `GET /vehicles/{id}/history?range=` | تاریخچه قیمت |
| `GET /brands` | برندها |
| `GET /prices` | آخرین قیمت‌ها (مرتب‌سازی/فیلتر) |
| `GET /compare?ids=1,2` | داده مقایسه‌ای |
| `GET /news` | اخبار (wp_query روی post) |

احراز هویت: نسخه عمومی نیاز ندارد؛ مسیرهای خصوصی (آینده) با `permission_callback` جدا.

## ۷. کش و عملکرد

- Object Cache در صورت وجود (Redis/Memcached) + Transient برای خروجی‌های پرتکرار (جدول قیمت ۵ دقیقه، آمار ۱۰ دقیقه).
- `wp_cache` برای نگاشت external_id → post_id.
- فهرست‌گذاری: `lkp_price_history(vehicle_id, recorded_at)`، `lkp_current_prices(market_price)`، `lkp_vehicles(brand_group)`.
- بارگذاری شرطی: Chart.js فقط در صفحه جزئیات قیمت؛ JS فیلتر فقط در صفحات لیست.
- تصاویر: `srcset` + `loading="lazy"` + تولید WebP در صورت وجود `imagick` (fallback به JPEG).

## ۸. آمادگی اپلیکیشن موبایل

- تمام عملیات اصلی از طریق سرویس‌ها/REST در دسترس‌اند (بدون نیاز به scrape صفحات HTML).
- خروجی JSON استاندارد با `X-WP-Total` و صفحه‌بندی.
- نسخه‌بندی namespace (`v1`) اجازه تغییر شکسته‌نده در v2 را می‌دهد.
- نرخ محدود (rate limit) و کش از ابتدا.

## ۹. ساختار پوشه‌ها

```text
lamerd-khodro/                     ریشه مخزن (پروژه)
├── README.md
├── docs/                          مستندات (این فایل‌ها)
├── theme/lamerd-khodro/           قالب وردپرس
│   ├── style.css  functions.php  index.php  front-page.php
│   ├── header.php  footer.php  single.php  archive.php  search.php  404.php  page.php
│   ├── taxonomy-brand.php  single-vehicles.php  (و…)
│   ├── assets/{css,js,fonts,images,icons}
│   ├── inc/{setup,enqueue,helpers,seo,ajax,homepage}.php
│   ├── template-parts/{header,footer,home,vehicle,price,news,components}/
│   ├── page-templates/{page-prices,page-compare,page-vehicles,page-brands}.php
│   └── languages/
└── plugins/
    ├── lamerd-khodro-core/
    │   ├── lamerd-khodro-core.php
    │   ├── includes/{class-plugin,class-post-types,class-taxonomies,class-meta-boxes,
    │   │            class-admin,class-search,class-rest,class-homepage-settings,
    │   │            class-brand-page,class-activation,helpers}.php
    │   └── assets/admin/
    └── lamerd-khodro-price-engine/
        ├── lamerd-khodro-price-engine.php
        ├── includes/{class-plugin,class-installer,class-db,
        │            providers/{interface-provider,class-cafekhodro-provider}.php,
        │            class-validator,class-sync-service,class-scheduler,
        │            class-price-repository,class-chart-data,class-report,
        │            class-export-{csv,xlsx,pdf}.php,
        │            class-rest,class-admin/*}.php
        └── assets/{admin,front}/
```
