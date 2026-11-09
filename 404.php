<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
http_response_code(404);
siteOpen(['title' => 'Page not found | Extra Printers', 'noindex' => true]);
?>
<div class="wrap"><div class="done">
  <div class="done__ico"><i class="fa-solid fa-print"></i></div>
  <h1 style="font-size:2.2rem;margin-bottom:.6rem">This page ran out of ink</h1>
  <p class="muted">The page you're looking for doesn't exist or has moved.</p>
  <form action="<?= e(url('shop.php')) ?>" method="get" style="display:flex;gap:.5rem;max-width:420px;margin:1.4rem auto">
    <input class="input" type="search" name="q" placeholder="Search products…" aria-label="Search products"><button class="btn btn-primary">Search</button>
  </form>
  <p style="display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap"><a class="btn btn-ghost" href="<?= e(url('')) ?>">Home</a><a class="btn btn-ghost" href="<?= e(url('invoice-books-johannesburg')) ?>">Invoice books</a><a class="btn btn-ghost" href="<?= e(url('paper-supplier-johannesburg')) ?>">Paper</a></p>
</div></div>
<?php siteClose(); ?>
