<?php
declare(strict_types=1);
require_once __DIR__ . '/_shell.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$p = $id ? product((string) $id) : null;
if ($id && !$p) { flash('error', 'Product not found.'); redirect('admin/products.php'); }

/** "Size: A4 | A3 [custom above 10000]" lines ⇄ option groups. */
function optionsToText(array $groups): string
{
    return implode("\n", array_map(fn($g) => $g['label'] . ': ' . implode(' | ', $g['values']) . (isset($g['custom_above']) ? ' [custom above ' . $g['custom_above'] . ']' : ''), $groups));
}
function textToOptions(string $text): array
{
    $out = [];
    foreach (preg_split('/\R/', $text) as $line) {
        if (!str_contains($line, ':')) continue;
        [$label, $vals] = array_map('trim', explode(':', $line, 2));
        $custom = null;
        if (preg_match('/\[custom above (\d+)\]/i', $vals, $m)) { $custom = (int) $m[1]; $vals = trim(str_replace($m[0], '', $vals)); }
        $values = array_values(array_filter(array_map('trim', explode('|', $vals)), 'strlen'));
        if ($label === '' || !$values) continue;
        $g = ['key' => substr(slugify($label), 0, 30), 'label' => mb_substr($label, 0, 60), 'values' => $values];
        if ($custom) $g['custom_above'] = $custom;
        $out[] = $g;
    }
    return $out;
}

/** Resizes an uploaded photo to max 1200 px and saves it as WebP (JPEG if WebP is unavailable). */
function saveProductPhoto(array $f, string $slug): ?string
{
    if (($f['error'] ?? 1) !== UPLOAD_ERR_OK || $f['size'] > 15 * 1048576) return null;
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return null;
    $src = match ($info[2]) { IMAGETYPE_JPEG => @imagecreatefromjpeg($f['tmp_name']), IMAGETYPE_PNG => @imagecreatefrompng($f['tmp_name']), default => @imagecreatefromwebp($f['tmp_name']) };
    if (!$src) return null;
    [$w, $h] = [imagesx($src), imagesy($src)];
    $scale = min(1, 1200 / max($w, $h));
    $dst = imagecreatetruecolor((int) round($w * $scale), (int) round($h * $scale));
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
    if (!is_dir(PRODUCT_IMG_DIR)) mkdir(PRODUCT_IMG_DIR, 0775, true);
    $name = $slug . '-' . bin2hex(random_bytes(3));
    $ok = function_exists('imagewebp') ? imagewebp($dst, PRODUCT_IMG_DIR . ($name .= '.webp'), 82) : imagejpeg($dst, PRODUCT_IMG_DIR . ($name .= '.jpg'), 85);
    imagedestroy($src); imagedestroy($dst);
    return $ok ? $name : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? 'save';
    $images = $p ? (json_decode((string) $p['images'], true) ?: []) : [];

    if ($form === 'photo' && $p) {
        $act = $_POST['act'] ?? '';
        $file = basename((string) ($_POST['file'] ?? ''));
        if ($act === 'upload') {
            $added = 0;
            foreach ((array) ($_FILES['photos']['name'] ?? []) as $i => $_) {
                if (count($images) >= 5) break;
                $n = saveProductPhoto(['tmp_name' => $_FILES['photos']['tmp_name'][$i], 'error' => $_FILES['photos']['error'][$i], 'size' => $_FILES['photos']['size'][$i]], $p['slug']);
                if ($n) { $images[] = $n; $added++; }
            }
            flash($added ? 'success' : 'error', $added ? "{$added} photo(s) added." : 'No photo was added. Use JPG, PNG or WebP under 15 MB (max 5 photos).');
        } elseif ($act === 'main' && in_array($file, $images, true)) {
            $images = array_values(array_unique(array_merge([$file], $images)));
        } elseif ($act === 'delete' && in_array($file, $images, true)) {
            $images = array_values(array_diff($images, [$file]));
            @unlink(PRODUCT_IMG_DIR . $file);
        }
        getDB()->prepare('UPDATE products SET images = ? WHERE id = ?')->execute([json_encode($images), $p['id']]);
        redirect('admin/product.php?id=' . $p['id'] . '#photos');
    }

    if ($form === 'delete' && $p) {
        foreach ($images as $f) @unlink(PRODUCT_IMG_DIR . basename($f));
        getDB()->prepare('DELETE FROM products WHERE id = ?')->execute([$p['id']]);
        audit('product_deleted', $p['name']);
        flash('success', $p['name'] . ' deleted.');
        redirect('admin/products.php');
    }

    // Save
    $name = postText('name', 160);
    $slug = slugify(postText('slug', 120) ?: $name);
    $opts = textToOptions((string) ($_POST['options'] ?? ''));
    $dupe = getDB()->prepare('SELECT id FROM products WHERE slug = ? AND id <> ?');
    $dupe->execute([$slug, $p['id'] ?? 0]);
    $err = $name === '' ? 'Enter a product name.' : ($dupe->fetchColumn() ? 'Another product already uses that slug.' : '');
    if ($err) { flash('error', $err); redirect('admin/product.php' . ($p ? '?id=' . $p['id'] : '')); }
    $price = trim((string) ($_POST['price_from'] ?? '')) === '' ? null : (float) str_replace(',', '.', (string) $_POST['price_from']);
    $vals = [(int) $_POST['category_id'], $slug, $name, postText('tagline', 255), postText('description', 6000), json_encode($opts, JSON_UNESCAPED_UNICODE),
             $price, postText('price_note', 80), !empty($_POST['fixed_price']) ? 1 : 0, !empty($_POST['needs_artwork']) ? 1 : 0,
             !empty($_POST['featured']) ? 1 : 0, (int) ($_POST['sort'] ?? 0), !empty($_POST['is_active']) ? 1 : 0, postText('seo_title', 160), postText('seo_description', 255)];
    if ($p) {
        getDB()->prepare('UPDATE products SET category_id=?, slug=?, name=?, tagline=?, description=?, options=?, price_from=?, price_note=?, fixed_price=?, needs_artwork=?, featured=?, sort=?, is_active=?, seo_title=?, seo_description=? WHERE id=?')
            ->execute([...$vals, $p['id']]);
        $pid = (int) $p['id'];
    } else {
        getDB()->prepare('INSERT INTO products (category_id, slug, name, tagline, description, options, price_from, price_note, fixed_price, needs_artwork, featured, sort, is_active, seo_title, seo_description) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute($vals);
        $pid = (int) getDB()->lastInsertId();
    }
    audit($p ? 'product_saved' : 'product_added', $name);
    flash('success', 'Saved. ' . count($opts) . ' option group(s).');
    redirect('admin/product.php?id=' . $pid);
}

$p ??= ['id' => 0, 'category_id' => categories()[0]['id'], 'slug' => '', 'name' => '', 'tagline' => '', 'description' => '', 'options' => '[]', 'price_from' => null,
        'price_note' => '', 'fixed_price' => 0, 'needs_artwork' => 1, 'featured' => 0, 'sort' => 100, 'is_active' => 1, 'seo_title' => '', 'seo_description' => '', 'images' => '[]', 'color' => 'blue', 'icon' => 'fa-print'];
$images = json_decode((string) $p['images'], true) ?: [];
adminOpen('products', $p['id'] ? $p['name'] : 'Add product', $p['id'] ? '<a href="' . e(productUrl($p)) . '" target="_blank">View on site <i class="fa-solid fa-arrow-up-right-from-square"></i></a>' : 'New products appear on the site as soon as you save.');
?>
<div class="grid-2">
  <form method="post" class="card" id="pform">
    <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
    <div class="card__body">
      <div class="form-grid">
        <div class="field"><label>Name</label><input name="name" required value="<?= e($p['name']) ?>"></div>
        <div class="field"><label>Category</label><select name="category_id"><?php foreach (SECTIONS as $sk => [$sl]): ?><optgroup label="<?= e($sl) ?>"><?php foreach (categories($sk) as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $p['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></div>
      </div>
      <div class="field"><label>Tagline (one line on product cards)</label><input name="tagline" maxlength="255" value="<?= e($p['tagline']) ?>"></div>
      <div class="field"><label>Description <button type="button" class="btn btn-ghost btn-xs" id="aiBtn" <?= $p['id'] ? '' : 'disabled title="Save first"' ?>><i class="fa-solid fa-wand-magic-sparkles"></i> Write with AI</button></label>
        <textarea name="description" id="desc" rows="8"><?= e($p['description']) ?></textarea><p class="hint">Blank line = new paragraph.</p></div>
      <div class="field"><label>Options: one group per line</label>
        <textarea name="options" rows="6" style="font-family:ui-monospace,Consolas,monospace;font-size:.85rem"><?= e(optionsToText(productOptions($p))) ?></textarea>
        <p class="hint">Format: <code>Size: A4 | A3 | A2</code>. For a custom amount box add <code>[custom above 10000]</code> at the end of the quantity line.</p></div>
      <div class="form-grid">
        <div class="field"><label>"From" price (R), blank = Get a quote</label><input name="price_from" inputmode="decimal" value="<?= e($p['price_from'] ?? '') ?>"></div>
        <div class="field"><label>Price note</label><input name="price_note" placeholder="per artwork / per ream" value="<?= e($p['price_note']) ?>"></div>
      </div>
      <div class="chips" style="margin-bottom:1rem">
        <?php foreach (['is_active' => 'Show on site', 'featured' => 'Featured', 'needs_artwork' => 'Customer uploads artwork', 'fixed_price' => 'Fixed price (not quoted)'] as $k => $label): ?>
          <label class="chip-check"><input type="checkbox" name="<?= $k ?>" value="1" <?= $p[$k] ? 'checked' : '' ?>> <?= e($label) ?></label>
        <?php endforeach; ?>
      </div>
      <h3 style="font-size:1rem;margin:1rem 0 .5rem">Google</h3>
      <div class="field"><label>SEO title (max 60)</label><input name="seo_title" id="seoT" maxlength="160" value="<?= e($p['seo_title']) ?>" placeholder="<?= e($p['name'] ?: 'Product') ?> Johannesburg | Extra Printers"></div>
      <div class="field"><label>SEO description (max 155)</label><input name="seo_description" id="seoD" maxlength="255" value="<?= e($p['seo_description']) ?>"></div>
      <div class="form-grid"><div class="field"><label>Slug (web address)</label><input name="slug" value="<?= e($p['slug']) ?>"></div><div class="field"><label>Sort order</label><input name="sort" type="number" value="<?= (int) $p['sort'] ?>"></div></div>
    </div>
    <div class="form-foot card__body"><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save product</button></div>
  </form>

  <div>
    <section class="card" id="photos">
      <div class="card__head"><div><h2>Photos &amp; mockups</h2><p>Up to 5. The first is the main photo. 1200 × 1200 px is best.</p></div></div>
      <div class="card__body">
        <?php if ($images): ?>
          <div class="photo-grid">
            <?php foreach ($images as $i => $f): ?>
              <figure><img src="<?= e(APP_URL . '/uploads/products/' . rawurlencode($f)) ?>" alt="">
                <figcaption><?= $i === 0 ? '<span class="pill st-blue">Main</span>' : '<form method="post">' . csrfField() . '<input type="hidden" name="id" value="' . (int) $p['id'] . '"><input type="hidden" name="form" value="photo"><input type="hidden" name="act" value="main"><input type="hidden" name="file" value="' . e($f) . '"><button class="btn btn-ghost btn-xs">Make main</button></form>' ?>
                  <form method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="form" value="photo"><input type="hidden" name="act" value="delete"><input type="hidden" name="file" value="<?= e($f) ?>"><button class="icon-btn" title="Delete" style="width:28px;height:28px"><i class="fa-regular fa-trash-can"></i></button></form></figcaption></figure>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="max-width:240px;aspect-ratio:1;border-radius:14px;overflow:hidden;margin-bottom:.6rem"><?= productImageHtml($p) ?></div>
          <p class="hint">Showing <?= productImages($p) ? 'the photo from assets/products/' : 'the branded placeholder' ?>. Upload a photo to replace it.</p>
        <?php endif; ?>
        <?php if ($p['id'] && count($images) < 5): ?>
          <form method="post" enctype="multipart/form-data" style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
            <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="form" value="photo"><input type="hidden" name="act" value="upload">
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required class="input" style="flex:1">
            <button class="btn btn-primary"><i class="fa-solid fa-upload"></i> Upload</button>
          </form>
        <?php elseif (!$p['id']): ?><p class="hint">Save the product first, then add photos.</p><?php endif; ?>
      </div>
    </section>
    <?php if ($p['id']): ?>
      <form method="post" data-confirm="Delete <?= e($p['name']) ?>? Past quotes keep their copy of it.">
        <?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="form" value="delete">
        <button class="btn btn-ghost" style="color:var(--coral)"><i class="fa-regular fa-trash-can"></i> Delete product</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php adminClose('<script>
(function(){var b=document.getElementById("aiBtn");if(!b||b.disabled)return;b.onclick=function(){b.disabled=true;var h=b.innerHTML;b.innerHTML="<i class=\"fa-solid fa-spinner fa-spin\"></i> Writing…";
wfPost("admin/ai.php",{action:"product",id:' . (int) $p['id'] . '}).then(function(r){b.disabled=false;b.innerHTML=h;if(!r.ok){wfToast(r.error||"Failed");return;}
document.getElementById("desc").value=r.description;if(r.seo_title)document.getElementById("seoT").value=r.seo_title;if(r.seo_description)document.getElementById("seoD").value=r.seo_description;wfToast("Written. Check it, then Save.");});};})();
</script>'); ?>
