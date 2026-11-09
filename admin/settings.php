<?php
declare(strict_types=1);
require_once __DIR__ . '/_shell.php';

$tabs = ['business' => 'Business details', 'email' => 'Email', 'ai' => 'AI writing', 'google' => 'Google', 'meta' => 'Meta pixel'];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'business';

$plain = [
    'business' => ['biz_name', 'biz_street', 'biz_suburb', 'biz_city', 'biz_postcode', 'biz_phone', 'biz_whatsapp', 'biz_email', 'biz_hours', 'biz_area', 'biz_lat', 'biz_lng', 'biz_facebook', 'biz_instagram', 'biz_tiktok', 'biz_linkedin', 'notify_email'],
    'email'    => ['smtp_host', 'smtp_port', 'smtp_user', 'mail_from_name'],
    'ai'       => ['groq_model'],
    'google'   => ['ga4_id', 'gsc_code', 'gads_id', 'gads_label_lead', 'gads_label_whatsapp', 'google_review_url', 'google_profile_url', 'gbp_description'],
    'meta'     => ['meta_pixel_id'],
];
$secretKeys = ['email' => ['smtp_pass'], 'ai' => ['groq_api_key'], 'meta' => ['meta_capi_token']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($plain[$tab] as $k) if (array_key_exists($k, $_POST)) set_setting($k, mb_substr(trim((string) $_POST[$k]), 0, 2000));
    foreach ($secretKeys[$tab] ?? [] as $k) save_secret_setting($k, (string) ($_POST[$k] ?? ''));
    if ($tab === 'ai') set_setting('ai_enabled', !empty($_POST['ai_enabled']) ? '1' : '0');
    if ($tab === 'google') set_setting('gsc_code', preg_replace('/.*content="([^"]+)".*/', '$1', get_setting('gsc_code')));
    audit('settings_saved', $tab);
    flash('success', $tabs[$tab] . ' saved.');
    redirect('admin/settings.php?tab=' . $tab);
}

$v = fn(string $k, string $d = '') => e(get_setting($k, $d));
$field = fn(string $k, string $label, string $hint = '', string $ph = '', string $d = '') =>
    '<div class="field"><label for="' . $k . '">' . e($label) . '</label><input id="' . $k . '" name="' . $k . '" value="' . $v($k, $d) . '" placeholder="' . e($ph) . '">' . ($hint ? '<p class="hint">' . $hint . '</p>' : '') . '</div>';
$secretField = fn(string $k, string $label, string $hint = '') =>
    '<div class="field"><label for="' . $k . '">' . e($label) . '</label><input id="' . $k . '" name="' . $k . '" type="password" autocomplete="new-password" placeholder="' . e(masked(get_setting($k, (string) secret($k, ''))) ?: 'Not set') . '">' . '<p class="hint">' . ($hint ?: 'Leave blank to keep the saved value.') . '</p></div>';

adminOpen('settings', 'Settings', 'Every key and ID the site uses. Step-by-step help: docs/SETUP-GUIDE.md');
?>
<nav class="tabs" style="margin-bottom:1rem"><?php foreach ($tabs as $k => $label): ?><a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'is-on' : '' ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
<div class="grid-2">
<form method="post" class="card"><div class="card__body">
  <?= csrfField() ?>
  <?php if ($tab === 'business'): ?>
    <p class="hint" style="margin-bottom:1rem"><strong>Keep these identical to your Google Business Profile.</strong> They appear in the footer, in Google's structured data, on every WhatsApp button and in the chat bot's answers.</p>
    <div class="form-grid">
      <?= $field('biz_name', 'Trading name', '', '', 'Extra Printers') ?><?= $field('biz_phone', 'Phone', '', '011 123 4567') ?>
      <?= $field('biz_street', 'Street address', '', '12 Main Street') ?><?= $field('biz_suburb', 'Suburb', '', 'Johannesburg CBD') ?>
      <?= $field('biz_city', 'City', '', '', 'Johannesburg') ?><?= $field('biz_postcode', 'Postal code', '', '2001') ?>
      <?= $field('biz_whatsapp', 'WhatsApp number', 'Used for every WhatsApp button. Digits only, e.g. 27821234567 or 0821234567.', '27630693955', '27630693955') ?><?= $field('biz_email', 'Public email', '', '', 'quotes@extraprinters.co.za') ?>
    </div>
    <?= $field('biz_hours', 'Opening hours', '', '', 'Mon–Fri 08:00–17:00 · Sat 08:00–13:00') ?>
    <?= $field('biz_area', 'Areas served', 'Comma separated. Used by the chat bot and in Google data.', '', 'Johannesburg, Sandton, Randburg, Roodepoort, Soweto, Midrand, Germiston') ?>
    <div class="form-grid"><?= $field('biz_lat', 'Latitude (optional)', 'From Google Maps: right-click your shop, then copy the numbers.', '-26.2041') ?><?= $field('biz_lng', 'Longitude (optional)', '', '28.0473') ?></div>
    <div class="form-grid"><?= $field('biz_facebook', 'Facebook page URL') ?><?= $field('biz_instagram', 'Instagram URL') ?><?= $field('biz_tiktok', 'TikTok URL') ?><?= $field('biz_linkedin', 'LinkedIn URL') ?></div>
    <?= $field('notify_email', 'Send new quote requests to', 'Leave blank to use the public email.') ?>
  <?php elseif ($tab === 'email'): ?>
    <p class="hint" style="margin-bottom:1rem">🟧 cPanel → Email Accounts → create <code>quotes@extraprinters.co.za</code> → Connect Devices shows the server. Without a password the site uses PHP mail(), which often lands in spam.</p>
    <div class="form-grid"><?= $field('smtp_host', 'Outgoing server', '', 'mail.extraprinters.co.za', SMTP_HOST) ?><?= $field('smtp_port', 'Port', '', '465', (string) SMTP_PORT) ?></div>
    <?= $field('smtp_user', 'Mailbox (username)', '', 'quotes@extraprinters.co.za', SMTP_USER) ?>
    <?= $secretField('smtp_pass', 'Mailbox password') ?>
    <?= $field('mail_from_name', 'From name', '', '', 'Extra Printers') ?>
  <?php elseif ($tab === 'ai'): ?>
    <p class="hint" style="margin-bottom:1rem">🟦 console.groq.com → API Keys → Create API Key. Free. It writes product descriptions, blog articles, social captions and chat replies.</p>
    <?= $secretField('groq_api_key', 'Groq API key') ?>
    <?= $field('groq_model', 'Model (optional)', 'Blank = picked automatically: ' . e(aiKey() ? groqModel() : GROQ_PREFERRED[0]) . '. Retired models are swapped automatically.') ?>
    <label class="chip-check" style="margin-bottom:1rem"><input type="checkbox" name="ai_enabled" value="1" <?= get_setting('ai_enabled', '1') === '1' ? 'checked' : '' ?>> AI writing on</label>
  <?php elseif ($tab === 'google'): ?>
    <?= $field('ga4_id', 'GA4 Measurement ID', '🟦 analytics.google.com → Admin → Data streams → Website.', 'G-XXXXXXXXXX') ?>
    <?= $field('gsc_code', 'Search Console verification code', 'Only for the "HTML tag" method: paste the content="…" value (or the whole tag).', 'abc123…') ?>
    <div class="form-grid"><?= $field('gads_id', 'Google Ads conversion ID', '', 'AW-123456789') ?><?= $field('gads_label_lead', 'Label: Quote request', '', 'AbC-dEfGhIj') ?></div>
    <?= $field('gads_label_whatsapp', 'Label: WhatsApp click', '', 'XyZ-123') ?>
    <?= $field('google_review_url', 'Google review link', 'Business Profile → Ask for reviews → copy. Added to every quote email.', 'https://g.page/r/…/review') ?>
    <?= $field('google_profile_url', 'Google Business Profile / Maps link', 'Added to the business data Google reads.', 'https://maps.app.goo.gl/…') ?>
    <div class="field"><label for="gbp_description">Google Business description (copy into your profile)</label>
      <textarea id="gbp_description" name="gbp_description" rows="6" maxlength="750"><?= $v('gbp_description', 'Extra Printers is a paper supplier and printing company in Johannesburg. We sell all kinds of paper in A1, A2, A3 and A4: gloss, matte, NCR, carbonless, self-carbonated, sticker and business card paper, plus till rolls for retail machines. We print custom invoice books (duplicate, triplicate and quadruplicate), business cards, letterheads, flyers, posters, calendars, certificates, company profiles, diaries, branded pens, T-shirts and embroidery, and make pull up banners, gazebos, flags and lightboxes. Upload your artwork on our website for a quick quote, or let our designers create it for R300. Collection in Johannesburg or delivery across Gauteng.') ?></textarea><p class="hint">Max 750 characters. No links or phone numbers (Google rejects them).</p></div>
  <?php elseif ($tab === 'meta'): ?>
    <?= $field('meta_pixel_id', 'Meta Pixel (Dataset) ID', '🟦 Events Manager → Connect data sources → Web.', '1234567890') ?>
    <?= $secretField('meta_capi_token', 'Conversions API token', 'Events Manager → Settings → Conversions API → Generate access token.') ?>
    <p class="hint">Facebook/Instagram posting and the chat bot are under <a href="social.php">Social &amp; bots</a>.</p>
  <?php endif; ?>
  <div class="form-foot"><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
    <?php if ($tab === 'email'): ?><button type="button" class="btn btn-ghost" data-test="test_email">Send test email</button><?php endif; ?>
    <?php if ($tab === 'ai'): ?><button type="button" class="btn btn-ghost" data-test="test_ai">Test</button><?php endif; ?>
  </div>
</div></form>

<section class="card">
  <div class="card__head"><h2>Every URL you'll paste</h2></div>
  <div class="card__body">
    <?= copyBox('Website', CANONICAL_URL) ?>
    <?= copyBox('Sitemap (Search Console → Sitemaps)', CANONICAL_URL . '/sitemap.xml') ?>
    <?= copyBox('Quote link (Business Profile)', CANONICAL_URL . '/cart.php') ?>
    <?= copyBox('Privacy / data deletion (Meta)', CANONICAL_URL . '/privacy.php') ?>
    <?= copyBox('Meta OAuth redirect URI', CANONICAL_URL . '/admin/meta-connect.php') ?>
    <?= copyBox('Meta webhook callback URL', CANONICAL_URL . '/webhook.php') ?>
    <?= copyBox('Landing page: invoice books', CANONICAL_URL . '/invoice-books-johannesburg') ?>
    <?= copyBox('Landing page: paper', CANONICAL_URL . '/paper-supplier-johannesburg') ?>
    <p class="hint">Cron (cPanel → Cron Jobs, every 15 min): <code>/usr/local/bin/php <?= e(str_replace('\\', '/', BASE_DIR)) ?>/cron.php</code> · last ran: <?= e(get_setting('cron_last') ? timeAgo(get_setting('cron_last')) : 'never') ?></p>
  </div>
</section>
</div>
<?php adminClose('<script>document.querySelectorAll("[data-test]").forEach(function(b){b.onclick=function(){b.disabled=true;wfPost("admin/ai.php",{action:b.dataset.test}).then(function(r){b.disabled=false;wfToast(r.ok?r.message:(r.error||"Failed"));});};});</script>'); ?>
