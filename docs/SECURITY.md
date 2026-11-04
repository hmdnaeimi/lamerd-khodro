# امنیت – لامرد خودرو

## ۱. الزامات پیاده‌سازی‌شده

| لایه | اقدام |
|---|---|
| AJAX همه endpointها | `check_ajax_referer( 'lkp_...', 'nonce' )` + بررسی `current_user_can` متناسب با عملیات |
| فرم‌های ادمین | `wp_nonce_field` + `wp_verify_nonce` + capability (`manage_lkp_options` / `edit_posts` / `edit_others_posts`) |
| ورودی‌ها | `sanitize_text_field`, `absint`, `floatval`, `wp_kses_post` (محتوا)، `esc_url_raw` (لینک) |
| خروجی‌ها | `esc_html`, `esc_attr`, `esc_url`, `wp_kses` (HTML مجاز) در تمام خروجی‌های قالب/پنل |
| SQL | فقط `$wpdb->prepare` با placeholderهای `%d/%s/%f`؛ هیچ کاتنگ متغیر در query |
| REST | `permission_callback` صریح (بدون پیش‌فرض `__return_true` برای مسیرهای غیرعمومی) |
| فایل آپلود | بررسی `wp_check_filetype_and_ext`، فقط مجاز image + xlsx/csv خروجی سمت سرور؛ مسیر export در uploads با نام تصادفی و پاک‌سازی خودکار |
| Rate Limit | AJAX: شمارنده درخواست در transient (۳۰ درخواست/دقیقه/کاربر/IP) → پاسخ 429؛ REST: ۶۰/دقیقه/IP |
| CSRF | لینک‌های مخرب admin با nonce از بین می‌روند؛ تنظیمات با POST + nonce |
| Brute force خزش | `X-Robots`/محدودیت جستجوی AJAX بدون noindex صفحات فیلتر (بخش سئو) |
| لاگ | پاک‌سازی URL از query احتمالاً حاوی token؛ بدون ذخیره هدر/بدنه کامل پاسخ خطا |
| حذف داده | فقط با تأیید دوم (فرم «حذف کامل» با عبارت تأیید) |

## ۲. بررسی‌های کدی اجباری پیش از انتشار

- جستجوی `$_GET/$_POST/$_REQUEST` بدون sanitize → ممنوع.
- جستجوی `" . $` داخل `$wpdb->query/get_results` → ممنوع.
- جستجوی `echo $` متغیر بدون `esc_*` در خروجی عمومی → ممنوع.
- جستجوی `wp_remote_` در `theme/` → ممنوع (کش: استقلال از API).

## ۳. تست امنیت (فاز ۷)

1. AJAX بدون nonce → 403.
2. AJAX با nonce کاربر مشترک برای عملیات ادمین → 403.
3. `?post_type=vehicles' OR1=1--` در جستجو → بدون خطای SQL (prepared).
4. ورودی `<script>` در نام خودرو/خبر → خروجی escaped.
5. آپلود فایل PHP به‌عنوان تصویر → رد.
6. REST بدون دسترسی → فقط داده عمومی (هیچ قیمت داخلی/لاگ ادمینی افشا نمی‌شود).
7. دسترسی مستقیم به فایل‌های افزونه (`includes/*.php`) → بدون خروجی (defined `ABSPATH` guard).

## ۴. محیط اجرا

- وردپرس به‌روز، PHP ≥ 8.1، `DISALLOW_FILE_EDIT` در `wp-config.php`، HTTPS اجباری، پیشوند دیتابیس اختصاصی در صورت امکان.
