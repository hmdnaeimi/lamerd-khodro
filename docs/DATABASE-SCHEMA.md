# ساختار دیتابیس – لامرد خودرو

پیشوند جداول با `$wpdb->prefix` ساخته می‌شود (پیش‌فرض `wp_`). جداول اختصاصی با پیشوند `lkp_`.

## بخش الف – جداول اختصاصی Price Engine

### `{$prefix}lkp_vehicles` — خودروهای دریافتی از منابع قیمت

| ستون | نوع | توضیح |
|---|---|---|
| `id` | BIGINT UNSIGNED AUTO_INCREMENT PK | شناسه داخلی |
| `source_id` | TINYINT UNSIGNED | FK منطقی به `lkp_price_sources.id` |
| `external_id` | BIGINT UNSIGNED | شناسه خودرو در API (`cars[].id`) |
| `name` | VARCHAR(191) | نام خودرو مطابق منبع |
| `name_normalized` | VARCHAR(191) | کلید نرمال‌شده برای اتصال به CPT (ی/ک فارسی، بدون نیم‌فاصله/فاصله اضافه) |
| `brand_group` | VARCHAR(100) | `group` از API (مثل «ایران خودرو») |
| `post_id` | BIGINT UNSIGNED NULL | FK منطقی به `wp_posts.ID` (خودروی CPT) — قابل NULL |
| `production_stopped` | TINYINT(1) | وضعیت توقف تولید |
| `is_active` | TINYINT(1) DEFAULT 1 | نمایش در فهرست‌ها |
| `created_at` | DATETIME | زمان ایجاد |
| `updated_at` | DATETIME | آخرین تغییر |

کلیدها:
- `UNIQUE KEY uq_source_external (source_id, external_id)` — جلوگیری از رکورد تکراری
- `KEY idx_post_id (post_id)`
- `KEY idx_name_norm (name_normalized)`
- `KEY idx_group (brand_group)`

### `{$prefix}lkp_current_prices` — آخرین قیمت معتبر

| ستون |类型 | توضیح |
|---|---|---|
| `vehicle_id` | BIGINT UNSIGNED PK | FK → `lkp_vehicles.id` (رکورد ۱:۱) |
| `factory_price` | BIGINT UNSIGNED NULL | قیمت کارخانه به تومان؛ **NULL واقعی = اطلاع ثبت نشده** |
| `market_price` | BIGINT UNSIGNED NULL | قیمت بازار به تومان |
| `change_amount` | BIGINT NULL | مبلغ تغییر اعلام‌شده منبع (تومان) |
| `change_percent` | DECIMAL(7,2) NULL | درصد تغییر اعلام‌شده منبع |
| `source_id` | TINYINT UNSIGNED | منبع |
| `fetched_at` | DATETIME | **زمان دریافت** توسط لامرد خودرو |
| `source_updated_at` | DATETIME NULL | **زمان اعلام** توسط منبع (`updated_at` جدول) |
| `is_valid` | TINYINT(1) DEFAULT 1 | صحت داده پس از اعتبارسنجی |

کلیدها: `KEY idx_market (market_price)`, `KEY idx_valid (is_valid)`, `KEY idx_fetched (fetched_at)`.

> نکته: ستون‌های BIGINT ارزش‌های تا ۹.۲e۱۸ تومان را پوشش می‌دهند؛ حداکثر قیمت مشاهده‌شده ~۲۰.۲۵ میلیارد تومان است.

### `{$prefix}lkp_price_history` — تاریخچه تغییرات (فقط از زمان راه‌اندازی)

| ستون | نوع | توضیح |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `vehicle_id` | BIGINT UNSIGNED | FK → `lkp_vehicles.id` |
| `source_id` | TINYINT UNSIGNED | |
| `factory_price` | BIGINT UNSIGNED NULL | قیمت کارخانه در این لحظه |
| `market_price` | BIGINT UNSIGNED NULL | قیمت بازار در این لحظه |
| `factory_change` | BIGINT NULL | تغییر کارخانه نسبت به رکورد قبلی |
| `market_change` | BIGINT NULL | تغییر بازار نسبت به رکورد قبلی |
| `recorded_at` | DATETIME | زمان ثبت در لامرد خودرو |
| `source_updated_at` | DATETIME NULL | زمان اعلام منبع |
| `note` | VARCHAR(191) | علت ثبت (initial/price_change/snapshot) |

کلیدها: `KEY idx_vehicle_time (vehicle_id, recorded_at)`, `KEY idx_recorded (recorded_at)`.

**قاعده ثبت:** فقط هنگام تغییر factory_price یا market_price نسبت به آخرین رکورد معتبر + یک رکورد `initial` در اولین دریافت. عدم تغییر = بدون رکورد. تاریخچه هرگز حذف/بازنویسی نمی‌شود.

### `{$prefix}lkp_price_sources` — منابع قیمت

| ستون | نوع | توضیح |
|---|---|---|
| `id` | TINYINT UNSIGNED PK | |
| `name` | VARCHAR(100) | «کافه خودرو» |
| `api_base` | VARCHAR(200) | آدرس پایه API |
| `attribution_text` | VARCHAR(255) | متن اجباری ذکر منبع |
| `attribution_url` | VARCHAR(200) | لینک منبع |
| `is_active` | TINYINT(1) | |
| `settings` | LONGTEXT | JSON: table_ids، timeout، retry |
| `last_success_at` | DATETIME NULL | آخرین دریافت موفق |
| `last_error` | TEXT NULL | آخرین خطا (بدون اطلاعات حساس) |
| `request_count_1m` | SMALLINT + `window_started_at` | شمارنده داخلی برای رعایت سقف ۱۲۰ درخواست/دقیقه |

### `{$prefix}lkp_sync_logs` — گزارش عملیات همگام‌سازی

| ستون | نوع | توضیح |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `started_at` / `finished_at` | DATETIME | |
| `trigger` | VARCHAR(20) | manual / cron / retry |
| `status` | VARCHAR(20) | success / partial / failed / locked |
| `received` / `created` / `updated` / `price_changed` / `errors` | INT | آمار |
| `duration_ms` | INT | |
| `error_details` | TEXT NULL | خلاصه خطاها (پاک‌سازی‌شده) |

### `{$prefix}lkp_price_snapshots` — وضعیت مقطعی روزانه

| ستون | نوع | توضیح |
|---|---|---|
| `id` | BIGINT UNSIGNED PK | |
| `snapshot_date` | DATE | روز (تقویم میلادی ذخیره؛ نمایش شمسی در UI) |
| `vehicle_id` | BIGINT UNSIGNED | |
| `factory_price` / `market_price` | BIGINT UNSIGNED NULL | |

`UNIQUE KEY uq_day_vehicle (snapshot_date, vehicle_id)` — یک ردیف در روز برای هر خودرو.

### ایندکس‌های wp_options (تنظیمات Price Engine)

`lkp_settings` (array): `sync_interval` (15|30|60|180|1440 دقیقه؛ پیش‌فرض ۶۰)، `table_ids` (پیش‌فرض `[1]`)، `retention_days`، `enable_snapshots`، `lock_ttl`، `report_header`، `export_decimal`…

`lkp_sync_lock` (string): مقدار `{expires_at}`؛ فقط یک اجرای هم‌زمان.

## بخش ب – CPT و Taxonomy (افزونه Core)

### CPT: `vehicles` (خودرو)

- `public: true`, `has_archive: true` (اسلاگ `vehicles`)، `menu_icon: dashicons-car`
- Supports: title, editor, thumbnail, excerpt, comments (بررسی‌ها)
- Rewrite slug: `vehicle` → `/vehicle/{slug}/`
- Meta (`wp_postmeta`) با پیشوند `_lkp_`:

**عمومی:** `_lkp_name_en`, `_lkp_model`, `_lkp_year`, `_lkp_country`, `_lkp_distributor`, `_lkp_body_type` (سدان/هاچ‌بک/کراس‌اور/شاسی‌بلند/وانت/مینی‌ون/کوپه/استیشن)، `_lkp_class` (اقتصادی/میان‌رده/لوکس/…)، `_lkp_production_status` (در تولید/توقف تولید)، `_lkp_market_status` (عرضه فعال/پیش‌فروش/متوقف)، `_lkp_views` (بازدید برای «محبوب»)، `_lkp_price_vehicle_id` (اتصال به `lkp_vehicles.id`).

**موتور:** `_lkp_engine_type`, `_lkp_engine_cc`, `_lkp_cylinders`, `_lkp_valves`, `_lkp_max_power` (اسب‌بخار), `_lkp_max_torque` (نیوتن‌متر), `_lkp_fuel` (بنزین/دوگانه‌سوز/هیبرید/برقی/دیزل), `_lkp_emission` (یورو۴/یورو۵/…)

**گیربکس:** `_lkp_transmission` (دستی/اتومات/CVT/دوزدسته), `_lkp_gears`, `_lkp_drivetrain` (دیفرانسیل جلو/عقب/چهارچرخ)

**عملکرد:** `_lkp_acceleration` (ثانیه), `_lkp_top_speed` (کیلومتر/ساعت), `_lkp_fuel_city`, `_lkp_fuel_highway`, `_lkp_fuel_combined` (لیتر/۱۰۰کیلومتر), `_lkp_tank` (لیتر)

**ابعاد:** `_lkp_length`, `_lkp_width`, `_lkp_height`, `_lkp_wheelbase` (میلی‌متر), `_lkp_weight` (کیلوگرم), `_lkp_trunk` (لیتر), `_lkp_seats`

**امکانات (رشته‌های چندمقداری جداشده با `|`):** `_lkp_safety`, `_lkp_comfort`, `_lkp_brakes`, `_lkp_airbags`, `_lkp_adas`, `_lkp_display`, `_lkp_audio`, `_lkp_camera`, `_lkp_sensors`, `_lkp_interior`

**گالری:** `_lkp_gallery` (JSON آرایه `{attachment_id: role}` با نقش‌های front/rear/side/cabin/dashboard/detail)

### CPT: `listing` (آگهی فروش خودرو)

- `public: true`, `has_archive: 'listings'`، rewrite slug `listing` → `/listing/{slug}/`
- Supports: title, editor, thumbnail, excerpt, revisions — بدون سبد خرید و پرداخت
- تاکسونومی: `brand` (مشترک با `vehicles`)
- Meta با پیشوند `_lkc_listing_`: `price` (تومان)، `year`، `mileage`، `city`، `color`، `seller`، `phone`، `featured` (`1`/`0`)، `featured_until` (`Y-m-d H:i:s` محلی؛ خالی = ویژه بدون تایمر)
- آگهی وقتی «فروش ویژه» است که `featured=1` و `featured_until` خالی یا در آینده باشد؛ جزئیات در [LISTINGS.md](LISTINGS.md).

### Taxonomy: `brand` (برند)

- `hierarchical: false`, rewrite slug `brand`
- ترم متا: `_lkp_brand_name_en`, `_lkp_brand_logo` (attachment id), `_lkp_brand_country`, `_lkp_brand_website`, `_lkp_brand_description`

### دسته‌بندی‌های اخبار (Category – سند بخش ۲۶)

در فعال‌سازی Core ایجاد می‌شوند (فقط ساختار، بدون محتوای ساختگی):
`اخبار خودرو`, `اخبار داخلی`, `اخبار خارجی`, `ایران خودرو`, `سایپا`, `خودروهای وارداتی`, `خودروهای چینی`, `شرایط فروش`, `قیمت خودرو`, `بررسی خودرو`, `فناوری خودرو`, `صنعت خودرو`.

## بخش ج – ایندکس‌گذاری و یکپارچگی

1. تمام FKها در سطح منطق برنامه بررسی می‌شوند (MySQL در MyISAM/utf8mb4 محدودیت FK دارد؛ InnoDB با `FOREIGN KEY` در صورت پشتیبانی فعال می‌شود — کد با `dbDelta` بدون FK سخت ساخته می‌شود تا قابل حمل بماند).
2. جدول‌ها با `dbDelta` (Wpdb) ایجاد/به‌روزرسانی می‌شوند؛ نسخه اسکیما در option `lkp_db_version` نگهداری می‌شود.
3. `charset_collate` از `$wpdb->get_charset_collate()` (utf8mb4).
4. حذف افزونه: جدول‌ها با `DROP TABLE IF EXISTS` فقط پس از تأیید مدیر (صفحه «حذف کامل داده‌ها» در تنظیمات) پاک می‌شوند؛ حذف عادی افزونه داده‌ها را نگه می‌دارد.
