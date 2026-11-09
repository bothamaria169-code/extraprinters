<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

$lines = cartLines();
$old   = $_SESSION['checkout_old'] ?? [];
unset($_SESSION['checkout_old']);
$fixed = cartFixedTotal();
$accept = '.' . implode(',.', ARTWORK_EXT);

siteOpen(['title' => 'Your quote cart | Extra Printers', 'path' => 'cart.php', 'noindex' => true]);
?>
<header class="phead"><div class="wrap">
  <h1>Your quote cart</h1>
  <p>Upload artwork for each product, check your options, then send the request. We reply with a quote by email and WhatsApp.</p>
</div></header>

<div class="wrap">
<?php if (!$lines): ?>
  <div class="empty-state" style="margin:40px 0 64px">
    <i class="fa-solid fa-cart-shopping"></i>
    <h2 style="margin:.6rem 0">Your quote cart is empty</h2>
    <p class="muted">Start with paper, invoice books or any product, or just WhatsApp us what you need.</p>
    <p style="margin-top:1.2rem;display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap">
      <a class="btn btn-primary" href="<?= e(url('#paper')) ?>"><i class="fa-solid fa-scroll"></i> Shop paper</a>
      <a class="btn btn-ghost" href="<?= e(url('shop.php?section=printing')) ?>"><i class="fa-solid fa-print"></i> Printing</a>
      <a class="btn btn-wa" href="<?= e(waLink("Hi Extra Printers, I'd like a quote.")) ?>" target="_blank" rel="noopener" data-track="whatsapp_click"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
    </p>
  </div>
<?php else: ?>
  <div class="cart-grid">
    <div>
      <?= flashHtml() ?>
      <?php if (isset($_GET['added'])): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><span>Added. Now upload your artwork below, or keep shopping.</span></div><?php endif; ?>
      <?php foreach ($lines as $key => $l): $p = $l['product']; ?>
        <article class="line" id="line-<?= e($key) ?>">
          <a class="line__img" href="<?= e(productUrl($p)) ?>"><?= productImageHtml($p, 'thumb') ?></a>
          <div>
            <div class="line__head">
              <div><span class="pcard__cat c-<?= e($p['color']) ?>"><?= e($p['cat_name']) ?></span><h3><?= e($p['name']) ?></h3></div>
              <button class="icon-btn" data-remove-line="<?= e($key) ?>" aria-label="Remove <?= e($p['name']) ?>"><i class="fa-regular fa-trash-can"></i></button>
            </div>
            <div class="line__opts"><?php foreach ($l['options'] as $label => $val): ?><span><b><?= e($label) ?>:</b> <?= e($val) ?></span><?php endforeach; ?></div>
            <?php if ($p['fixed_price']): ?><p class="small"><strong><?= money($p['price_from']) ?></strong> <?= e($p['price_note']) ?></p><?php endif; ?>
            <textarea class="input" rows="2" data-line-notes="<?= e($key) ?>" placeholder="Notes for this product (optional)" aria-label="Notes for <?= e($p['name']) ?>" style="margin-top:.4rem;font-size:.88rem"><?= e($l['notes']) ?></textarea>
            <?php if ($p['needs_artwork'] || $p['slug'] === 'graphic-design'): ?>
              <div class="drop" data-key="<?= e($key) ?>" tabindex="0" role="button" aria-label="Upload files for <?= e($p['name']) ?>">
                <input type="file" multiple accept="<?= e($accept) ?>">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <p><strong><?= $p['slug'] === 'graphic-design' ? 'Upload your logo, photos and references' : 'Upload your artwork' ?></strong>: drag files here or tap to choose.<br>
                  CMYK PDF at 300 dpi with 3 mm bleed is best. Up to <?= MAX_ARTWORK_FILES ?> files, <?= MAX_ARTWORK_MB ?> MB each.</p>
              </div>
            <?php endif; ?>
            <ul class="files">
              <?php foreach ($l['files'] as $f): ?>
                <li><i class="fa-solid fa-file"></i><span class="f-name"><?= e($f['name']) ?></span><small><?= fileSizeLabel($f['size']) ?></small>
                  <button type="button" aria-label="Remove file" data-key="<?= e($key) ?>" data-remove-file="<?= e($f['id']) ?>"><i class="fa-solid fa-xmark"></i></button></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </article>
      <?php endforeach; ?>
      <p style="display:flex;gap:.6rem;flex-wrap:wrap">
        <a class="btn btn-ghost" href="<?= e(url('shop.php')) ?>"><i class="fa-solid fa-plus"></i> Add another product</a>
        <a class="btn btn-ghost" href="<?= e(url('artwork-guidelines.php')) ?>" target="_blank"><i class="fa-solid fa-palette"></i> Artwork guidelines</a>
      </p>
    </div>

    <aside class="summary">
      <h2>Send your quote request</h2>
      <form method="post" action="<?= e(url('checkout.php')) ?>" data-busy>
        <?= csrfField() ?>
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
        <div class="field"><label for="name">Your name *</label><input id="name" name="name" required autocomplete="name" value="<?= e($old['name'] ?? '') ?>"></div>
        <div class="field"><label for="company">Company</label><input id="company" name="company" autocomplete="organization" value="<?= e($old['company'] ?? '') ?>"></div>
        <div class="field"><label for="email">Email *</label><input id="email" type="email" name="email" required autocomplete="email" value="<?= e($old['email'] ?? '') ?>"></div>
        <div class="field"><label for="phone">Phone / WhatsApp *</label><input id="phone" type="tel" name="phone" required autocomplete="tel" placeholder="082 123 4567" value="<?= e($old['phone'] ?? '') ?>"></div>
        <div class="field"><label>Collection or delivery</label>
          <div class="radio-cards">
            <label><input type="radio" name="delivery" value="collect" <?= ($old['delivery'] ?? 'collect') === 'collect' ? 'checked' : '' ?>> Collect</label>
            <label><input type="radio" name="delivery" value="deliver" <?= ($old['delivery'] ?? '') === 'deliver' ? 'checked' : '' ?>> Deliver</label>
          </div>
        </div>
        <div class="field"><label for="address">Delivery address</label><input id="address" name="address" autocomplete="street-address" placeholder="Only if delivering" value="<?= e($old['address'] ?? '') ?>"></div>
        <div class="field"><label for="needed_by">Needed by</label><input id="needed_by" type="date" name="needed_by" min="<?= date('Y-m-d') ?>" value="<?= e($old['needed_by'] ?? '') ?>"></div>
        <div class="field"><label for="file_link">Large files link</label><input id="file_link" type="url" name="file_link" placeholder="WeTransfer / Google Drive (optional)" value="<?= e($old['file_link'] ?? '') ?>"></div>
        <div class="field"><label for="cnotes">Message</label><textarea id="cnotes" name="notes" rows="3" placeholder="Anything we should know"><?= e($old['notes'] ?? '') ?></textarea></div>
        <label class="check" style="margin-bottom:1rem"><input type="checkbox" name="consent" value="1" required> <span>I agree that Extra Printers may use my details to quote and contact me (<a href="<?= e(url('privacy.php')) ?>" target="_blank">privacy</a>).</span></label>
        <?php if ($fixed > 0): ?><p class="small muted" style="margin-bottom:.8rem">Design fees so far: <strong><?= money($fixed) ?></strong>. Printing is quoted separately.</p><?php endif; ?>
        <button type="submit" class="btn btn-primary btn-lg btn-block"><i class="fa-solid fa-paper-plane"></i> Send quote request</button>
        <p class="small muted" style="margin-top:.7rem;text-align:center"><i class="fa-solid fa-lock"></i> Your files are private and only seen by our team.</p>
      </form>
    </aside>
  </div>
<?php endif; ?>
</div>
<?php siteClose(); ?>
