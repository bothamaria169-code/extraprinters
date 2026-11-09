<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

$p = product(preg_replace('/[^a-z0-9-]/', '', (string) ($_GET['slug'] ?? '')));
if (!$p || !$p['is_active']) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

$imgs    = productImages($p);
$opts    = productOptions($p);
$related = array_values(array_filter(products(['category_id' => $p['category_id']]), fn($r) => $r['id'] !== $p['id']));
if (count($related) < 4) $related = array_merge($related, array_filter(products(['section' => $p['section'], 'limit' => 8]), fn($r) => $r['id'] !== $p['id'] && $r['category_id'] !== $p['category_id']));
$related = array_slice($related, 0, 4);
$isInstall = $p['slug'] === 'signage-installation';

$crumbs = [['Home', CANONICAL_URL . '/'], [SECTIONS[$p['section']][0], CANONICAL_URL . '/shop.php?section=' . $p['section']],
           [$p['cat_name'], CANONICAL_URL . '/shop/' . $p['cat_slug']], [$p['name'], CANONICAL_URL . '/product/' . $p['slug']]];

siteOpen([
    'title'       => $p['seo_title'] ?: $p['name'] . ' Johannesburg | Extra Printers',
    'description' => $p['seo_description'] ?: excerpt($p['tagline'] . '. Choose your options, upload your artwork and get a quote from Extra Printers in Johannesburg.', 155),
    'path'        => 'product/' . $p['slug'],
    'image'       => $imgs ? str_replace(APP_URL, CANONICAL_URL, preg_replace('/\?v=\d+$/', '', $imgs[0])) : null,
    'active'      => $p['section'], 'type' => 'product',
    'schema'      => [schemaProduct($p, $imgs), schemaBreadcrumb($crumbs)],
]);
?>
<div class="wrap" style="padding-top:20px">
  <?= breadcrumbsHtml(array_map(fn($c) => [$c[0], str_replace(CANONICAL_URL, APP_URL, $c[1])], $crumbs)) ?>
</div>
<div class="wrap pdp">
  <div class="gallery">
    <div class="gallery__main"><?= productImageHtml($p, 'hero', true) ?></div>
    <?php if (count($imgs) > 1): ?>
      <div class="gallery__thumbs">
        <?php foreach ($imgs as $i => $src): ?><button type="button" data-src="<?= e($src) ?>" class="<?= $i ? '' : 'is-on' ?>" aria-label="Photo <?= $i + 1 ?>"><img src="<?= e($src) ?>" alt="" loading="lazy"></button><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <a href="<?= e(url('shop/' . $p['cat_slug'])) ?>" class="eyebrow eyebrow--<?= e($p['color'] === 'blue' ? '' : $p['color']) ?>"><i class="fa-solid <?= e($p['icon']) ?>"></i> <?= e($p['cat_name']) ?></a>
    <h1><?= e($p['name']) ?></h1>
    <p class="pdp__tag"><?= e($p['tagline']) ?></p>

    <?php if ($isInstall): ?>
      <div class="buybox">
        <p>Tell us about the site and upload photos or measurements. We'll arrange a site visit and quote for making and installing the signage.</p>
        <div class="buybox__btns">
          <a class="btn btn-primary" href="<?= e(url('request.php?type=installation')) ?>"><i class="fa-solid fa-screwdriver-wrench"></i> Request installation</a>
          <a class="btn btn-wa" href="<?= e(waLink(productWaMessage($p))) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-product="<?= e($p['slug']) ?>"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
        </div>
      </div>
    <?php else: ?>
    <form id="addForm" data-name="<?= e($p['name']) ?>" novalidate>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
      <?php foreach ($opts as $gi => $g): ?>
        <fieldset class="opt">
          <legend class="opt__label"><?= e($g['label']) ?></legend>
          <div class="opt__vals">
            <?php foreach ($g['values'] as $vi => $v): ?>
              <label><input type="radio" name="opt[<?= e($g['key']) ?>]" value="<?= e($v) ?>" <?= $vi === 0 ? 'checked' : '' ?>><span><?= e($v) ?></span></label>
            <?php endforeach; ?>
            <?php if (isset($g['custom_above'])): ?>
              <label><input type="radio" name="opt[<?= e($g['key']) ?>]" value="__custom"><span>Custom (above <?= number_format($g['custom_above'], 0, '.', ' ') ?>)</span></label>
            <?php endif; ?>
          </div>
          <?php if (isset($g['custom_above'])): ?>
            <div class="opt__custom field" data-custom-for="opt[<?= e($g['key']) ?>]">
              <input type="number" name="custom_qty" min="<?= (int) $g['custom_above'] + 1 ?>" step="1" placeholder="e.g. <?= number_format($g['custom_above'] * 2, 0, '', '') ?>" aria-label="Custom amount">
            </div>
          <?php endif; ?>
        </fieldset>
      <?php endforeach; ?>
      <div class="field">
        <label for="notes">Anything else? <span class="muted">(optional)</span></label>
        <textarea id="notes" name="notes" rows="3" placeholder="Finishing, colours, deadline, the last invoice number used…"></textarea>
      </div>
      <div class="buybox">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem"><?= priceHtml($p) ?><span class="small muted"><i class="fa-solid fa-location-dot"></i> Johannesburg · Gauteng delivery</span></div>
        <div class="buybox__btns">
          <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-cart-plus"></i> Add to quote</button>
          <a class="btn btn-wa btn-lg" href="<?= e(waLink(productWaMessage($p))) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-product="<?= e($p['slug']) ?>"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
        </div>
      </div>
    </form>
    <?php if ($p['needs_artwork']): ?>
      <div class="note-box" style="margin-top:1rem"><i class="fa-solid fa-palette"></i>
        <div><strong>Artwork:</strong> after adding to your quote you can upload your files: CMYK PDF, 300 dpi, 3 mm bleed, up to <?= MAX_ARTWORK_FILES ?> files of <?= MAX_ARTWORK_MB ?> MB.
          <a href="<?= e(url('artwork-guidelines.php')) ?>">Guidelines</a> · No artwork? <a href="<?= e(url('product/graphic-design')) ?>">We design it for R300</a>.</div></div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="desc">
      <h2 style="font-size:1.3rem;margin-bottom:.7rem">About <?= e(mb_strtolower($p['name']) === mb_strtolower($p['cat_name']) ? 'this product' : $p['name']) ?></h2>
      <?php foreach (preg_split('/\n\s*\n/', trim((string) $p['description'])) as $para): ?><p><?= nl2br(e($para)) ?></p><?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($related): ?>
<section class="section section--tint" style="padding-block:48px">
  <div class="wrap">
    <div class="section__head"><h2 style="font-size:1.6rem">You may also need</h2></div>
    <div class="pgrid"><?php foreach ($related as $r): ?><?= productCard($r) ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>
<?php siteClose(); ?>
