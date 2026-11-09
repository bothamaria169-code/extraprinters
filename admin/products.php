<?php
declare(strict_types=1);
require_once __DIR__ . '/_shell.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'toggle') {
    $field = in_array($_POST['field'] ?? '', ['is_active', 'featured'], true) ? $_POST['field'] : 'is_active';
    getDB()->prepare("UPDATE products SET {$field} = 1 - {$field} WHERE id = ?")->execute([(int) $_POST['id']]);
    redirect('admin/products.php' . (!empty($_POST['section']) ? '?section=' . urlencode($_POST['section']) : ''));
}

$section = isset(SECTIONS[$_GET['section'] ?? '']) ? $_GET['section'] : '';
$sql = 'SELECT p.*, c.name AS cat_name, c.section, c.color, c.icon, (SELECT COUNT(*) FROM quote_items qi WHERE qi.product_id = p.id) AS requests
        FROM products p JOIN categories c ON c.id = p.category_id' . ($section ? ' WHERE c.section = ?' : '') . ' ORDER BY c.sort, p.sort';
$s = getDB()->prepare($sql);
$s->execute($section ? [$section] : []);
$rows = $s->fetchAll();
$noPhoto = count(array_filter($rows, fn($p) => !productImages($p)));

adminOpen('products', 'Products', count($rows) . ' products' . ($noPhoto ? " · <strong>{$noPhoto}</strong> still show the placeholder" : ''),
    '<a class="btn btn-primary" href="product.php"><i class="fa-solid fa-plus"></i> Add product</a>');
?>
<nav class="tabs" style="margin-bottom:1rem">
  <a href="products.php" class="<?= !$section ? 'is-on' : '' ?>">All</a>
  <?php foreach (SECTIONS as $k => [$label]): ?><a href="?section=<?= $k ?>" class="<?= $section === $k ? 'is-on' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
</nav>
<section class="card">
  <table class="tbl">
    <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Requests</th><th>Featured</th><th>On site</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
      <tr>
        <td data-label="Product"><div style="display:flex;gap:.7rem;align-items:center"><span class="thumb"><?= productImageHtml($p, 'thumb') ?></span>
          <div><a href="product.php?id=<?= (int) $p['id'] ?>"><strong><?= e($p['name']) ?></strong></a><br><small class="hint">slug: <?= e($p['slug']) ?></small></div></div></td>
        <td data-label="Category"><span class="pill st-<?= e($p['color'] === 'blue' ? 'blue' : $p['color']) ?>"><?= e($p['cat_name']) ?></span></td>
        <td data-label="Price"><?= $p['price_from'] !== null ? money($p['price_from']) . ' ' . e($p['price_note']) : '<span class="hint">Quote</span>' ?></td>
        <td data-label="Requests"><?= (int) $p['requests'] ?></td>
        <?php foreach (['featured', 'is_active'] as $f): ?>
          <td data-label="<?= $f === 'featured' ? 'Featured' : 'On site' ?>"><form method="post"><?= csrfField() ?><input type="hidden" name="form" value="toggle"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="field" value="<?= $f ?>"><input type="hidden" name="section" value="<?= e($section) ?>">
            <button class="tag <?= $p[$f] ? 'tag-on' : 'tag-off' ?>" style="border:0;cursor:pointer"><?= $p[$f] ? 'Yes' : 'No' ?></button></form></td>
        <?php endforeach; ?>
        <td><a class="btn btn-ghost btn-xs" href="product.php?id=<?= (int) $p['id'] ?>">Edit</a> <a class="btn btn-ghost btn-xs" target="_blank" href="<?= e(productUrl($p)) ?>"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<p class="hint"><i class="fa-solid fa-image"></i> Photos: Edit → Photos → Upload, or drop a file named <code>&lt;slug&gt;.jpg</code> into <code>assets/products/</code>. Best size 1200 × 1200 px.</p>
<?php adminClose(); ?>
