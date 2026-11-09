<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

$q       = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
$cat     = isset($_GET['cat']) ? category((string) $_GET['cat']) : null;
$section = $cat['section'] ?? (isset(SECTIONS[$_GET['section'] ?? '']) ? $_GET['section'] : null);

if (isset($_GET['cat']) && !$cat) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

if ($cat) {
    $list  = products(['category_id' => $cat['id']]);
    $title = $cat['name'] . ' Johannesburg';
    $h1    = $cat['name'];
    $lead  = $cat['blurb'] . '. Choose your options, upload your artwork and get a quote from Extra Printers in Johannesburg.';
    $path  = 'shop/' . $cat['slug'];
} elseif ($q !== '') {
    $list  = products(['q' => $q]);
    $title = 'Search: ' . $q;
    $h1    = 'Results for "' . $q . '"';
    $lead  = count($list) . ' product' . (count($list) === 1 ? '' : 's') . ' found.';
    $path  = 'shop.php';
} elseif ($section) {
    $list  = products(['section' => $section]);
    [$label, $blurb] = SECTIONS[$section];
    $title = $label . ' – Printing & Paper in Johannesburg';
    $h1    = $label;
    $lead  = $blurb . '. Delivered across Gauteng or collected in Johannesburg.';
    $path  = 'shop.php?section=' . $section;
} else {
    $list  = products();
    $title = 'Shop all paper, printing & signage';
    $h1    = 'Shop everything';
    $lead  = 'Paper, printing, signage and design services. Add anything to your quote cart.';
    $path  = 'shop.php';
}

$crumbs = [['Home', CANONICAL_URL . '/']];
if ($section) $crumbs[] = [SECTIONS[$section][0], CANONICAL_URL . '/shop.php?section=' . $section];
if ($cat) $crumbs[] = [$cat['name'], CANONICAL_URL . '/shop/' . $cat['slug']];

siteOpen(['title' => $title . ' | Extra Printers', 'description' => mb_substr($lead, 0, 155), 'path' => $path, 'active' => $section ?? '',
          'noindex' => $q !== '', 'schema' => [schemaBreadcrumb($crumbs)]]);
$color = $cat['color'] ?? (SECTIONS[$section][3] ?? 'blue');
?>
<header class="phead">
  <div class="wrap">
    <?= breadcrumbsHtml(array_map(fn($c) => [$c[0], str_replace(CANONICAL_URL, APP_URL, $c[1])], $crumbs)) ?>
    <h1><?= e($h1) ?></h1>
    <p><?= e($lead) ?></p>
  </div>
</header>
<section class="section" style="padding-top:28px">
  <div class="wrap">
    <?php if ($section): ?>
      <nav class="filters" aria-label="Categories">
        <a href="<?= e(url('shop.php?section=' . $section)) ?>" class="<?= !$cat ? 'is-on' : '' ?>">All <?= e(strtolower(SECTIONS[$section][0])) ?></a>
        <?php foreach (categories($section) as $c): ?>
          <a href="<?= e(categoryUrl($c)) ?>" class="<?= ($cat['id'] ?? 0) === $c['id'] ? 'is-on' : '' ?>"><span class="dot dot--<?= e($c['color']) ?>"></span><?= e($c['name']) ?></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>
    <?php if ($list): ?>
      <div class="pgrid"><?php foreach ($list as $p): ?><?= productCard($p) ?><?php endforeach; ?></div>
    <?php else: ?>
      <div class="empty-state"><i class="fa-solid fa-magnifying-glass"></i><h2 style="margin:.6rem 0">Nothing found</h2>
        <p class="muted">Try another word, or WhatsApp us. If it can be printed, we can probably help.</p>
        <p style="margin-top:1rem"><a class="btn btn-wa" href="<?= e(waLink('Hi Extra Printers, do you do ' . $q . '?')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click"><i class="fa-brands fa-whatsapp"></i> Ask on WhatsApp</a></p></div>
    <?php endif; ?>
  </div>
</section>
<?php siteClose(); ?>
