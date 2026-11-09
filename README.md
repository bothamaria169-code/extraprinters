# Extra Printers

Online print shop for **Extra Printers, Johannesburg**. Customers browse papers, printing, signage and services, add products to a cart, upload their artwork and send a **quote request**. Each product also has a **WhatsApp enquiry** button. Customers can order a design for **R300 per artwork** or request a signage installation.
PHP 8.1+ · MySQL/MariaDB · no build step. Built with **SWIFTDNA** (same stack and design system as WorkFinder and InvoiceChat).

| Area | Where |
|---|---|
| Home page (paper shop first) | `index.php` |
| Shop, categories, products | `shop.php`, `product.php`, `includes/catalog.php` |
| Cart, artwork upload, quote request | `cart.php`, `checkout.php`, `ajax/cart.php`, `ajax/upload.php`, `includes/cart.php` |
| Customer's quote page | `quote.php` (private link sent by email) |
| Design request (R300) and signage installation | `request.php?type=design`, `request.php?type=installation` |
| Artwork guidelines | `artwork-guidelines.php` |
| Local SEO landing pages | `landing.php` (e.g. `/invoice-books-johannesburg`), `includes/seo.php` |
| Blog (AI-written printing tips) | `blog.php`, `post.php` |
| Admin | `admin/`: Dashboard, Quotes & orders, Products, Categories, Blog, Social & bots, Settings, Audit log |
| AI writing (Groq) | `includes/ai.php`: product text, blog articles, social captions, chat replies |
| Meta bots | `classes/MetaPoster.php` (auto-posts to Facebook + Instagram), `webhook.php` (WhatsApp / Messenger / Instagram auto-replies) |
| Scheduled work | `cron.php`: social posts, blog articles, clearing old cart uploads |
| Database | `database/migrate.php`: creates every table and the full product list itself on the first page load |
| Guides | **`docs/SETUP-GUIDE.md`** (Google Business Profile, Search Console, Analytics, Ads, Meta, Groq, step by step) · **`docs/Extra-Printers-SEO-and-Google-Ads-Plan.pdf`** (for the client) |

---

## 1. Run it on your computer (XAMPP, 5 min)

1. Open the **XAMPP Control Panel** → **Start** Apache and MySQL.
2. Open http://localhost/phpmyadmin → **New** → database name `extraprinters` → collation `utf8mb4_unicode_ci` → **Create**.
3. In the project folder, copy `config/secrets.example.php` to `config/secrets.php`. For local use, only set `admin_seed_password` (pick your own).
4. Open http://localhost/extraprinters. The tables and all 30+ products are created by themselves.
5. Admin: http://localhost/extraprinters/admin → sign in with `admin_email` and your `admin_seed_password`.

## 2. Put it online (🟧 CPANEL, 20 min)

1. 🟧 **MySQL® Databases** → create the database `<cpaneluser>_extraprinters` and a user `<cpaneluser>_extraprinters` with a strong password. Then **Add User To Database** → **ALL PRIVILEGES** → **Make Changes**.
2. Fill in `config/secrets.php`: `db_name`, `db_user`, `db_pass`, `admin_seed_password`, `app_url` = `https://extraprinters.co.za`.
3. Upload the site. Pick one way:
   - **Git (recommended):** 🟧 **Git™ Version Control** → **Create** → Clone URL `https://github.com/bothamaria169-code/extraprinters.git`, repository path `/home/tczghmfy/repositories/extraprinters` → **Create**. Then **Manage** → **Pull or Deploy** → **Deploy HEAD Commit**. `.cpanel.yml` copies the files into `public_html`.
   - **Zip:** 🟧 **File Manager** → `public_html` → **Upload** the zip → **Extract**.
4. 🟧 **File Manager** → upload `config/secrets.php` into `public_html/config/` (first time only; it is never in git).
5. 🟧 **Domains** → `extraprinters.co.za` → **Force HTTPS Redirect** → **On**.
6. 🟧 **Select PHP Version** → **8.2**, then under **Options** set `upload_max_filesize` = `64M` and `post_max_size` = `70M` (so 50 MB artwork files upload).
7. 🟧 **Cron Jobs** → **Once Per Fifteen Minutes**:
   ```bash
   /usr/local/bin/php /home/tczghmfy/public_html/cron.php >/dev/null 2>&1
   ```
8. Open https://extraprinters.co.za once, then sign in at `/admin`.

**Test:** add a product to the cart, upload a PDF, send the quote request, and check that it shows in **Admin → Quotes** and that both emails arrive.

## 3. Switch things on (🟩 ADMIN → Settings)

| Setting | What to fill in |
|---|---|
| **Business details** | Trading name, street address, suburb, phone, WhatsApp number (e.g. `27821234567`), email, opening hours. These appear in the footer, on Google and on every WhatsApp button, so keep them **identical** to your Google Business Profile |
| **Email delivery** | Mailbox `quotes@extraprinters.co.za` + password → **Send test email** |
| **AI writing (Groq)** | Key from console.groq.com → **Test** |
| **Google** | GA4 Measurement ID, Search Console verification code, Google Ads conversion ID/label, Business Profile review link |
| **Meta** | Pixel ID + Conversions API token; App ID + secret → **Connect with Facebook**; WhatsApp Cloud API token, phone number ID and verify token |

The full click-by-click steps are in **docs/SETUP-GUIDE.md**.

## 4. Product photos and mockups

Every product shows a branded placeholder until it has a photo. To replace it, use either way:

- **Admin → Products → Edit → Photos → Upload** (up to 5 photos per product; the first one is the main photo), **or**
- drop a file named after the product's **slug** into `assets/products/`, e.g. `assets/products/invoice-books.jpg`. Slugs are listed in **Admin → Products**.

Best size: **1200 × 1200 px** (square), JPG or WebP, under 400 KB. Calendars already uses `product.jpeg`.

## 5. Artwork rules shown to customers

PDF in **CMYK**, **300 dpi**, **3 mm bleed** and a **5 mm safe zone**, fonts outlined or embedded. Up to **5 files per product**, **50 MB each**. Accepted: PDF (best), AI, EPS, PSD, TIFF, JPG, PNG, ZIP. Bigger files go in as a WeTransfer or Google Drive link in the notes box. Files are private: only admins can download them.

## Troubleshooting

- **"We'll be right back" page** → the database details in `config/secrets.php` are wrong. The page tells you which one.
- **Uploads fail above ~2–40 MB** → the step 2.6 PHP limits weren't set.
- **Emails land in spam** → 🟧 **Email Deliverability** → `extraprinters.co.za` → **Repair** SPF and DKIM.
- **Locked out of admin** → set `admin_reset_password` in `secrets.php` and load any page once.
