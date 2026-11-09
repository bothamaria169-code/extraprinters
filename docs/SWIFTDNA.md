# SWIFTDNA — the website build DNA

> **Reference word: `SWIFTDNA`.** When a prompt says "build with SWIFTDNA" (or just "SWIFTDNA"), follow every rule in this file.
> Built from Zama's own sites: **InvoiceChat** and **WorkFinder** (the base stack and design system), **BidCo Auctions** and **EasyAuctions** (Meta auto-posting, Groq articles and AI replies), **Auction Hub Group** (cron articles, sitemaps, README layout) and **HealthSolutions** (keeping secrets out of the web root), plus current web design practice.
> Code to copy from first: `C:\xampp\htdocs\WorkFinder` (newest), then `C:\xampp\htdocs\Invoicechat` (Meta, WhatsApp bot, tracking).

---

## 1. Stack (always the same)

- **PHP 8.1+**, `declare(strict_types=1);` in every file. **MySQL/MariaDB** through PDO with prepared statements only.
- **Composer:** PHPMailer (email), dompdf (PDFs, if needed). Nothing else unless the feature truly needs it.
- **No build step, no JS framework.** Plain PHP pages, `ajax/` endpoints that return JSON, one vanilla `assets/js/app.js`.
- **Fonts:** Bricolage Grotesque (headings) + Plus Jakarta Sans (body) from Google Fonts. **Icons:** Font Awesome 6 (cdnjs).
- Runs locally on **XAMPP** at `http://localhost/<folder>` and live on **cPanel** at `https://<name>.swiftfusion.co.za` (or the client's own domain).

## 2. Folder structure (copy it exactly)

```
<project>/
├── index.php                 ← landing page
├── login.php register.php forgot-password.php reset-password.php
├── dashboard.php             ← signed-in user's home (if the site has users)
├── privacy.php terms.php     ← needed by Google and Meta app review
├── cron.php                  ← every scheduled job (posts, articles, emails)
├── webhook.php               ← Meta webhooks (WhatsApp / Messenger / Instagram DMs)
├── sitemap.php robots.txt
├── admin/                    ← _shell.php + one file per admin page
├── ajax/                     ← _boot.php + one JSON endpoint per action
├── auth/google.php           ← Google sign-in callback
├── classes/                  ← MetaPoster.php, GoogleAuth.php, other API wrappers
├── includes/                 ← auth, security, functions, settings, mailer, layout, ai, social, tracking
├── config/                   ← config.php, db.php, secrets.example.php, secrets.php (git-ignored)
├── database/migrate.php      ← creates/updates tables itself on first page load
├── assets/css/ (style.css, app.css, admin.css)  assets/js/app.js  assets/img/mark.svg
├── uploads/                  ← with its own .htaccess (no PHP execution)
├── docs/SETUP-GUIDE.md       ← step-by-step switch-on guide
├── README.md
├── .htaccess .cpanel.yml .gitignore
```

## 3. Design system — what "clean" means

### Tokens (every colour is a token; dark mode only re-points tokens)
Start from `WorkFinder/assets/css/style.css`. Only `--brand`, `--brand-600/700/50/100` and the wordmark change per site.

```css
:root {
  --brand:#0d9488; --brand-600:#0b7d73; --brand-700:#09625b; --brand-50:#e6f6f4; --brand-100:#c8ece7;
  --mint:#16a34a; --amber:#d98a06; --coral:#e5484d; --violet:#7c5cff;
  --ink:#0e1726; --bg:#f4f6fb; --surface:#fff; --surface-2:#f8f9fc;
  --line:#e4e8f1; --line-strong:#cfd6e3; --text:#0e1726; --text-2:#4a5768; --text-3:#8390a3;
  --font:'Plus Jakarta Sans',system-ui,sans-serif; --font-display:'Bricolage Grotesque',sans-serif;
  --r-sm:10px; --r:14px; --r-lg:20px; --r-xl:28px; --r-pill:999px;
  --e-1:0 1px 2px rgba(14,23,38,.06),0 1px 3px rgba(14,23,38,.04);
  --e-2:0 2px 6px rgba(14,23,38,.05),0 10px 24px rgba(14,23,38,.07);
  --e-brand:0 10px 24px color-mix(in srgb,var(--brand) 28%,transparent);
  --t:.2s cubic-bezier(.4,0,.2,1);
}
/* + @media (prefers-color-scheme: dark) :root:not([data-theme="light"]) {…}  and  :root[data-theme="dark"] {…} */
```

### Rules
1. **One brand colour**, used for buttons, links and one accent per section. Everything else is ink, grey and white.
2. **Spacing on an 8px grid** (8 / 16 / 24 / 32 / 48 / 64 / 96). Sections get `padding-block: clamp(56px, 8vw, 112px)`.
3. **Content width** `max-width: 1200px` with a 16px gutter on phones (20–24px from tablet up). Text blocks max **65 characters** wide.
4. **Fluid type:** `h1 { font-size: clamp(2.2rem, 5vw, 3.8rem) }`, `h2 clamp(1.7rem,3.5vw,2.6rem)`, body 15–16px, line-height 1.55.
5. **Components** (reuse, don't reinvent): `.btn .btn-primary .btn-ghost .btn-dark`, `.card`, `.kpi` tiles, `.tbl` tables, `.pill` status chips, split-screen auth pages, `adm-head` page headers.
6. **Mobile first.** Tables turn into cards under 720px using `data-label`. Tap targets at least 44×44px. No horizontal scroll, ever.
7. **Light + dark theme** with a toggle stored in `localStorage` and `data-theme` on `<html>`.
8. **Motion:** hover lift (`translateY(-2px)` + shadow), 200ms. Turn it all off under `prefers-reduced-motion`.
9. **Images:** WebP, `loading="lazy"` below the fold, `width`/`height` set (no layout jump), `object-fit: cover` in fixed `aspect-ratio` frames.
10. **Accessibility (WCAG 2.2 AA):** text contrast 4.5:1, visible `:focus-visible` ring, one `<h1>` per page, labels on every input, `alt` on every image.
11. **Speed targets (Core Web Vitals):** LCP under 2.5s, INP under 200ms, CLS under 0.1. Preload the hero image and fonts, `defer` all scripts.

### Public page anatomy (pick what the site needs, in this order)
Announcement bar (optional) → sticky white header (logo, nav, CTA button, theme toggle) → **hero** (headline, one-line promise, primary + ghost button, image or live stat) → trust strip (logos / numbers) → products or services grid → "How it works" (3–4 steps) → feature split sections (image left/right alternating) → testimonials → FAQ (`<details>`) → dark CTA band → footer (4 columns, contact, socials, legal) → floating WhatsApp button.

### SEO on every site
Local first: a landing page for each money keyword + city (e.g. `/invoice-books-johannesburg`), NAP (name, address, phone) identical everywhere, Google Business Profile link, LocalBusiness schema with `areaServed`, FAQ schema. When the client asks for it, deliver an SEO + Google Ads PDF covering keywords, a competitor table, and a daily budget for a 7-day campaign.
Unique `<title>` and meta description per page, Open Graph + Twitter tags with a 1200×630 image, `schema.org` JSON-LD (Organization / LocalBusiness / Product), `sitemap.php`, `robots.txt`, canonical URLs, Google Search Console verification file.

## 4. Code rules

- `e()` on every output, CSRF token on every form and AJAX POST, sign-in rate limiting, single-use hashed reset tokens (`includes/security.php`).
- **Secrets** live only in `config/secrets.php` (git-ignored, uploaded once with File Manager). `secrets.example.php` shows every key with `CHANGE_ME`. Never put a real key in README, HTML or git.
- **Settings table** + `get_setting()` / `set_setting()`: every API key the owner may change (Groq, Meta, Google, SMTP) is editable in **Admin → Settings**, falling back to `secret()`.
- **Self-running migrations:** `database/migrate.php` adds missing tables/columns on the first page load after a deploy, and seeds the admin `admin@swiftfusion.co.za` from `admin_seed_password` (bcrypt). Includes `admin_reset_password` recovery.
- `utf8mb4`, foreign keys, indexes on every filter column.
- Uploads outside PHP execution; private files served through a signed PHP link.
- POPIA: consent tick at sign-up, "Download my data" and "Delete my account".

## 5. Admin dashboard (`/admin`)

Same shell as WorkFinder/InvoiceChat (`admin/_shell.php`): sidebar, `adm-head`, KPI row, then cards and tables. Standard pages:
**Dashboard** (KPIs + 30-day chart) · **Users** · the site's own content pages (products, orders, quotes…) · **Social & ads** (Meta connect, auto-post schedule, post log, pixels) · **AI** (Groq key, model, on/off, monthly limits) · **Emails** · **Payments** (if any) · **Settings** (every key, each with a **Test** button) · **Audit log**.

## 6. Groq AI writing (copy `WorkFinder/includes/ai.php`)

- Key from https://console.groq.com → API Keys, saved in **Admin → Settings → AI**.
- **Never hard-code one model.** `GROQ_PREFERRED` list (`openai/gpt-oss-120b` first), read the key's live model list from `https://api.groq.com/openai/v1/models` (cached a day), auto-switch when a model is retired, retry without JSON mode if a model rejects it.
- `groqJson($system, $user)` for structured output; reasoning models get extra `max_completion_tokens` and `reasoning_effort: low`.
- Log every call in `ai_requests` (user, kind, ok) and enforce monthly limits.
- **Use it for:** SEO articles from `cron.php` (like Auction Hub / EasyAuctions `ArticleWriter`), social captions + hashtags, product/service descriptions in admin ("✨ Write with AI" button), personal auto-replies to contact-form enquiries (EasyAuctions `AutoReply`: AI writes the body only; the letterhead, greeting and signature are fixed; on failure fall back to the standard thank-you).

## 7. Meta auto-bots (copy `Invoicechat/classes/MetaPoster.php` + `webhook.php`, BidCo `includes/social.php`)

1. **Auto-posting bot (Facebook Page + Instagram).** Graph API `v21.0`. Admin pastes App ID + secret → **Connect with Facebook** (`admin/meta-connect.php`) → long-lived user token → non-expiring Page token, saved in settings. Scopes: `pages_show_list, pages_manage_posts, pages_read_engagement, instagram_basic, instagram_content_publish, business_management`. `cron.php` picks an item that hasn't been posted recently, writes the caption with Groq (template fallback), posts at set time slots, logs every post in `social_posts`, max one per platform per slot. Media must be on a public https URL (doesn't work from localhost).
2. **Chat bot (WhatsApp Cloud API, Messenger, Instagram DMs).** `webhook.php`: GET answers the `hub.verify_token` handshake; POST returns 200 immediately, then replies with Groq using the site's own data (prices, products, FAQs, opening hours) and hands over to a human on request. Extra scopes for DMs: `pages_messaging`, `instagram_manage_messages`.
3. **Tracking:** Meta Pixel + Conversions API (`includes/tracking.php`), with a **Send test event** button.
4. **Catalogue sync** (shops): product feed for Commerce Manager / WhatsApp catalogue.

## 8. Deploy (GitHub + cPanel)

- Repo `bothamaria169-code/<name>`, cloned in cPanel to `/home/swiftfusionco/repositories/<name>` (a different folder from the site).
- `.cpanel.yml` copies the site into `/home/swiftfusionco/<name>.swiftfusion.co.za/`; it never touches `secrets.php` or `uploads/`.
- Cron every 5–15 min: `/usr/local/bin/php /home/swiftfusionco/<site>/cron.php >/dev/null 2>&1`.
- Every change: push → **Update from Remote** → **Deploy HEAD Commit**.

## 9. README and guide rules (easy to follow)

Every project ships **`README.md`** and **`docs/SETUP-GUIDE.md`**, written for the owner, not for developers:

- **README top:** one line of what the site does, then the stack line, then an **Area → Where** table (page → file).
- Then **Run it locally** (XAMPP, numbered), **Deploy**, **After deploying — switch things on**, **Troubleshooting**.
- **Where-badges** at the start of each step: 🟦 **ACCOUNT** (Google, Meta, Groq websites) · 🟩 **ADMIN** (our admin panel) · 🟧 **CPANEL** (hosting).
- An **"Every URL you'll paste"** table (Where | Field | Paste exactly).
- Numbered click paths with **exact button names in bold**, joined with →, and a time estimate per section, e.g. `## 4. Meta (20 min)`.
- Every section ends with a **test step** ("Send test email", "open in a private window").
- Gotchas go in `>` notes. Short sentences, no jargon, no secrets.
- **Screenshots:** when Zama pastes a screenshot, read every label on it and use those exact words in the steps. Save reference screenshots in `docs/screenshots/` and page captures as `Page — Brand.png`. Link them in the guide under the step they belong to: `![Meta → Basic settings](docs/screenshots/meta-basic.png)`.

## 10. How every build runs

0. **README first.** Before any code, write `README.md` and `docs/SETUP-GUIDE.md` (database in cPanel, Google Business Profile, Search Console, Google Analytics 4, Google Ads, Meta, Groq, email). That way Zama can start the account setup while the build runs. Keep both files up to date as the build goes on.
1. Read this file and the reference project before writing code.
2. Work in phases, and show each phase working in the browser before starting the next: **(1)** foundation, design system, layout, auth, admin seed → **(2)** public pages → **(3)** the site's core feature → **(4)** admin pages → **(5)** Groq → **(6)** Meta bots → **(7)** SEO, README, SETUP-GUIDE, deploy files.
3. Check every page at 375px and 1280px, light and dark, before calling it done.

## 11. Kick-off prompt template

```
SWIFTDNA build: <Site name>
Folder: C:\xampp\htdocs\<folder>   Live: https://<name>.swiftfusion.co.za
Brand colour: <hex or "pick one">   Logo: <file>   Tone: <e.g. bold, premium, friendly>
What it does: <one paragraph>
Pages: <list>
Core feature: <e.g. quote calculator, shop, bookings>
Groq writes: <articles / captions / product text / replies>
Meta bots: <auto-post FB+IG / WhatsApp bot / Messenger bot / pixel>
Payments: <none / EFT / OTT / PayFast>
Screenshots attached: <what they show>
```
