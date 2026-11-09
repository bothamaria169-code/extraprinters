# Extra Printers: setup guide (Google, Meta, AI, email)

Work through this from top to bottom. Each step says where you do it:

- 🟦 **ACCOUNT**: on Google, Meta or Groq (their websites)
- 🟩 **ADMIN**: in the site's admin at `https://extraprinters.co.za/admin/`
- 🟧 **CPANEL**: in your hosting control panel

> You can do sections 1–3 (Google Business Profile, Search Console, Analytics) **right now**, even before the site is live. Google Business Profile verification takes days, so start it first.

## Every URL you'll paste

| Where | Field | Paste exactly |
|---|---|---|
| Google Business Profile | Website | `https://extraprinters.co.za` |
| Google Business Profile | Appointment / quote link | `https://extraprinters.co.za/cart.php` |
| Search Console | Property (Domain) | `extraprinters.co.za` |
| Search Console → Sitemaps | Sitemap URL | `https://extraprinters.co.za/sitemap.xml` |
| GA4 → Data stream | Website URL | `https://extraprinters.co.za` |
| Meta → App settings → Basic | App domain | `extraprinters.co.za` |
| Meta → App settings → Basic | Privacy policy / data deletion URL | `https://extraprinters.co.za/privacy.php` |
| Meta → Facebook Login for Business | Valid OAuth Redirect URI | `https://extraprinters.co.za/admin/meta-connect.php` |
| Meta → WhatsApp / Messenger → Webhooks | Callback URL | `https://extraprinters.co.za/webhook.php` |

**Admin → Settings** shows each of these with a **Copy** button.

---

## 0. Same name, address and phone everywhere (2 min)

Google ranks a local business higher when its **N**ame, **A**ddress and **P**hone (NAP) match exactly on the website, the Business Profile, Facebook and directories. Write yours down once and copy it everywhere:

```
Extra Printers
<street number and street>, <suburb>, Johannesburg, <postal code>
<phone, e.g. 011 123 4567>
```

🟩 **Admin → Settings → Business details** → paste the same lines → **Save**.

## 1. Google Business Profile (🟦 ACCOUNT, 15 min + verification)

This is the most important step for local searches like "invoice books near me" and for the map results.

1. 🟦 Go to https://business.google.com → **Manage now**, and sign in with the Gmail that will own the business.
2. Business name: `Extra Printers` → **Next**.
3. Category: type **Printing service** and choose it → **Next**. Add more later (step 7).
4. "Do you want to add a location customers can visit?" → **Yes** if customers can collect from you, then enter the address from step 0.
   - Collection/delivery only, no shop counter? → **No**, then add **service areas** instead: Johannesburg, Sandton, Randburg, Roodepoort, Soweto, Midrand, Germiston, Kempton Park.
5. Phone and website from the table above → **Next** → **Finish**.
6. **Verify**: Google offers a video recording, a phone call, SMS or a postcard. For a video, film the street sign, the shop front with signage, and you unlocking the door or using the printing equipment, in one continuous take.
7. When verified, open **Edit profile**:
   - **Business category** → **Add another category**: `Paper store`, `Sign shop`, `Screen printer`, `Embroidery service`, `Graphic designer`, `Stationery store`.
   - **Description** (750 characters max): paste the one from **Admin → Settings → SEO → Google Business description**.
   - **Hours**, **Opening date**, **Logo** (`CMYK Petal X Logo.png`) and **Cover** (a shop or product photo).
   - **Products** → add **Invoice Books**, **Copy Paper A4**, **NCR Paper**, **Business Cards**, **Pull Up Banners** with a photo, a "from" price and the product's link.
   - **Services** → add every product name from the website.
8. **Photos**: add at least 10 real photos (shop, machines, finished jobs). Upload new ones weekly; Google rewards active profiles.
9. **Ask for reviews**: **Home** → **Ask for reviews** → copy the link → 🟩 **Admin → Settings → Google → Review link**. The site then adds a "Review us on Google" button to every quote email.

**Test:** search `Extra Printers Johannesburg` on your phone. The profile can take 1–7 days to show.

## 2. Google Search Console (🟦 ACCOUNT + 🟧 CPANEL, 10 min)

1. 🟦 https://search.google.com/search-console → **Add property** → choose **Domain** → type `extraprinters.co.za` → **Continue**.
2. Google shows a **TXT record** (starts with `google-site-verification=`) → **Copy**.
3. 🟧 cPanel → **Zone Editor** → `extraprinters.co.za` → **Manage** → **+ Add Record** → type **TXT**, name `extraprinters.co.za.`, record = paste → **Save Record**.
4. 🟦 Back in Search Console → **Verify**. If it fails, wait 30 minutes and press **Verify** again.
   > Can't edit DNS? Choose **URL prefix** instead → **HTML tag** → copy only the `content="…"` value → 🟩 **Admin → Settings → Google → Search Console code** → **Save** → **Verify**.
5. 🟦 Once the site is live: **Sitemaps** → `sitemap.xml` → **Submit**.
6. 🟦 **URL inspection** → paste `https://extraprinters.co.za/invoice-books-johannesburg` → **Request indexing**. Repeat for the home page and `/paper-supplier-johannesburg`.

**Test:** after 2–3 days, **Pages** shows the pages as "Indexed".

## 3. Google Analytics 4 ("Google insights") (🟦 ACCOUNT, 10 min)

1. 🟦 https://analytics.google.com → **Start measuring** → account name `Extra Printers` → **Next**.
2. Property name `extraprinters.co.za`, time zone **South Africa**, currency **ZAR** → **Next** → industry **Business & industrial**, size **Small** → goals **Generate leads** → **Create** → accept the terms.
3. Platform **Web** → URL `https://extraprinters.co.za`, stream name `Website` → **Create stream**.
4. Copy the **Measurement ID** (`G-XXXXXXXXXX`) → 🟩 **Admin → Settings → Google → GA4 Measurement ID** → **Save**.
5. 🟦 **Admin** (gear icon) → **Events**. After the first visits, mark these as **Key events** (the star): `generate_lead` (quote request sent), `whatsapp_click`, `add_to_cart`, `file_upload`. The site sends all four already.
6. 🟦 **Admin** → **Product links** → **Search Console links** → **Link** → choose the property from section 2.

**Test:** open the site in another tab → GA4 **Reports** → **Realtime** shows 1 user.

## 4. Google Ads (🟦 ACCOUNT, 20 min)

The budget, keywords and the 7-day campaign plan are in **docs/Extra-Printers-SEO-and-Google-Ads-Plan.pdf**. To set it up:

1. 🟦 https://ads.google.com → **Start now** → choose **Switch to Expert Mode** (at the bottom of the first screen) → **Create an account without a campaign**. Billing country **South Africa**, time zone **(GMT+02:00) Johannesburg**, currency **ZAR**.
2. 🟦 **Goals** → **Conversions** → **+ New conversion action** → **Website** → URL `https://extraprinters.co.za` → **Scan** → **+ Add a conversion action manually**:
   - Category **Submit lead form**, name `Quote request`, value **Use the same value**, R250, count **One** → **Done**.
   - Choose **Use Google Tag Manager** → copy the **Conversion ID** and **Conversion label** → 🟩 **Admin → Settings → Google → Ads conversion ID / label** → **Save**.
   - Repeat for `WhatsApp click` (category **Contact**, value R100).
3. 🟦 **Tools** → **Data manager** → link **Google Analytics** and **Search Console**.
4. 🟦 **Campaigns** → **+** → **New campaign** → objective **Leads** → type **Search** → conversion goals: keep **Submit lead form** and **Contact**.
5. Follow the PDF for each campaign: **Location** → **Enter another location** → `Johannesburg` → **Radius** 25 km (Invoice Books) / 35 km (Paper); **Location options** → **Presence: People in or regularly in your targeted locations**. Budget per day from the PDF; bidding **Maximise conversions**. Then the ad groups, keywords (phrase and exact match), **negative keywords**, responsive search ads (copy from the PDF), **sitelinks**, **call** and **location** assets (link the Business Profile).
6. **Publish campaign**.

**Test:** in **Goals** → **Conversions**, the status changes to **Recording conversions** after the first quote request.

## 5. Email delivery (🟧 CPANEL + 🟩 ADMIN, 5 min)

1. 🟧 **Email Accounts** → **+ Create** → `quotes@extraprinters.co.za` with a strong password.
2. 🟧 **Email Deliverability** → `extraprinters.co.za` → SPF and DKIM must be **Valid**. If not, press **Repair**.
3. 🟩 **Settings → Email delivery** → host `mail.extraprinters.co.za`, port **465**, username `quotes@extraprinters.co.za`, password → **Save** → **Send test email**.

## 6. AI writing with Groq (🟦 ACCOUNT, 3 min, free)

1. 🟦 https://console.groq.com → sign in with Google → **API Keys** → **Create API Key** → name `extraprinters` → copy it.
2. 🟩 **Settings → AI writing** → paste → **Save** → **Test**. The model is picked automatically and swapped if Groq retires it.

AI then writes product descriptions (**✨ Write with AI** on each product), two blog articles a week on your keywords, the social post captions and the chat-bot replies.

## 7. Meta: auto-posts on Facebook and Instagram (🟦 ACCOUNT, 20 min)

1. 🟦 Make sure the Instagram account is a **Professional → Business** account linked to the Extra Printers Facebook Page (Meta Business Suite → **Settings** → **Accounts** → **Instagram accounts**).
2. 🟦 https://developers.facebook.com/apps → **Create app** → use case **Manage everything on your Page** → name `Extra Printers Poster` → **Create app**.
3. 🟦 **App settings → Basic**: app domain, privacy policy URL, data deletion URL (table above), category **Business and pages** → **Save changes**. Copy the **App ID** and **App secret**.
4. 🟦 **Facebook Login for Business → Settings** → Valid OAuth Redirect URIs → `https://extraprinters.co.za/admin/meta-connect.php` → **Save changes**.
5. 🟦 **Use cases → Customise** → add `pages_show_list`, `pages_manage_posts`, `pages_read_engagement`, `pages_messaging`, `instagram_basic`, `instagram_content_publish`, `instagram_manage_messages`, `business_management`.
6. 🟩 **Social & bots → Connections** → paste App ID + secret → **Save keys** → **Connect with Facebook** → tick the Page and the Instagram account → **Save**.
7. 🟩 **Social & bots → Auto-posting** → pick the days and times (e.g. Mon/Wed/Fri 08:00) → switch on. The bot picks a product that hasn't been posted lately, writes the caption with AI and posts it with the product photo.

**Test:** 🟩 **Social & bots** → **Post one now** → check the Facebook Page.

## 8. Meta: auto-reply chat bots (WhatsApp, Messenger, Instagram) (🟦 ACCOUNT, 20 min)

1. 🟦 In the same Meta app → **Add use case** → **Connect with customers through WhatsApp** → **Start using the API**. Choose your business's WhatsApp number. (A number can't be on the WhatsApp Business app and the Cloud API at the same time; use a second number or migrate it.)
2. 🟦 **WhatsApp → API Setup** → copy the **Phone number ID**. For a token that doesn't expire: Business Settings → **System users** → **Add** → role **Admin** → **Generate new token** → app `Extra Printers Poster`, permissions `whatsapp_business_messaging`, `whatsapp_business_management` → copy.
3. 🟩 **Social & bots → Chat bot** → paste the phone number ID and token, then write any **Verify token** you like (e.g. `xp-verify-2026`) → **Save**.
4. 🟦 **WhatsApp → Configuration** → **Webhook** → **Edit** → callback URL `https://extraprinters.co.za/webhook.php`, the same verify token → **Verify and save** → **Webhook fields** → subscribe to **messages**.
5. 🟦 Messenger + Instagram DMs: **Messenger API settings** → **Webhooks** → same callback URL and verify token → **Add subscriptions** → `messages`, `messaging_postbacks`. Do the same under **Instagram** → **Webhooks**.

The bot answers with your products, sizes, artwork rules and opening hours. It never makes up a price: it sends the product link and offers a quote. When someone types "human" or "agent", it stops replying and alerts you by email.

**Test:** send "Hi, do you print invoice books?" to the WhatsApp number. A reply arrives within seconds and shows in 🟩 **Social & bots → Chat log**.

## 9. Meta Pixel (🟦 ACCOUNT, 5 min)

1. 🟦 Meta **Events Manager** → **Connect data sources** → **Web** → name `Extra Printers` → **Create** → copy the **Dataset (Pixel) ID**.
2. 🟦 **Settings** → **Conversions API** → **Generate access token** → copy.
3. 🟩 **Settings → Meta** → paste both → **Save** → **Send test event**.

---

## Weekly routine (10 min)

- Reply to every Google review.
- Post 2–3 photos of finished jobs to the Business Profile.
- 🟩 **Admin → Quotes**: answer every new quote within 2 hours. Fast replies win most print jobs.
- Search Console → **Performance**: see which searches find you, and tell Claude to add a landing page for any that look promising.
