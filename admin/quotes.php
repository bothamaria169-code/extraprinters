<?php
declare(strict_types=1);
require_once __DIR__ . '/_shell.php';

$status = isset(QUOTE_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$type   = isset(QUOTE_TYPES[$_GET['type'] ?? '']) ? $_GET['type'] : '';
$q      = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 80);
$page   = max(1, (int) ($_GET['page'] ?? 1));
$per    = 25;

$where = ['1=1']; $args = [];
if ($status) { $where[] = 'q.status = ?'; $args[] = $status; }
if ($type)   { $where[] = 'q.type = ?'; $args[] = $type; }
if ($q !== '') { $where[] = '(q.ref LIKE ? OR q.name LIKE ? OR q.email LIKE ? OR q.phone LIKE ? OR q.company LIKE ?)'; array_push($args, ...array_fill(0, 5, '%' . $q . '%')); }
$w = implode(' AND ', $where);
$c = getDB()->prepare("SELECT COUNT(*) FROM quotes q WHERE $w"); $c->execute($args); $total = (int) $c->fetchColumn();
$s = getDB()->prepare("SELECT q.*, (SELECT GROUP_CONCAT(product_name SEPARATOR ', ') FROM quote_items WHERE quote_id = q.id) AS items,
                       (SELECT COUNT(*) FROM quote_files WHERE quote_id = q.id) AS files FROM quotes q WHERE $w ORDER BY q.created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$s->execute($args);
$rows = $s->fetchAll();
$counts = getDB()->query('SELECT status, COUNT(*) c FROM quotes GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

adminOpen('quotes', 'Quotes & orders', number_format($total) . ' request' . ($total === 1 ? '' : 's'));
?>
<nav class="tabs" style="margin-bottom:1rem">
  <a href="quotes.php" class="<?= !$status ? 'is-on' : '' ?>">All</a>
  <?php foreach (QUOTE_STATUSES as $k => $label): ?><a href="?status=<?= $k ?>" class="<?= $status === $k ? 'is-on' : '' ?>"><?= e($label) ?> <small><?= (int) ($counts[$k] ?? 0) ?></small></a><?php endforeach; ?>
</nav>
<form class="toolbar" method="get">
  <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
  <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Ref, name, email, phone, company">
  <select class="input select-sm" name="type" onchange="this.form.submit()"><option value="">All types</option><?php foreach (QUOTE_TYPES as $k => $label): ?><option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
  <button class="btn btn-ghost">Filter</button>
</form>
<section class="card">
  <table class="tbl">
    <thead><tr><th>Ref</th><th>Customer</th><th>Products</th><th>Files</th><th>Status</th><th>Quote</th><th>Received</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td data-label="Ref"><a href="quote.php?id=<?= (int) $r['id'] ?>"><strong><?= e($r['ref']) ?></strong></a><br><small class="hint"><?= e(QUOTE_TYPES[$r['type']]) ?></small></td>
        <td data-label="Customer"><?= e($r['name']) ?><?= $r['company'] ? '<br><small class="hint">' . e($r['company']) . '</small>' : '' ?></td>
        <td data-label="Products"><?= e(excerpt($r['items'], 70)) ?></td>
        <td data-label="Files"><?= (int) $r['files'] ?><?= $r['file_link'] ? ' + link' : '' ?></td>
        <td data-label="Status"><?= statusPill($r['status']) ?></td>
        <td data-label="Quote"><?= $r['quoted_total'] !== null ? money($r['quoted_total']) : '—' ?></td>
        <td data-label="Received"><?= e(timeAgo($r['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (!$rows): ?><div class="empty"><i class="fa-solid fa-inbox"></i>No requests match.</div><?php endif; ?>
</section>
<?= pager($total, $page, $per, array_filter(['status' => $status, 'type' => $type, 'q' => $q])) ?>
<?php adminClose(); ?>
