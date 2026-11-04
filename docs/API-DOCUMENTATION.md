# مستندات API

## بخش ۱ – API منبع: کافه خودرو (بررسی‌شده در تاریخ ۱۴۰۵/۰۸/۱۱)

> تمام ساختارهای زیر از پاسخ واقعی endpointها استخراج شده‌اند؛ هیچ فیلدی فرضی نیست.

### شرایط استفاده

- بدون API Key (فقط‌خواندنی، عمومی).
- سقف **۱۲۰ درخواست در دقیقه برای هر IP**؛ بیش از آن → HTTP 429.
- **الزام ذکر منبع:** متن «منبع: کافه خودرو — https://cofekhodro.ir» باید در صفحات نمایش‌دهنده قیمت درج شود.
- تاریخچه قیمت ارائه نمی‌شود.
- قیمت‌ها صرفاً اطلاع‌رسانی‌اند.

### Endpoint ۱: فهرست جدول‌ها

`GET https://cofekhodro.ir/wp-json/cafekhodro/v1/tables`

پاسخ واقعی:

```json
{
  "source": {
    "name": "کافه خودرو",
    "url": "https://cofekhodro.ir",
    "attribution": "منبع: کافه خودرو — https://cofekhodro.ir"
  },
  "unit": "toman",
  "tables": [
    {
      "id": 1,
      "title": "لیست قیمت های خودرو داخلی",
      "updated_at": "2026-11-02T09:10:28+00:00",
      "url": "https://cofekhodro.ir/wp-json/cafekhodro/v1/tables/1"
    }
  ]
}
```

### Endpoint ۲: جدول قیمت

`GET https://cofekhodro.ir/wp-json/cafekhodro/v1/tables/1`

پاسخ واقعی (ساختار + نمونه):

```json
{
  "id": 1,
  "title": "لیست قیمت های خودرو داخلی",
  "unit": "toman",
  "updated_at": "2026-11-02T09:10:28+00:00",
  "count": 115,
  "cars": [
    {
      "id": 12,
      "group": "ایران خودرو",
      "name": "207 تیوتری",
      "factory_price": 1502231900,
      "market_price": 2663000000,
      "production_stopped": false,
      "change": { "amount": -107000000, "percent": -3.86 }
    },
    {
      "id": 39,
      "group": "سایپا",
      "name": "کوییک GXR رینگ",
      "factory_price": 826000000,
      "market_price": 1893000000,
      "production_stopped": false,
      "change": null
    }
  ],
  "source": { "name": "کافه خودرو", "url": "https://cofekhodro.ir", "attribution": "…" }
}
```

### نقشه فیلدها (نرمال‌سازی شده برای ذخیره)

| فیلد API | نوع واقعی | قانون اعتبارسنجی ذخیره |
|---|---|---|
| `id` | int (≥۱) | کلید یکتا در کنار `source_id`؛ الزامی |
| `group` | string (فارسی) | ≤۱۰۰ نویسه؛ نگهداری متن اصلی (مثلاً «سایر خودروها») |
| `name` | string (فارسی+انگلیسی+رقم) | ≤۱۹۱؛ تولید `name_normalized` |
| `factory_price` | int \| null | `null` = ناموجود؛ صفر/منفی → null + ثبت هشدار؛ حداکثر مرز عقلانی ۹۹۹,۹۹۹,۹۹۹,۹۹۹ |
| `market_price` | int \| null | مانند بالا |
| `production_stopped` | bool | bool |
| `change` | object \| null | `amount`: int؛ `percent`: float؛ **دامنه منطقی percent بین -۱۰۰ تا +۱۰۰** (در داده واقعی مقادیر `-99.9` با `amount` نامعقول دیده می‌شود → percent ذخیره می‌شود ولی در UI با برچسب «داده نامعتبر منبع» نمایش داده می‌شود اگر با اختلاف قیمت فاصله غیرمنطقی داشته باشد) |
| `unit` | `"toman"` | فقط تومان پذیرفته می‌شود؛ هر مقدار دیگر = خطای schema |
| `updated_at` | ISO8601 با offset | تبدیل به UTC و ذخیره در `source_updated_at` |
| `count` | int | تطبیق با `count(cars)` برای تشخیص پاسخ ناقص |

### مشاهدات مهم از داده واقعی (پایه اعتبارسنجی)

1. `factory_price` یا `market_price` می‌توانند هر دو `null` باشند (مثلاً تویوتا رافور).
2. `change` می‌تواند `null` باشد حتی وقتی قیمت موجود است (یعنی داده قبلی ندارد).
3. `change.amount = 0, percent = 0` رایج است = «بدون تغییر»، نه ناموجود.
4. مقادیر پرت دیده شده: `percent: -100` همراه `market_price: null` (کاهش به ناموجود)، و `amount: -10489600000000, percent: -99.9` (ناسازگار با قیمت‌ها) → قواعد اعتبارسنجی درست همین‌ها را مهار می‌کند.
5. `name`ها تکراری/شبیه نسخه‌ای دارند («شاهین سانروف» / «شاهین بدون سانروف») → اتصال به CPT باید سطح‌به‌سطح و قابل تأیید دستی باشد.
6. نام‌هایی با رقم فارسی و انگلیسی مخلوط («هایما (7X) مشکی ۴۰۴») → نرمال‌سازی یکسان‌سازی ارقام برای تطبیق.

### مدیریت نرخ درخواست (Rate Limiting)

- سقف عملی: **حداکثر ۲ درخواست به ازای هر اجرا** (`tables` + `tables/1`)؛ با بازه پیش‌فرض ۶۰ دقیقه‌ای، بار سرور منبع حدود ۲/۶۰ درخواست در دقیقه است → بسیار زیر سقف ۱۲۰.
- با این حال شمارنده `request_count_1m` در `lkp_price_sources` نگهداری می‌شود و قبل از هر درخواست بررسی می‌شود: اگر ≥۱۰۰ در یک دقیقه بود، اجرا با تأخیر زمان‌بندی یا با وضعیت `rate_limited` ثبت می‌شود.
- پاسخ 429 → تأخیر افزایشی (5s → 15s → 45s)، حداکثر ۳ تلاش؛ سپس ثبت `failed` در لاگ با حفظ داده قبلی.

## بخش ۲ – REST API داخلی لامرد خودرو (`lamerd/v1`)

عمومی، فقط‌خواندنی، JSON. همه مسیرها: `permission_callback: __return_true`، کش ۵ دقیقه، Rate Limit داخلی ۶۰ درخواست/دقیقه/IP (شمارنده transient + پاسخ 429 با `Retry-After`).

| مسیر | پارامترها | خلاصه خروجی |
|---|---|---|
| `GET /prices` | `page, per_page(≤50), search, brand, sort(price_market_asc/desc/change_desc/name), min_price, max_price, status` | `{data:[{vehicle_id, name, brand_group, factory_price, market_price, change_amount, change_percent, production_stopped, fetched_at, permalink}], total, pages}` |
| `GET /prices/stats` | – | `{valid, up, down, unchanged, last_sync}` |
| `GET /prices/{vehicle_id}` | – | آخرین قیمت + اطلاعات خودروی متصل |
| `GET /prices/{vehicle_id}/history` | `range: 7d,1m,3m,6m,1y,all` | `{points:[{date, factory_price, market_price}], count}` |
| `GET /vehicles` | `page, per_page, brand, body, year, min_price, max_price, search` | فهرست CPT خودروها + آخرین قیمت متصل |
| `GET /vehicles/{id}` | – | مشخصات کامل متا + قیمت |
| `GET /brands` | – | برندها با تعداد خودرو |
| `GET /compare` | `ids=3,7,12` (2..4) | آرایه مشخصات برای جدول مقایسه |
| `GET /news` | `page, per_page, category` | اخبار (فیلد استاندارد وردپرس) |
| `GET /search` | `q` (نرمال‌شده) | نتایج ترکیبی: خودروها/برندها/اخبار |

نمونه `GET /prices?per_page=2`:

```json
{
  "data": [
    {
      "vehicle_id": 12,
      "name": "207 تیوتری",
      "brand_group": "ایران خودرو",
      "factory_price": 1502231900,
      "market_price": 2663000000,
      "change_amount": -107000000,
      "change_percent": -3.86,
      "production_stopped": false,
      "fetched_at": "2026-11-02T09:10:28+00:00",
      "permalink": "https://lamerdkhodro.ir/price/12/"
    }
  ],
  "total": 115,
  "pages": 58
}
```

### مستندات داخلی برای اپلیکیشن آینده

- همه تاریخ‌ها ISO8601 UTC؛ رابط کاربری شمسی‌سازی می‌کند.
- اعداد صحیح تومان؛ `null` یعنی ناموجود (هرگز `0` جایگزین نشود).
- صفحه‌بندی با هدرهای `X-WP-Total` / `X-WP-TotalPages`.
- احراز هویت آینده: `permission_callback` اختصاصی + Application Passwords / JWT (مستند در فاز توسعه کاربری).
