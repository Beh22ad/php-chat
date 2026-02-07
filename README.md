# Lightweight File-Based Chat Application

A lightweight, file-based chat application built with PHP and vanilla JavaScript. It mimics the look and feel of Telegram, runs on shared hosting without a database (uses JSON files), and supports private messaging.

## 🌐 Languages
- **English** (below)
- **[فارسی](#فارسی)**

---

## English Description

### ✨ Features
- **No Database Required**: Uses JSON files to store data, making it incredibly easy to install on standard shared hosting (cPanel).
- **Telegram-like UI**: A responsive, mobile-friendly design that looks and feels like the popular Telegram app.
- **Private Messaging**: Users can register and chat privately with one another.
- **Real-time Updates**: Uses AJAX polling to fetch new messages every 3 seconds.
- **Notifications**: Supports browser notifications for new messages.
- **RTL Support**: Fully supports Persian/Farsi text (RTL alignment) for both input and display.
- **File Management**: Automatically splits JSON files when they grow too large to maintain performance.

### ⚠️ Security Warning
- This project is intended for **personal use**, such as chatting with family or friends.
- It is **not secure** for exchanging sensitive information, passwords, or secrets.
- Since it uses JSON files for storage, it is not optimized for high-traffic public websites.
- Anyone is welcome to fork, modify, and improve the code.

### 🚀 Installation
1. Upload the files to your server (e.g., inside `public_html/chat`).
2. **Crucial**: Create a folder named `db_data` in the same directory.
3. Set permissions for `db_data` to `755` (or `777` if necessary) so the script can write files.
4. Open `index.php` in your browser.

---

## فارسی

### ✨ ویژگی‌ها
- **بدون نیاز به دیتابیس**: از فایل‌های JSON برای ذخیره‌سازی استفاده می‌کند که نصب آن در هاستینگ‌های اشتراکی (مثل cPanel) بسیار آسان است.
- **ظاهر تلگرام**: دارای رابط کاربری واکنش‌گرا (Responsive) و شبیه به تلگرام، که هم در موبایل و هم دسکتاپ عالی به نظر می‌رسد.
- **چت خصوصی**: کاربران می‌توانند ثبت‌نام کرده و به صورت خصوصی با یکدیگر چت کنند.
- **بروزرسانی لحظه‌ای**: از AJAX برای دریافت پیام‌های جدید هر ۳ ثانیه استفاده می‌کند.
- **نوتیفیکیشن**: پشتیبانی از اعلان‌های مرورگر برای پیام‌های جدید.
- **پشتیبانی از فارسی**: کاملاً از متن فارسی (راست‌چین) پشتیبانی می‌کند.
- **مدیریت فایل**: اگر حجم فایل‌های JSON زیاد شود، به صورت خودکار فایل جدید ایجاد می‌کند.

### ⚠️ هشدار امنیتی
- این پروژه صرفاً برای **استفاده شخصی**، مانند گپ زدن با خانواده یا دوستان طراحی شده است.
- برای تبادل اطلاعات حساس، رمز عبور یا اسرار **امن نیست**.
- چون از فایل برای ذخیره‌سازی استفاده می‌کند، برای سایت‌های پربازدید و عمومی بهینه نشده است.
- هر کسی دعوت می‌شود تا کد را فورک کرده، تغییر دهد و بهبود بخشد.

### 🚀 نصب و راه‌اندازی
1. فایل‌ها را در سرور خود آپلود کنید (مثلاً در پوشه `public_html/chat`).
2. **مهم**: یک پوشه به نام `db_data` در همان مسیر ایجاد کنید.
3. دسترسی پوشه `db_data` را روی `755` (یا در صورت نیاز `777`) تنظیم کنید تا اسکریپت بتواند در آن فایل بسازد.
4. فایل `index.php` را در مرورگر خود باز کنید.

---

## 🛠 Development & Contributing | توسعه و همکاری

This project is open source. Feel free to submit issues or pull requests to make it better!

این پروژه متن‌باز است. شما می‌توانید برای گزارش مشکلات یا بهبود کد مشارکت کنید!

### Technologies Used | تکنولوژی‌های استفاده شده:
- **Backend | بک‌اند**: PHP (Native)
- **Frontend | فرانت‌اند**: HTML5, CSS3, JavaScript (Vanilla)
- **Data Storage | ذخیره‌سازی**: JSON Files
- **Icons | آیکون‌ها**: SVG