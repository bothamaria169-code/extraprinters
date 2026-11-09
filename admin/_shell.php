<?php
declare(strict_types=1);

/**
 * Admin shell (SWIFTDNA): sidebar, top bar and page header. Pages handle
 * their POSTs first (which redirect), then call adminOpen() … adminClose().
 */
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/layout.php';
require_once dirname(__DIR__) . '/includes/quotes.php';
require_once dirname(__DIR__) . '/includes/ai_writers.php';

startAppSession();
requireAdmin();
$ADMIN = getCurrentUser();
if ($_SERVER['REQUEST_METHOD'] === 'POST') requireCsrf();

function adminOpen(string $active, string $title, string $sub = '', string $actions = ''): void
{
    global $ADMIN;
    $newQuotes = (int) getDB()->query("SELECT COUNT(*) FROM quotes WHERE status = 'new'")->fetchColumn();
    $nav = [
        'Shop' => [
            ['dashboard', 'index.php',    'fa-gauge-high',   'Dashboard', 0],
            ['quotes',    'quotes.php',   'fa-file-invoice', 'Quotes & orders', $newQuotes],
            ['products',  'products.php', 'fa-box-open',     'Products', 0],
        ],
        'Marketing' => [
            ['blog',      'blog.php',     'fa-pen-fancy',    'Blog (AI)', 0],
            ['social',    'social.php',   'fa-share-nodes',  'Social & bots', 0],
        ],
        'Setup' => [
            ['settings',  'settings.php', 'fa-sliders',      'Settings', 0],
            ['audit',     'audit.php',    'fa-clock-rotate-left', 'Audit log', 0],
        ],
    ];
    $name = $ADMIN['full_name'] ?: $ADMIN['email'];
    pageHead($title . ' – Admin – ' . APP_NAME, ['css' => ['style.css', 'admin.css', 'site.css']]);
    ?>
<body class="adm">
<div class="adm-scrim" id="admScrim"></div>
<aside class="adm-side" id="admSide">
  <?= brandMark('brand--light') ?>
  <?php foreach ($nav as $group => $links): ?>
    <div class="adm-side__label"><?= e($group) ?></div>
    <?php foreach ($links as [$slug, $href, $icon, $label, $count]): ?>
      <a href="<?= $href ?>" class="<?= $active === $slug ? 'is-active' : '' ?>">
        <i class="fa-solid <?= $icon ?>"></i><span><?= e($label) ?></span>
        <?php if ($count): ?><b class="n"><?= (int) $count ?></b><?php endif; ?>
      </a>
    <?php endforeach; ?>
  <?php endforeach; ?>
  <div class="adm-side__foot">
    <a href="<?= APP_URL ?>/" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>View website</span></a>
  </div>
</aside>

<div class="adm-main">
  <header class="adm-top">
    <button class="icon-btn adm-burger" id="admBurger" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    <form class="adm-search" action="quotes.php" method="get" role="search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" placeholder="Search quotes by ref, name, email or phone…" value="<?= e($_GET['q'] ?? '') ?>">
    </form>
    <div class="adm-top__right">
      <button class="icon-btn" id="themeBtn" title="Switch light / dark"><i class="fa-solid fa-circle-half-stroke"></i></button>
      <div class="adm-me">
        <div class="avatar"><?= e(strtoupper(mb_substr($name, 0, 1))) ?></div>
        <div class="adm-me__txt"><strong><?= e($name) ?></strong><small>Administrator</small></div>
      </div>
      <form method="POST" action="<?= APP_URL ?>/logout.php"><?= csrfField() ?><button class="icon-btn" title="Sign out"><i class="fa-solid fa-arrow-right-from-bracket"></i></button></form>
    </div>
  </header>

  <main class="adm-body">
    <div class="adm-head">
      <div><h1><?= e($title) ?></h1><?php if ($sub): ?><p><?= $sub ?></p><?php endif; ?></div>
      <?php if ($actions): ?><div class="adm-head__actions"><?= $actions ?></div><?php endif; ?>
    </div>
    <?= flashHtml() ?>
<?php
}

function adminClose(string $scripts = ''): void
{
    ?>
  </main>
</div>
<div class="toast" id="toast"></div>
<script>window.WF = { url: <?= json_encode(APP_URL) ?>, csrf: <?= json_encode(csrfToken()) ?> };</script>
<script src="<?= assetV('js/app.js') ?>"></script>
<?= $scripts ?>
</body>
</html>
<?php
}

/** Copy-able value box, used for the URLs Google and Meta ask for. */
function copyBox(string $label, string $value): string
{
    return '<div class="copy-box"><small>' . e($label) . '</small><code>' . e($value) . '</code>'
         . '<button type="button" class="icon-btn" data-copy="' . e($value) . '" title="Copy"><i class="fa-regular fa-copy"></i></button></div>';
}

function statusPill(string $status): string
{
    $c = ['new' => 'magenta', 'quoted' => 'cyan', 'approved' => 'blue', 'in_production' => 'yellow', 'ready' => 'mint', 'completed' => 'mint', 'cancelled' => 'off'][$status] ?? 'off';
    return '<span class="pill st-' . $c . '">' . e(QUOTE_STATUSES[$status] ?? $status) . '</span>';
}
