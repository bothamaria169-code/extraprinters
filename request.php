<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/quotes.php';

/**
 * Design request (R300 per artwork), custom signage installation and the
 * general contact form. Each becomes a quote in Admin → Quotes.
 */
$type = in_array($_GET['type'] ?? '', ['design', 'installation', 'contact'], true) ? $_GET['type'] : 'design';

$cfg = [
    'design' => [
        'title' => 'Request a Graphic Design – R300 per Artwork | Extra Printers Johannesburg',
        'h1' => 'Request a design', 'eyebrow' => ['magenta', 'fa-pen-nib', 'Graphic design · R300 per artwork'],
        'lead' => 'Tell us what you need and upload your logo, photos or examples. Our designer creates print-ready CMYK artwork, and you approve a proof before anything is printed.',
        'what' => ['Logo', 'Business cards', 'Flyer / poster', 'Invoice book layout', 'Letterhead', 'Banner / signage', 'Company profile', 'Calendar', 'T-shirt design', 'Other'],
        'files' => 'Logo, photos, examples you like (optional)',
    ],
    'installation' => [
        'title' => 'Custom Signage Installation Johannesburg | Extra Printers',
        'h1' => 'Custom signage installation', 'eyebrow' => ['yellow', 'fa-screwdriver-wrench', 'Signage installation'],
        'lead' => 'Shop fronts, lightboxes, window and wall graphics, and vehicle branding. Upload photos of the site and rough measurements, and we\'ll arrange a site visit and quote.',
        'what' => ['Shop front / fascia sign', 'Lightbox', 'Window graphics', 'Wall vinyl / wallpaper', 'Directional signs', 'Vehicle branding', 'Other'],
        'files' => 'Photos of the site, measurements, your design (optional)',
    ],
    'contact' => [
        'title' => 'Contact Extra Printers | Printing & Paper Johannesburg',
        'h1' => 'Send us a message', 'eyebrow' => ['cyan', 'fa-envelope', 'Contact'],
        'lead' => 'Questions about paper, printing or signage? Send a message and we\'ll get back to you, usually within a few business hours.',
        'what' => [], 'files' => 'Attach a file (optional)',
    ],
][$type];

$old = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    [$c, $err] = quoteCustomerFromPost();
    $old = $c + ['what' => (array) ($_POST['what'] ?? []), 'count' => (int) ($_POST['count'] ?? 1), 'site' => postText('site', 255)];
    $what = array_values(array_intersect($old['what'], $cfg['what']));
    if (!$err && $type !== 'contact' && !$what) $err = $type === 'design' ? 'Tick what you would like us to design.' : 'Tick the type of signage.';
    if (!$err && $c['notes'] === '') $err = $type === 'contact' ? 'Please write your message.' : 'Please describe what you need in the brief.';
    if (!$err && rateLimited('quote', 6, 60)) $err = 'You have sent several requests already. Please WhatsApp us instead.';

    // Files: save to a temporary folder first; createQuote() moves them under the reference.
    $saved = [];
    $tmp = ARTWORK_DIR . 'cart/req-' . bin2hex(random_bytes(6)) . '/';
    if (!$err && !empty($_FILES['files']['name'][0])) {
        $n = min(count($_FILES['files']['name']), MAX_ARTWORK_FILES);
        for ($i = 0; $i < $n; $i++) {
            $f = ['name' => $_FILES['files']['name'][$i], 'tmp_name' => $_FILES['files']['tmp_name'][$i], 'size' => $_FILES['files']['size'][$i], 'error' => $_FILES['files']['error'][$i]];
            if ($f['error'] === UPLOAD_ERR_NO_FILE) continue;
            if ($msg = checkArtworkUpload($f)) { $err = $f['name'] . ': ' . $msg; break; }
            if (!is_dir($tmp)) mkdir($tmp, 0775, true);
            $stored = bin2hex(random_bytes(10)) . '.' . strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (move_uploaded_file($f['tmp_name'], $tmp . $stored)) $saved[] = ['name' => mb_substr(basename($f['name']), 0, 200), 'stored' => $stored, 'size' => (int) $f['size']];
        }
    }

    if (!$err) {
        $opts = [];
        if ($what) $opts[$type === 'design' ? 'Design' : 'Signage'] = implode(', ', $what);
        if ($type === 'design') $opts['Number of artworks'] = (string) max(1, min(50, $old['count']));
        if ($type === 'installation' && $old['site']) $opts['Site'] = $old['site'];
        $product = $type === 'design' ? product('graphic-design') : ($type === 'installation' ? product('signage-installation') : null);
        $name = $type === 'contact' ? 'Website enquiry' : ($product['name'] ?? $cfg['h1']);
        $q = createQuote($type, $c, [['product' => $product, 'name' => $name, 'options' => $opts,
            'quantity' => $opts['Number of artworks'] ?? '', 'notes' => '', 'files' => $saved, 'dir' => $tmp]]);
        @rmdir($tmp);
        redirect(quoteUrl($q) . '&new=1');
    }
    foreach ($saved as $s) @unlink($tmp . $s['stored']);
    @rmdir($tmp);
    flash('error', $err);
}

siteOpen(['title' => $cfg['title'], 'description' => mb_substr($cfg['lead'], 0, 155), 'path' => $type === 'contact' ? 'contact.php' : 'request.php?type=' . $type,
          'active' => $type === 'contact' ? 'contact' : 'services']);
[$ec, $ei, $et] = $cfg['eyebrow'];
?>
<header class="phead"><div class="wrap">
  <span class="eyebrow eyebrow--<?= $ec ?>"><i class="fa-solid <?= $ei ?>"></i> <?= e($et) ?></span>
  <h1><?= e($cfg['h1']) ?></h1>
  <p><?= e($cfg['lead']) ?></p>
</div></header>

<div class="wrap cart-grid">
  <form method="post" enctype="multipart/form-data" class="form-card" data-busy>
    <?= flashHtml() ?>
    <?= csrfField() ?>
    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
    <?php if ($cfg['what']): ?>
      <fieldset class="opt"><legend class="opt__label"><?= $type === 'design' ? 'What should we design?' : 'What signage do you need?' ?> *</legend>
        <div class="opt__vals">
          <?php foreach ($cfg['what'] as $w): ?><label><input type="checkbox" name="what[]" value="<?= e($w) ?>" <?= in_array($w, $old['what'] ?? [], true) ? 'checked' : '' ?>><span><?= e($w) ?></span></label><?php endforeach; ?>
        </div>
      </fieldset>
    <?php endif; ?>
    <?php if ($type === 'design'): ?>
      <div class="field" style="max-width:220px"><label for="count">Number of artworks</label><input id="count" type="number" name="count" min="1" max="50" value="<?= (int) ($old['count'] ?? 1) ?>"></div>
    <?php endif; ?>
    <?php if ($type === 'installation'): ?>
      <div class="field"><label for="site">Site address</label><input id="site" name="site" placeholder="Street, suburb" value="<?= e($old['site'] ?? '') ?>"></div>
    <?php endif; ?>
    <div class="field"><label for="notes"><?= $type === 'contact' ? 'Your message' : ($type === 'design' ? 'Your brief' : 'Describe the job') ?> *</label>
      <textarea id="notes" name="notes" rows="6" placeholder="<?= e($type === 'design' ? 'Business name, text to include, colours, style you like, where it will be printed…' : ($type === 'installation' ? 'Size of the wall/window, height, lighting, deadline…' : 'How can we help?')) ?>"><?= e($old['notes'] ?? '') ?></textarea></div>
    <div class="field"><label for="files"><?= e($cfg['files']) ?></label>
      <input id="files" type="file" name="files[]" multiple accept=".<?= e(implode(',.', ARTWORK_EXT)) ?>" class="input">
      <p class="hint">Up to <?= MAX_ARTWORK_FILES ?> files, <?= MAX_ARTWORK_MB ?> MB each.</p></div>
    <div class="form-row">
      <div class="field"><label for="name">Your name *</label><input id="name" name="name" required autocomplete="name" value="<?= e($old['name'] ?? '') ?>"></div>
      <div class="field"><label for="company">Company</label><input id="company" name="company" autocomplete="organization" value="<?= e($old['company'] ?? '') ?>"></div>
      <div class="field"><label for="email">Email *</label><input id="email" type="email" name="email" required autocomplete="email" value="<?= e($old['email'] ?? '') ?>"></div>
      <div class="field"><label for="phone">Phone / WhatsApp *</label><input id="phone" type="tel" name="phone" required autocomplete="tel" value="<?= e($old['phone'] ?? '') ?>"></div>
    </div>
    <?php if ($type !== 'contact'): ?><div class="field" style="max-width:240px"><label for="needed_by">Needed by</label><input id="needed_by" type="date" name="needed_by" min="<?= date('Y-m-d') ?>" value="<?= e($old['needed_by'] ?? '') ?>"></div><?php endif; ?>
    <label class="check" style="margin-bottom:1rem"><input type="checkbox" name="consent" value="1" required> <span>I agree that Extra Printers may use my details to reply to me (<a href="<?= e(url('privacy.php')) ?>" target="_blank">privacy</a>).</span></label>
    <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-paper-plane"></i> <?= $type === 'contact' ? 'Send message' : 'Send request' ?></button>
  </form>

  <aside class="summary">
    <?php if ($type === 'design'): ?>
      <div class="price-big grad-text">R300</div><p class="muted" style="margin-bottom:1rem">per artwork, print-ready</p>
      <ul class="ticks"><li>CMYK, 300 dpi, with bleed</li><li>A proof to approve first</li><li>Changes included</li><li>Print it with us or anywhere</li></ul>
    <?php else: ?>
      <h2>Talk to us</h2>
      <ul class="ticks" style="margin-top:0">
        <?php if (biz('phone')): ?><li><a href="<?= e(telLink()) ?>"><?= e(biz('phone')) ?></a></li><?php endif; ?>
        <li><a href="mailto:<?= e(biz('email')) ?>"><?= e(biz('email')) ?></a></li>
        <?php if (bizAddress()): ?><li><?= e(bizAddress()) ?></li><?php endif; ?>
        <li><?= e(biz('hours')) ?></li>
      </ul>
    <?php endif; ?>
    <a class="btn btn-wa btn-block" href="<?= e(waLink($type === 'design' ? "Hi Extra Printers, I'd like a design." : "Hi Extra Printers, I have a question.")) ?>" target="_blank" rel="noopener" data-track="whatsapp_click"><i class="fa-brands fa-whatsapp"></i> Rather WhatsApp us</a>
  </aside>
</div>
<?php siteClose(); ?>
