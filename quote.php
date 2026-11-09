<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/quotes.php';

$q = findQuote((string) ($_GET['ref'] ?? ''), (string) ($_GET['t'] ?? ''));
if (!$q) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$items = quoteItems((int) $q['id']);
$files = quoteFiles((int) $q['id']);
$new   = isset($_GET['new']);
$flow  = ['new', 'quoted', 'approved', 'in_production', 'ready', 'completed'];
$at    = array_search($q['status'], $flow, true);

siteOpen(['title' => 'Request ' . $q['ref'] . ' | Extra Printers', 'path' => 'quote.php', 'noindex' => true]);
?>
<div class="wrap">
  <div class="done" <?= $new ? 'data-lead-ref="' . e($q['ref']) . '"' : '' ?>>
    <div class="done__ico"><i class="fa-solid <?= $new ? 'fa-check' : 'fa-file-invoice' ?>"></i></div>
    <h1 style="font-size:clamp(1.8rem,4vw,2.5rem);margin-bottom:.6rem"><?= $new ? 'Request sent, thank you!' : 'Your request' ?></h1>
    <p class="muted">Reference <span class="ref"><?= e($q['ref']) ?></span></p>
    <?php if ($new): ?><p style="margin-top:1rem">We've emailed a copy to <strong><?= e($q['email']) ?></strong>. We'll check your files and send your quote shortly. Bookmark this page to check its progress.</p><?php endif; ?>

    <?php if ($q['status'] === 'cancelled'): ?>
      <div class="alert alert-error" style="margin-top:1.4rem"><i class="fa-solid fa-ban"></i><span>This request was cancelled. WhatsApp us if that's a mistake.</span></div>
    <?php else: ?>
      <div class="status-steps"><?php foreach ($flow as $i => $st): ?><span class="<?= $at !== false && $i <= $at ? 'is-done' : '' ?>"><?= e(QUOTE_STATUSES[$st]) ?></span><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if ($q['quoted_total'] !== null): ?>
      <div class="panel" style="text-align:left;margin:1rem 0">
        <span class="eyebrow eyebrow--magenta"><i class="fa-solid fa-tag"></i> Your quote</span>
        <div class="price-big" style="margin:.7rem 0"><?= money($q['quoted_total']) ?></div>
        <?php if ($q['quote_message']): ?><p class="muted"><?= nl2br(e($q['quote_message'])) ?></p><?php endif; ?>
        <p style="margin-top:1rem"><a class="btn btn-wa" href="<?= e(waLink("Hi Extra Printers, I approve quote {$q['ref']} for " . money($q['quoted_total']) . '.')) ?>" target="_blank" rel="noopener" data-track="whatsapp_click"><i class="fa-brands fa-whatsapp"></i> Approve on WhatsApp</a></p>
      </div>
    <?php endif; ?>
  </div>

  <div class="form-card" style="max-width:760px;margin:0 auto 64px">
    <h2 style="font-size:1.2rem;margin-bottom:.8rem"><?= e(QUOTE_TYPES[$q['type']]) ?> · <?= e(date('j M Y, H:i', strtotime($q['created_at']))) ?></h2>
    <?php foreach ($items as $it): $opts = json_decode((string) $it['options'], true) ?: []; ?>
      <div style="padding:.8rem 0;border-bottom:1px solid var(--line)">
        <strong><?= e($it['product_name']) ?></strong>
        <div class="line__opts"><?php foreach ($opts as $k => $v): ?><span><b><?= e($k) ?>:</b> <?= e($v) ?></span><?php endforeach; ?></div>
        <?php if ($it['notes']): ?><p class="small muted"><?= e($it['notes']) ?></p><?php endif; ?>
        <?php $mine = array_filter($files, fn($f) => (int) $f['item_id'] === (int) $it['id']); if ($mine): ?>
          <p class="small"><i class="fa-solid fa-paperclip"></i> <?= e(implode(', ', array_column($mine, 'orig_name'))) ?></p>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if ($q['notes']): ?><p style="margin-top:.8rem" class="muted"><strong>Message:</strong> <?= nl2br(e($q['notes'])) ?></p><?php endif; ?>
    <p style="margin-top:1rem" class="small muted"><?= $q['delivery'] === 'deliver' ? 'Delivery to ' . e($q['address']) : 'Collection in ' . e(biz('city')) ?><?= $q['needed_by'] ? ' · needed by ' . e(date('j M Y', strtotime($q['needed_by']))) : '' ?></p>
    <p style="margin-top:1.2rem;display:flex;gap:.6rem;flex-wrap:wrap">
      <a class="btn btn-wa" href="<?= e(waLink("Hi Extra Printers, about my request {$q['ref']}")) ?>" target="_blank" rel="noopener" data-track="whatsapp_click"><i class="fa-brands fa-whatsapp"></i> WhatsApp us</a>
      <a class="btn btn-ghost" href="<?= e(url('')) ?>"><i class="fa-solid fa-house"></i> Back to shop</a>
    </p>
  </div>
</div>
<?php siteClose(); ?>
