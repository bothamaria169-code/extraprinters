<?php
declare(strict_types=1);
require_once __DIR__ . '/_shell.php';

$db = getDB();
$n  = fn(string $sql) => (int) $db->query($sql)->fetchColumn();
$new      = $n("SELECT COUNT(*) FROM quotes WHERE status = 'new'");
$month    = $n("SELECT COUNT(*) FROM quotes WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$working  = $n("SELECT COUNT(*) FROM quotes WHERE status IN ('approved','in_production','ready')");
$value    = (float) $db->query("SELECT COALESCE(SUM(quoted_total),0) FROM quotes WHERE status IN ('approved','in_production','ready','completed') AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
$chats    = $n("SELECT COUNT(DISTINCT sender) FROM chat_log WHERE direction = 'in' AND created_at >= CURDATE() - INTERVAL 7 DAY");

$days = [];
for ($i = 29; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-{$i} days"))] = 0;
foreach ($db->query('SELECT DATE(created_at) d, COUNT(*) c FROM quotes WHERE created_at >= CURDATE() - INTERVAL 29 DAY GROUP BY d') as $r) $days[$r['d']] = (int) $r['c'];
$byProduct = $db->query('SELECT product_name, COUNT(*) c FROM quote_items qi JOIN quotes q ON q.id = qi.quote_id WHERE q.created_at >= CURDATE() - INTERVAL 90 DAY GROUP BY product_name ORDER BY c DESC LIMIT 8')->fetchAll();
$bySource = $db->query('SELECT source, COUNT(*) c FROM quotes WHERE created_at >= CURDATE() - INTERVAL 90 DAY GROUP BY source ORDER BY c DESC LIMIT 6')->fetchAll();
$recent = $db->query('SELECT * FROM quotes ORDER BY created_at DESC LIMIT 8')->fetchAll();

$setup = [
    'Business details (address, phone, WhatsApp)' => biz('phone') !== '' && biz('whatsapp') !== '' && biz('street') !== '',
    'Email delivery (SMTP)'          => get_setting('smtp_pass', SMTP_PASS) !== '',
    'Google Analytics 4'             => get_setting('ga4_id') !== '',
    'Search Console verification'    => get_setting('gsc_code') !== '' || !IS_LOCAL,
    'Google Ads conversion tracking' => get_setting('gads_id') !== '' && get_setting('gads_label_lead') !== '',
    'AI writing (Groq)'              => aiKey() !== '',
    'Facebook + Instagram connected' => get_setting('meta_page_token') !== '',
    'WhatsApp chat bot'              => get_setting('wa_token') !== '' && get_setting('chatbot_on') === '1',
    'Meta Pixel'                     => get_setting('meta_pixel_id') !== '',
    'Product photos uploaded'        => (bool) array_filter(products(), fn($p) => productImages($p)),
];
$cron = get_setting('cron_last');
adminOpen('dashboard', 'Dashboard', 'Extra Printers at a glance' . ($cron ? ' · cron ran ' . e(timeAgo($cron)) : ' · <span style="color:var(--coral)">cron has not run yet (README step 2.7)</span>'),
    '<a class="btn btn-primary" href="quotes.php?status=new"><i class="fa-solid fa-inbox"></i> New quotes (' . $new . ')</a>');
?>
<div class="kpis">
  <div class="kpi"><div class="kpi__ico"><i class="fa-solid fa-inbox"></i></div><div><div class="kpi__label">New quote requests</div><div class="kpi__value"><?= $new ?></div><div class="kpi__delta"><a href="quotes.php?status=new">Reply now</a>, fast replies win jobs</div></div></div>
  <div class="kpi kpi--violet"><div class="kpi__ico"><i class="fa-solid fa-file-invoice"></i></div><div><div class="kpi__label">Requests this month</div><div class="kpi__value"><?= $month ?></div><div class="kpi__delta"><?= $working ?> jobs in progress</div></div></div>
  <div class="kpi kpi--mint"><div class="kpi__ico"><i class="fa-solid fa-sack-dollar"></i></div><div><div class="kpi__label">Approved this month</div><div class="kpi__value"><?= money($value) ?: 'R0' ?></div><div class="kpi__delta">From quotes marked approved or later</div></div></div>
  <div class="kpi kpi--amber"><div class="kpi__ico"><i class="fa-brands fa-whatsapp"></i></div><div><div class="kpi__label">Chats (7 days)</div><div class="kpi__value"><?= $chats ?></div><div class="kpi__delta"><a href="social.php#chat">Chat log</a></div></div></div>
</div>

<div class="grid-2">
  <section class="card">
    <div class="card__head"><div><h2>Quote requests, last 30 days</h2></div></div>
    <div class="card__body"><div class="chart-box"><canvas id="trend"></canvas></div></div>
  </section>
  <section class="card">
    <div class="card__head"><h2>Setup checklist</h2><a href="settings.php">Settings</a></div>
    <div class="card__body"><ul class="setup-list">
      <?php foreach ($setup as $label => $done): ?><li><i class="fa-solid <?= $done ? 'fa-circle-check' : 'fa-circle' ?>"></i> <?= e($label) ?></li><?php endforeach; ?>
    </ul><p class="hint" style="margin-top:.8rem">Step-by-step: docs/SETUP-GUIDE.md</p></div>
  </section>
</div>

<div class="grid-2 even">
  <section class="card">
    <div class="card__head"><h2>Most requested (90 days)</h2></div>
    <div class="card__body">
      <?php $max = max(1, (int) ($byProduct[0]['c'] ?? 1)); foreach ($byProduct as $r): ?>
        <div class="bar-row"><span><?= e($r['product_name']) ?></span><div class="track"><span style="width:<?= round($r['c'] / $max * 100) ?>%"></span></div><b><?= (int) $r['c'] ?></b></div>
      <?php endforeach; if (!$byProduct): ?><div class="empty"><i class="fa-solid fa-chart-simple"></i>No requests yet.</div><?php endif; ?>
    </div>
  </section>
  <section class="card">
    <div class="card__head"><h2>Where customers came from</h2></div>
    <div class="card__body">
      <?php $max = max(1, (int) ($bySource[0]['c'] ?? 1)); foreach ($bySource as $r): ?>
        <div class="bar-row"><span><?= e($r['source'] ?: 'Direct') ?></span><div class="track"><span style="width:<?= round($r['c'] / $max * 100) ?>%;background:var(--magenta)"></span></div><b><?= (int) $r['c'] ?></b></div>
      <?php endforeach; if (!$bySource): ?><div class="empty"><i class="fa-solid fa-route"></i>Sources show here (Google Ads, Facebook, Google search…).</div><?php endif; ?>
    </div>
  </section>
</div>

<section class="card">
  <div class="card__head"><h2>Latest requests</h2><a href="quotes.php">All quotes</a></div>
  <?php foreach ($recent as $q): ?>
    <div class="list-row"><div class="avatar"><?= e(strtoupper(mb_substr($q['name'], 0, 1))) ?></div>
      <div class="list-row__main"><strong><a href="quote.php?id=<?= (int) $q['id'] ?>"><?= e($q['ref']) ?> · <?= e($q['name']) ?></a></strong><small><?= e(QUOTE_TYPES[$q['type']]) ?><?= $q['company'] ? ' · ' . e($q['company']) : '' ?></small></div>
      <div class="list-row__side"><?= statusPill($q['status']) ?> <?= e(timeAgo($q['created_at'])) ?></div></div>
  <?php endforeach; if (!$recent): ?><div class="empty"><i class="fa-solid fa-inbox"></i>No quote requests yet. Test the flow: add a product, upload a file, send.</div><?php endif; ?>
</section>
<?php
$labels = array_map(fn($d) => date('j M', strtotime($d)), array_keys($days));
adminClose('<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script><script>
(function(){var css=getComputedStyle(document.documentElement);var c=function(v){return css.getPropertyValue(v).trim();};
new Chart(document.getElementById("trend"),{type:"bar",data:{labels:' . json_encode($labels) . ',datasets:[{label:"Requests",data:' . json_encode(array_values($days)) . ',backgroundColor:c("--brand"),borderRadius:4}]},
options:{maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{ticks:{color:c("--text-3"),maxTicksLimit:10},grid:{display:false}},y:{beginAtZero:true,ticks:{color:c("--text-3"),precision:0},grid:{color:c("--line")}}}}});})();
</script>');
