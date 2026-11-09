<?php
declare(strict_types=1);
require_once __DIR__ . '/_shell.php';
require_once BASE_DIR . '/includes/blog.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';
    if ($form === 'auto') {
        set_setting('blog_auto', !empty($_POST['blog_auto']) ? '1' : '0');
        set_setting('blog_days', implode(',', array_intersect((array) ($_POST['days'] ?? []), ['1', '2', '3', '4', '5', '6', '7'])));
        set_setting('blog_keywords', mb_substr(trim((string) $_POST['blog_keywords']), 0, 8000));
        flash('success', 'Blog schedule saved.');
    } elseif ($form === 'write') {
        set_time_limit(120);
        $kw = postText('keyword', 160);
        $slug = preg_replace('/[^a-z0-9-]/', '', (string) ($_POST['product'] ?? ''));
        $r = blogWriteNext($kw !== '' ? $kw . '|' . $slug : null);
        flash($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Published: ' . $r['title'] : 'Could not write it: ' . $r['error']);
    } elseif ($form === 'toggle') {
        getDB()->prepare("UPDATE posts SET status = IF(status = 'published', 'draft', 'published') WHERE id = ?")->execute([(int) $_POST['id']]);
    } elseif ($form === 'delete') {
        getDB()->prepare('DELETE FROM posts WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Article deleted.');
    }
    audit('blog_' . $form);
    redirect('admin/blog.php');
}

$posts = getDB()->query('SELECT * FROM posts ORDER BY published_at DESC')->fetchAll();
$used = array_column($posts, 'keyword');
$days = array_filter(explode(',', get_setting('blog_days', '2,5')));
adminOpen('blog', 'Blog (AI)', 'Helpful articles on your keywords bring Google visitors. AI writes them from your products; read each one and edit or unpublish anything off.');
?>
<div class="grid-2 even">
  <section class="card">
    <div class="card__head"><h2>Write one now</h2></div>
    <form method="post" class="card__body" data-busy><?= csrfField() ?><input type="hidden" name="form" value="write">
      <div class="field"><label>Keyword (blank = next on the list)</label><input name="keyword" placeholder="e.g. invoice book sizes explained"></div>
      <div class="field"><label>Product to link</label><select name="product"><option value="">None</option><?php foreach (products() as $p): ?><option value="<?= e($p['slug']) ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn-primary" <?= aiEnabled() ? '' : 'disabled' ?>><i class="fa-solid fa-wand-magic-sparkles"></i> Write &amp; publish</button>
      <?php if (!aiEnabled()): ?><p class="hint">Add a Groq key in Settings → AI writing first.</p><?php endif; ?>
    </form>
  </section>
  <section class="card">
    <div class="card__head"><h2>Automatic articles</h2></div>
    <form method="post" class="card__body"><?= csrfField() ?><input type="hidden" name="form" value="auto">
      <label class="chip-check" style="margin-bottom:.8rem"><input type="checkbox" name="blog_auto" value="1" <?= get_setting('blog_auto') === '1' ? 'checked' : '' ?>> Publish automatically (after 09:00)</label>
      <div class="chips" style="margin-bottom:.8rem"><?php foreach (['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '7' => 'Sun'] as $k => $d): ?><label class="chip-check"><input type="checkbox" name="days[]" value="<?= $k ?>" <?= in_array((string) $k, $days, true) ? 'checked' : '' ?>> <?= $d ?></label><?php endforeach; ?></div>
      <div class="field"><label>Keyword list: one per line, "keyword | product-slug". Used keywords are skipped.</label>
        <textarea name="blog_keywords" rows="9" style="font-family:ui-monospace,Consolas,monospace;font-size:.82rem"><?= e(implode("\n", blogKeywords())) ?></textarea></div>
      <button class="btn btn-primary">Save</button>
    </form>
  </section>
</div>
<section class="card">
  <div class="card__head"><h2>Articles (<?= count($posts) ?>)</h2></div>
  <table class="tbl"><thead><tr><th>Title</th><th>Keyword</th><th>Published</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($posts as $p): ?>
    <tr><td data-label="Title"><a href="<?= e(url('blog/' . $p['slug'])) ?>" target="_blank"><?= e($p['title']) ?></a></td><td data-label="Keyword"><?= e($p['keyword']) ?></td>
      <td data-label="Published"><?= e(date('j M Y', strtotime($p['published_at']))) ?></td>
      <td data-label="Status"><form method="post"><?= csrfField() ?><input type="hidden" name="form" value="toggle"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="tag <?= $p['status'] === 'published' ? 'tag-on' : 'tag-off' ?>" style="border:0;cursor:pointer"><?= e(ucfirst($p['status'])) ?></button></form></td>
      <td><form method="post" data-confirm="Delete this article?"><?= csrfField() ?><input type="hidden" name="form" value="delete"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="icon-btn"><i class="fa-regular fa-trash-can"></i></button></form></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <?php if (!$posts): ?><div class="empty"><i class="fa-solid fa-pen-fancy"></i>No articles yet.</div><?php endif; ?>
</section>
<?php adminClose(); ?>
