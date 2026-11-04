# موتور قیمت خودرو لامرد خودرو (Price Engine)

افزونه مستقل: `plugins/lamerd-khodro-price-engine/` — بدون وابستگی به قالب.

## ۱. وظایف

1. دریافت قیمت از API کافه خودرو (Provider)
2. اعتبارسنجی و نرمال‌سازی
3. ذخیره دائمی در دیتابیس داخلی (`lkp_*`)
4. مدیریت خودروهای منبع و اتصال به CPT
5. ثبت تاریخچه قیمت (فقط تغییرات واقعی)
6. تحلیل تغییرات + داده نمودار
7. گزارش‌گیری (۱۲ گزارش)
8. خروجی CSV / XLSX / PDF
9. مدیریت منابع و لاگ همگام‌سازی

## ۲. Provider Architecture

```text
interface LKP_Provider {
    public function get_source_meta(): array;        // name, url, attribution
    public function list_tables(): array;            // [{id,title,updated_at}]
    public function fetch_table( int $table_id ): LKP_TablePayload;
}
```

- `LKP_CafeKhodroProvider` تنها پیاده‌سازی فعلی.
- خروجی هر Provider به `LKP_TablePayload` (id, title, source_updated_at, unit, cars[]) نرمال می‌شود؛ Validator و Sync فقط این نوع را می‌بینند ← اضافه کردن منبع جدید = یک کلاس جدید + ثبت در `lkp_price_sources`.
- هیچ `echo`/`die`/دسترسی به `$_GET` در Provider نیست؛ فقط HTTP با `wp_remote_get` و پرتاب Exception.

### قواعد HTTP

| وضعیت | اقدام |
|---|---|
| 200 + JSON معتبر | ادامه |
| 429 | Retry با 5s → 15s → 45s (حداکثر ۳)؛ ثبت `rate_limited` |
| 5xx / timeout | همان Retry |
| 4xx دیگر | بدون Retry؛ ثبت فوری `failed` |
| JSON ناقص / `unit` نامعتبر | رد پاسخ، حفظ داده قبلی |

- `timeout: 15` ثانیه، `decompress: true`، `User-Agent: LamerdKhodroPriceEngine/1.0 (+https://lamerdkhodro.ir)`.

## ۳. سیستم همگام‌سازی

### ماژول Scheduler

- Hook: `lkp_price_sync` از نوع `WP-Cron`؛ زمان‌بندی با `wp_schedule_event` بر اساس `sync_interval` (۱۵/۳۰/۶۰/۱۸۰/۱۴۴۰ دقیقه؛ **پیش‌فرض ۶۰**).
- در صفحه تنظیمات، دستور Cron واقعی هاست نمایش داده می‌شود:
  `*/30 * * * * cd /path/to/wordpress && wp cron event run --due-now >/dev/null 2>&1` یا معادل `php wp-cron.php`.
- در فعال‌سازی، اگر رویداد موجود نباشد ساخته می‌شود؛ تغییر فاصله = لغو رویداد قبلی + برنامه‌ریزی جدید.

### قفل هم‌زمانی

- `get_option('lkp_sync_lock')` شامل `expires_at` (now + 10 دقیقه).
- اگر قفل معتبر بود: اجرا با وضعیت `locked` ثبت (یا خاموش) و خروج → هرگز دو اجرا هم‌زمان.
- آزادسازی در `finally` (even on fatal caught exceptions).

### جریان `SyncService::run(string $trigger)`

```text
1. اخذ قفل
2. دریافت فهرست جدول‌ها (1 درخواست)  ← در صورت خطا: جدول‌های settings
   برای هر table_id (پیش‌فرض فقط ۱):
3.   fetch_table()
4.   Validator::validate_payload()      ← unit، count، فیلدها، مرزها
5.   برای هر car:
       a. upsert lkp_vehicles (external_id یکتا)
       b. خواندن lkp_current_prices فعلی
       c. مقایسه factory/market با مقادیر جدید (با رعایت NULL)
       d. در صورت تغییر → INSERT history با factory_change/market_change
       e. upsert lkp_current_prices (fetched_at=now, source_updated_at=payload)
6.   ثبت موفقیت منبع (last_success_at)
7. snapshot روزانه (اگر enabled و snapshot امروز نیست)
8. INSERT lkp_sync_logs با آمار
9. آزادسازی قفل + پاک‌سازی transient های نمایش (lkp_prices_*)
```

### آمار ثبت‌شده در هر اجرا

`received` (تعداد cars)، `created` (خودروی جدید)، `updated` (قیمت تغییرکرده)، `price_changed` (رویداد تغییر)، `errors` (شامل رد شدن اعتبارسنجی)، `duration_ms`.

## ۴. اصول ثبت تاریخچه (الزامی)

1. فقط تغییر **واقعی** ثبت می‌شود؛ `null → null` یا تغییر نکردن = بدون رکورد.
2. تغییر کارخانه و بازار در **دو ستون جداگانه** (`factory_change` / `market_change`).
3. `null` هرگز به `0` تبدیل نمی‌شود؛ مقایسه `null` با عدد = تغییر از ناموجود به مقدار (ثبت با توضیح).
4. اولین دریافت هر خودرو → رکورد `note='initial'` (مبنای نمودار).
5. رکورد تاریخی حذف یا UPDATE نمی‌شود؛ فقط INSERT.
6. «زمان دریافت» (`recorded_at` / `fetched_at`) با «زمان اعلام منبع» (`source_updated_at`) جداست و هر دو ذخیره می‌شود.
7. API تاریخچه ندارد → **هیچ داده ساختگی برای قبل از راه‌اندازی تولید نمی‌شود.** نمودار از نقطه فعال شدن شروع می‌شود و پیام «داده از تاریخ …» نمایش داده می‌شود.

## ۵. استقلال از API

- تمام صفحات عمومی فقط `SELECT` از `lkp_current_prices`/`lkp_price_history` می‌زنند.
- هنگام قطع API: آخرین قیمت معتبر باقی می‌ماند؛ `fetched_at` قدیمی → UI برچسب «اطلاعات قدیمی (آخرین دریافت: …)» نشان می‌دهد.
- داشبورد مدیریتی وضعیت API را از `last_success_at` و آخرین لاگ استخراج می‌کند (success/partial/failed/never).
- هیچ `wp_remote_get` در قالب یا مسیر عمومی وجود ندارد (کنترل با جستجوی کد).

## ۶. پنل مدیریت

منوی اصلی: **مدیریت قیمت خودرو** (`manage_lkp_options` capability؛ پیش‌فرض فقط مدیر کل).

| زیرمنو | محتوا (همه واقعی و متصل به DB) |
|---|---|
| داشبورد | تعداد خودروها/قیمت‌های معتبر، آخرین دریافت موفق، وضعیت API، تغییرات امروز، خودروهای جدید، خطاهای اخیر، نمودار خلاصه ۳۰ روز |
| خودروها | فهرست `lkp_vehicles` + جستجو + اتصال/قطع اتصال به CPT (تطبیق نرمال‌شده به‌صورت پیشنهادی) |
| آخرین قیمت‌ها | جدول کامل + مرتب‌سازی + صفحه‌بندی |
| تاریخچه قیمت | فیلتر خودرو/بازه/نوع تغییر |
| گزارش‌ها | ۱۲ گزارش + بازه دلخواه + فیلترها + دکمه خروجی |
| منابع | CRUD منابع + آخرین وضعیت + دکمه «دریافت دستی» |
| همگام‌سازی | اجرای دستی، جدول لاگ‌ها، وضعیت قفل، اطلاعات Cron |
| تنظیمات | فاصله، جدول‌ها، snapshot، نگه‌داشت، اعتبارسنجی، حذف کامل داده‌ها (با تأیید) |

## ۷. گزارش‌ها

۱۲ گزارش با پارامتر `range` (today/yesterday/7d/30d/90d/365d/custom) و فیلترهای brand / vehicle / source / price_type:

1. تغییرات روزانه 2. هفتگی 3. ماهانه 4. سالانه 5. بیشترین افزایش 6. بیشترین کاهش 7. میانگین تغییر 8. اختلاف بازار/کارخانه 9. مقایسه برندها 10. بدون تغییر 11. عملکرد منابع 12. دریافت موفق/ناموفق (از `lkp_sync_logs`).

داده همه از `lkp_price_history` + `lkp_sync_logs` محاسبه می‌شود (SQL آگرگیت با ایندکس `idx_vehicle_time`).

## ۸. خروجی‌ها

| فرمت | پیاده‌سازی | نکات |
|---|---|---|
| CSV | `LKP_Export_CSV` | UTF-8 BOM برای اکسل فارسی؛ جداساز کاما؛ اعداد **بدون متن** (کامای هزارگان) |
| XLSX | `LKP_Export_XLSX` (ZIP + SpreadsheetML ساده، بدون وابستگی خارجی) | سلول عددی با `t="n"`؛ ستون‌های استاندارد؛ هدر فارسی؛ راست‌چین |
| PDF | `LKP_Export_PDF` (HTML قالب‌دار + ارسال به مرورگر با `Content-Type: application/pdf` از طریق چاپگر PDF / یا خروجی HTML چاپ‌پذیر RTL) | سربرگ برند، فیلترهای اعمال‌شده، تاریخ شمسی، راست‌چین |

همه خروجی‌ها فیلترهای انتخابی مدیر را لحاظ می‌کنند و از همان کوئری گزارش استفاده می‌کنند (یک منبع حقیقت).

## ۹. امنیت (نقاط خاص)

- همه تنظیمات با `sanitize_text_field` / `absint` / `wp_kses` (لیست مجاز).
- دکمه‌های AJAX با `check_ajax_referer('lkp_admin')` + `current_user_can('manage_lkp_options')`.
- خروجی‌ها فقط برای کاربر مجاز؛ فایل‌های موقت در `wp-content/uploads/lkp-export/` با نام تصادفی و پاک‌سازی پس از دانلود.
- لاگ خطاها بدون URL حاوی احتمالاً credential و بدون هدر حساس.
