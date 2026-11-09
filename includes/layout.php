<?php
declare(strict_types=1);

require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/tracking.php';
require_once __DIR__ . '/seo.php';

function assetV(string $path): string
{
    return APP_URL . '/assets/' . $path . '?v=' . (string) @filemtime(BASE_DIR . '/assets/' . $path);
}

function fontsAndIcons(): string
{
    return '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
         . '<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">'
         . '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">'
         . '<link rel="icon" href="' . APP_URL . '/favicon.ico" sizes="any"><link rel="icon" type="image/png" href="' . APP_URL . '/assets/img/mark-192.png"><link rel="apple-touch-icon" href="' . APP_URL . '/assets/img/mark-192.png">'
         . '<script>try{var t=localStorage.getItem("xp-theme");if(t)document.documentElement.dataset.theme=t;}catch(e){}</script>';
}

/** Minimal <head> for admin and sign-in pages (never indexed). */
function pageHead(string $title, array $opt = []): void
{
    ?>
<!DOCTYPE html>
<html lang="en-ZA">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e($title) ?></title>
  <meta name="robots" content="noindex, nofollow">
  <?= fontsAndIcons() ?>
  <?php foreach ($opt['css'] ?? ['style.css'] as $file): ?><link rel="stylesheet" href="<?= assetV('css/' . $file) ?>"><?php endforeach; ?>
</head>
<?php
}

function brandMark(string $class = ''): string
{
    return '<a href="' . APP_URL . '/" class="brand ' . e($class) . '" aria-label="Extra Printers home">'
         . '<img src="' . APP_URL . '/assets/img/mark.webp" alt="" class="brand__mark" width="40" height="40">'
         . '<span class="brand__word"><b>E<span class="x">X</span>TRA</b><small>PRINTERS</small></span></a>';
}

function flashHtml(): string
{
    $out = '';
    foreach (takeFlashes() as [$type, $msg]) {
        $icon = $type === 'error' ? 'fa-circle-exclamation' : ($type === 'success' ? 'fa-circle-check' : 'fa-circle-info');
        $out .= '<div class="alert alert-' . e($type) . '"><i class="fa-solid ' . $icon . '"></i><span>' . e($msg) . '</span></div>';
    }
    return $out;
}

/**
 * Public page: SEO head, header and navigation.
 * @param array{title: string, description?: string, path?: string, image?: string, schema?: array, noindex?: bool, active?: string, type?: string} $o
 */
function siteOpen(array $o): void
{
    startAppSession();
    captureAttribution();
    $title = $o['title'];
    $desc  = $o['description'] ?? 'Extra Printers: paper supplier and printing company in Johannesburg. Invoice books, NCR paper, business cards, flyers, banners and signage. Upload your artwork and get a quote.';
    $canon = CANONICAL_URL . '/' . ltrim($o['path'] ?? '', '/');
    $img   = $o['image'] ?? CANONICAL_URL . '/assets/img/og-default.jpg';
    $schema = array_merge([schemaLocalBusiness()], $o['schema'] ?? []);
    $gsc = preg_replace('/[^\w-]/', '', get_setting('gsc_code'));
    $active = $o['active'] ?? '';
    ?>
<!DOCTYPE html>
<html lang="en-ZA">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($desc) ?>">
  <link rel="canonical" href="<?= e($canon) ?>">
  <?php if (!empty($o['noindex'])): ?><meta name="robots" content="noindex, follow"><?php else: ?><meta name="robots" content="index, follow, max-image-preview:large"><?php endif; ?>
  <meta name="geo.region" content="ZA-GP"><meta name="geo.placename" content="Johannesburg">
  <meta name="theme-color" content="#0a4ff0">
  <meta property="og:site_name" content="Extra Printers">
  <meta property="og:locale" content="en_ZA">
  <meta property="og:type" content="<?= e($o['type'] ?? 'website') ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($desc) ?>">
  <meta property="og:url" content="<?= e($canon) ?>">
  <meta property="og:image" content="<?= e($img) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <?php if ($gsc): ?><meta name="google-site-verification" content="<?= e($gsc) ?>"><?php endif; ?>
  <?= fontsAndIcons() ?>
  <link rel="stylesheet" href="<?= assetV('css/style.css') ?>">
  <link rel="stylesheet" href="<?= assetV('css/site.css') ?>">
  <?php foreach ($schema as $s): ?><script type="application/ld+json"><?= json_encode($s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script><?php endforeach; ?>
  <?= trackingHead() ?>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<div class="cmyk-bar" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
<div class="topbar">
  <div class="wrap topbar__in">
    <span><i class="fa-solid fa-location-dot"></i> <?= e(biz('city')) ?> · collection or delivery across Gauteng</span>
    <span class="topbar__right">
      <?php if (biz('phone')): ?><a href="<?= e(telLink()) ?>"><i class="fa-solid fa-phone"></i> <?= e(biz('phone')) ?></a><?php endif; ?>
      <a href="<?= e(url('artwork-guidelines.php')) ?>"><i class="fa-solid fa-palette"></i> Artwork guidelines</a>
      <a href="<?= e(url('request.php?type=design')) ?>"><i class="fa-solid fa-pen-nib"></i> Design for R300</a>
    </span>
  </div>
</div>
<header class="site-head" id="siteHead">
  <div class="wrap site-head__in">
    <?= brandMark() ?>
    <nav class="mainnav" id="mainNav" aria-label="Main">
      <?php foreach (SECTIONS as $key => [$label, $blurb, $icon, $color]): ?>
        <div class="mainnav__item <?= $active === $key ? 'is-active' : '' ?>">
          <a href="<?= e(url('shop.php?section=' . $key)) ?>" class="mainnav__link mainnav__link--<?= $color ?>"><?= e($label) ?> <i class="fa-solid fa-chevron-down"></i></a>
          <div class="mega mega--<?= $color ?>">
            <div class="mega__intro"><i class="fa-solid <?= $icon ?>"></i><strong><?= e($label) ?></strong><p><?= e($blurb) ?></p><a href="<?= e(url('shop.php?section=' . $key)) ?>">View all <i class="fa-solid fa-arrow-right"></i></a></div>
            <div class="mega__cols">
              <?php foreach (categories($key) as $c): ?>
                <div>
                  <a class="mega__cat" href="<?= e(categoryUrl($c)) ?>"><i class="fa-solid <?= e($c['icon']) ?> c-<?= e($c['color']) ?>"></i> <?= e($c['name']) ?></a>
                  <?php if ($key !== 'paper'): foreach (products(['category_id' => $c['id']]) as $p): ?>
                    <a class="mega__prod" href="<?= e(productUrl($p)) ?>"><?= e($p['name']) ?></a>
                  <?php endforeach; endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <a href="<?= e(url('blog.php')) ?>" class="mainnav__link mainnav__plain <?= $active === 'blog' ? 'is-active' : '' ?>">Tips</a>
      <a href="<?= e(url('contact.php')) ?>" class="mainnav__link mainnav__plain <?= $active === 'contact' ? 'is-active' : '' ?>">Contact</a>
    </nav>
    <div class="site-head__right">
      <form class="hsearch" action="<?= e(url('shop.php')) ?>" method="get" role="search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="q" placeholder="Search paper, invoice books…" aria-label="Search products" value="<?= e($_GET['q'] ?? '') ?>">
      </form>
      <button class="icon-btn" id="themeBtn" title="Light / dark" aria-label="Switch light or dark theme"><i class="fa-solid fa-circle-half-stroke"></i></button>
      <a href="<?= e(url('cart.php')) ?>" class="cart-btn" aria-label="Quote cart"><i class="fa-solid fa-cart-shopping"></i><span>Quote cart</span><b id="cartCount" class="<?= cartCount() ? '' : 'is-empty' ?>"><?= cartCount() ?></b></a>
      <button class="icon-btn nav-burger" id="navBurger" aria-label="Menu" aria-expanded="false" aria-controls="mainNav"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</header>
<main id="main">
<?php
}

function siteClose(string $scripts = ''): void
{
    $wa = waLink("Hi Extra Printers, I'd like a quote.");
    ?>
</main>
<section class="cta-strip">
  <div class="wrap cta-strip__in">
    <div><h2>Got a print job? Let's quote it.</h2><p>Add products to your quote cart and upload your artwork, or just WhatsApp us a photo of what you need.</p></div>
    <div class="cta-strip__btns">
      <a href="<?= e(url('cart.php')) ?>" class="btn btn-light btn-lg"><i class="fa-solid fa-file-invoice"></i> Start a quote</a>
      <a href="<?= e($wa) ?>" class="btn btn-wa btn-lg" target="_blank" rel="noopener" data-track="whatsapp_click"><i class="fa-brands fa-whatsapp"></i> WhatsApp us</a>
    </div>
  </div>
</section>
<footer class="site-foot">
  <div class="cmyk-bar" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
  <div class="wrap site-foot__grid">
    <div class="site-foot__about">
      <?= brandMark('brand--light') ?>
      <p>Paper supplier and printing company in <?= e(biz('city')) ?>. Invoice books, NCR paper, business stationery, marketing print and signage.</p>
      <address>
        <strong><?= e(biz('name')) ?></strong><br>
        <?php if (bizAddress()): ?><?= e(bizAddress()) ?><br><?php endif; ?>
        <?php if (biz('phone')): ?><a href="<?= e(telLink()) ?>"><?= e(biz('phone')) ?></a><br><?php endif; ?>
        <a href="mailto:<?= e(biz('email')) ?>"><?= e(biz('email')) ?></a><br>
        <span><?= e(biz('hours')) ?></span>
      </address>
      <div class="socials">
        <?php foreach (['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'tiktok' => 'fa-tiktok', 'linkedin' => 'fa-linkedin-in'] as $k => $ic): if (biz($k)): ?>
          <a href="<?= e(biz($k)) ?>" target="_blank" rel="noopener" aria-label="<?= ucfirst($k) ?>"><i class="fa-brands <?= $ic ?>"></i></a>
        <?php endif; endforeach; ?>
      </div>
    </div>
    <?php foreach (['paper' => 'Paper', 'printing' => 'Printing'] as $sec => $label): ?>
      <div><h3><?= $label ?></h3>
        <?php foreach (products(['section' => $sec, 'limit' => 9]) as $p): ?><a href="<?= e(productUrl($p)) ?>"><?= e($p['name']) ?></a><?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <div><h3>Signage & services</h3>
      <?php foreach (array_merge(products(['section' => 'signage', 'limit' => 5]), products(['section' => 'services'])) as $p): ?><a href="<?= e(productUrl($p)) ?>"><?= e($p['name']) ?></a><?php endforeach; ?>
    </div>
    <div><h3>Help</h3>
      <a href="<?= e(url('artwork-guidelines.php')) ?>">Artwork guidelines</a>
      <a href="<?= e(url('request.php?type=design')) ?>">Request a design</a>
      <a href="<?= e(url('request.php?type=installation')) ?>">Signage installation</a>
      <a href="<?= e(url('invoice-books-johannesburg')) ?>">Invoice books Johannesburg</a>
      <a href="<?= e(url('paper-supplier-johannesburg')) ?>">Paper supplier Johannesburg</a>
      <a href="<?= e(url('blog.php')) ?>">Printing tips</a>
      <a href="<?= e(url('contact.php')) ?>">Contact us</a>
    </div>
  </div>
  <div class="wrap site-foot__legal">
    <span>© <?= date('Y') ?> <?= e(biz('name')) ?> · Printing in <?= e(biz('city')) ?>, South Africa</span>
    <span><a href="<?= e(url('privacy.php')) ?>">Privacy (POPIA)</a> · <a href="<?= e(url('terms.php')) ?>">Terms</a></span>
  </div>
</footer>
<a class="wa-float" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp" data-track="whatsapp_click"><i class="fa-brands fa-whatsapp"></i></a>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>window.XP = { url: <?= json_encode(APP_URL) ?>, csrf: <?= json_encode(csrfToken()) ?>, maxMb: <?= MAX_ARTWORK_MB ?> };</script>
<script src="<?= assetV('js/site.js') ?>" defer></script>
<?= $scripts ?>
</body>
</html>
<?php
}
