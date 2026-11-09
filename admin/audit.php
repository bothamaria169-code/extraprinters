<?php
declare(strict_types=1);
require_once __DIR__ . '/_shell.php';

$rows = getDB()->query('SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id = a.admin_id ORDER BY a.id DESC LIMIT 200')->fetchAll();
adminOpen('audit', 'Audit log', 'The last 200 admin actions');
?>
<section class="card">
  <table class="tbl"><thead><tr><th>When</th><th>Who</th><th>Action</th><th>Target</th><th>IP</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td data-label="When"><?= e(date('j M H:i', strtotime($r['created_at']))) ?></td><td data-label="Who"><?= e($r['email'] ?? '—') ?></td>
      <td data-label="Action"><?= e(str_replace('_', ' ', $r['action'])) ?></td><td data-label="Target"><?= e($r['target']) ?></td><td data-label="IP"><?= e($r['ip']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <?php if (!$rows): ?><div class="empty"><i class="fa-solid fa-clock-rotate-left"></i>Nothing yet.</div><?php endif; ?>
</section>
<?php adminClose(); ?>
