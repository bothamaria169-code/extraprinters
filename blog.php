<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 12;
$total = (int) getDB()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
$posts = getDB()->query("SELECT slug, title, excerpt, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT {$per} OFFSET " . (($page - 1) * $per))->fetchAll();

siteOpen(['title' => 'Printing Tips & Guides | Extra Printers Johannesburg', 'description' => 'Guides on paper types, invoice books, artwork, business cards and signage from the Extra Printers team in Johannesburg.',
          'path' => 'blog.php' . ($page > 1 ? '?page=' . $page : ''), 'active' => 'blog']);
?>
<header class="phead"><div class="wrap">
  <span class="eyebrow eyebrow--yellow"><i class="fa-solid fa-lightbulb"></i> From the print room</span>
  <h1>Printing tips &amp; guides</h1>
  <p>Paper weights, invoice book set-ups, artwork tips and more, so your next print job comes out right first time.</p>
</div></header>
<section class="section" style="padding-top:32px"><div class="wrap">
  <?php if ($posts): ?>
    <div class="post-cards">
      <?php foreach ($posts as $p): ?>
        <article class="post-card"><small><?= e(date('j M Y', strtotime($p['published_at']))) ?></small>
          <h2 style="font-size:1.15rem"><a href="<?= e(url('blog/' . $p['slug'])) ?>" style="color:var(--text)"><?= e($p['title']) ?></a></h2>
          <p><?= e($p['excerpt']) ?></p><a href="<?= e(url('blog/' . $p['slug'])) ?>" style="margin-top:auto;font-weight:700">Read <i class="fa-solid fa-arrow-right"></i></a></article>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:2rem"><?= pager($total, $page, $per, []) ?></div>
  <?php else: ?>
    <div class="empty-state"><i class="fa-solid fa-pen-fancy"></i><h2 style="margin:.6rem 0">Guides coming soon</h2><p class="muted">Our first printing guides are on the way.</p></div>
  <?php endif; ?>
</div></section>
<?php siteClose(); ?>
