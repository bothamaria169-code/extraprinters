<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

$slug = (string) ($_GET['slug'] ?? '');
$L = landings()[$slug] ?? null;
if (!$L) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

$prods = array_values(array_filter(array_map(fn($s) => product($s), $L['products']), fn($p) => $p && $p['is_active']));
$main  = $prods[0] ?? null;
$crumbs = [['Home', CANONICAL_URL . '/'], [$L['h1'], CANONICAL_URL . '/' . $slug]];
$service = ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $L['h1'], 'serviceType' => $L['keyword'],
            'provider' => ['@id' => CANONICAL_URL . '/#business'], 'areaServed' => array_map(fn($a) => ['@type' => 'Place', 'name' => $a], AREAS),
            'url' => CANONICAL_URL . '/' . $slug];
siteOpen(['title' => $L['title'], 'description' => $L['desc'], 'path' => $slug, 
          'schema' => [$service, schemaFaq($L['faq']), schemaBreadcrumb($crumbs)]]);
?>
<section class="hero" style="padding-bottom:48px">
  <div class="wrap">
    <?= breadcrumbsHtml([['Home', APP_URL . '/'], [$L['h1'], '']]) ?>
    <div class="hero__grid" style="margin-top:1rem">
      <div>
        <span class="eyebrow eyebrow--<?= e($L['color']) ?>"><i class="fa-solid fa-location-dot"></i> Johannesburg &amp; Gauteng</span>
        <h1><?= e($L['h1']) ?></h1>
        <?php foreach ($L['intro'] as $para): ?><p class="hero__lead" style="margin-bottom:.8rem"><?= e($para) ?></p><?php endforeach; ?>
        <div class="hero__cta">
          <?php if ($main): ?><a href="<?= e(productUrl($main)) ?>" class="btn btn-primary btn-lg"><i class="fa-solid fa-cart-plus"></i> Get a quote</a><?php endif; ?>
          <a href="<?= e(waLink('Hi Extra Printers, I found you searching "' . $L['keyword'] . '". I\'d like a quote.')) ?>" class="btn btn-wa btn-lg" target="_blank" rel="noopener" data-track="whatsapp_click"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
          <?php if (biz('phone')): ?><a href="<?= e(telLink()) ?>" class="btn btn-ghost btn-lg"><i class="fa-solid fa-phone"></i> Call</a><?php endif; ?>
        </div>
      </div>
      <div class="panel">
        <h2 style="font-size:1.3rem;margin-bottom:.4rem">Why order from Extra Printers</h2>
        <ul class="ticks"><?php foreach ($L['points'] as $pt): ?><li><?= e($pt) ?></li><?php endforeach; ?></ul>
      </div>
    </div>
  </div>
</section>
<section class="section section--tint" style="padding-top:48px">
  <div class="wrap">
    <div class="section__head"><h2>Choose your <?= e(mb_strtolower(preg_replace('/ (printed )?in Johannesburg$| Johannesburg$/i', '', $L['h1']))) ?></h2></div>
    <div class="pgrid"><?php foreach ($prods as $p): ?><?= productCard($p) ?><?php endforeach; ?></div>
  </div>
</section>
<section class="section">
  <div class="wrap split" style="align-items:start">
    <div>
      <h2 style="font-size:clamp(1.6rem,3.2vw,2.2rem);margin-bottom:1rem">Questions about <?= e(strtolower(str_replace(' Johannesburg', '', $L['keyword']))) ?></h2>
      <p class="muted" style="margin-bottom:1rem">We serve businesses across Johannesburg and Gauteng:</p>
      <div class="areas"><?php foreach (AREAS as $a): ?><span><?= e($a) ?></span><?php endforeach; ?></div>
    </div>
    <div class="faq"><?php foreach ($L['faq'] as $q => $a): ?><details><summary><?= e($q) ?></summary><p><?= e($a) ?></p></details><?php endforeach; ?></div>
  </div>
</section>
<?php siteClose(); ?>
