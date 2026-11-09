<?php
declare(strict_types=1);
require_once __DIR__ . '/_shell.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$s = getDB()->prepare('SELECT * FROM quotes WHERE id = ?');
$s->execute([$id]);
$q = $s->fetch();
if (!$q) { flash('error', 'Quote not found.'); redirect('admin/quotes.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';
    if ($form === 'quote') {
        $total = (float) str_replace([' ', ','], ['', '.'], (string) ($_POST['quoted_total'] ?? ''));
        if ($total <= 0) { flash('error', 'Enter the quote amount.'); redirect('admin/quote.php?id=' . $id); }
        getDB()->prepare("UPDATE quotes SET quoted_total = ?, quote_message = ?, status = IF(status = 'new', 'quoted', status) WHERE id = ?")
            ->execute([$total, postText('quote_message', 3000), $id]);
        $s->execute([$id]);
        $q = $s->fetch();
        $err = !empty($_POST['send']) ? sendPricedQuote($q) : '';
        audit('quote_priced', $q['ref'], money($total));
        flash($err ? 'error' : 'success', $err ? 'Saved, but the email failed: ' . $err : (!empty($_POST['send']) ? 'Quote saved and emailed to ' . $q['email'] . '.' : 'Quote saved.'));
    } elseif ($form === 'status' && isset(QUOTE_STATUSES[$_POST['status'] ?? ''])) {
        getDB()->prepare('UPDATE quotes SET status = ?, admin_notes = ? WHERE id = ?')->execute([$_POST['status'], postText('admin_notes', 4000), $id]);
        audit('quote_status', $q['ref'], $_POST['status']);
        flash('success', 'Updated.');
    } elseif ($form === 'delete') {
        $dir = ARTWORK_DIR . $q['ref'];
        if (is_dir($dir)) { array_map('unlink', glob($dir . '/*') ?: []); @rmdir($dir); }
        getDB()->prepare('DELETE FROM quotes WHERE id = ?')->execute([$id]);
        audit('quote_deleted', $q['ref']);
        flash('success', $q['ref'] . ' deleted with its files.');
        redirect('admin/quotes.php');
    }
    redirect('admin/quote.php?id=' . $id);
}

$items = quoteItems($id);
$files = quoteFiles($id);
$phone = preg_replace('/\D/', '', (string) $q['phone']);
if (str_starts_with($phone, '0')) $phone = '27' . substr($phone, 1);
$waText = "Hi {$q['name']}, this is Extra Printers about your request {$q['ref']}.";

adminOpen('quotes', $q['ref'] . ' · ' . $q['name'], e(QUOTE_TYPES[$q['type']]) . ' · received ' . e(date('j M Y H:i', strtotime($q['created_at']))) . ' · ' . statusPill($q['status']),
    ($phone ? '<a class="btn btn-wa" target="_blank" rel="noopener" href="https://wa.me/' . $phone . '?text=' . rawurlencode($waText) . '"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a> ' : '')
    . '<a class="btn btn-ghost" href="mailto:' . e($q['email']) . '?subject=' . rawurlencode('Your request ' . $q['ref']) . '"><i class="fa-regular fa-envelope"></i> Email</a> '
    . '<a class="btn btn-ghost" target="_blank" href="' . e(quoteUrl($q)) . '"><i class="fa-solid fa-eye"></i> Customer view</a>');
?>
<div class="grid-2">
  <div>
    <section class="card">
      <div class="card__head"><h2>Products</h2></div>
      <?php foreach ($items as $it): $opts = json_decode((string) $it['options'], true) ?: []; $mine = array_filter($files, fn($f) => (int) $f['item_id'] === (int) $it['id']); ?>
        <div class="list-row" style="align-items:flex-start">
          <div class="list-row__main">
            <strong><?= e($it['product_name']) ?></strong>
            <div class="line__opts"><?php foreach ($opts as $k => $v): ?><span><b><?= e($k) ?>:</b> <?= e($v) ?></span><?php endforeach; ?></div>
            <?php if ($it['unit_price'] !== null): ?><small><?= money($it['unit_price']) ?> each (fixed price)</small><?php endif; ?>
            <?php if ($it['notes']): ?><p class="hint" style="margin:.3rem 0"><i class="fa-regular fa-note-sticky"></i> <?= e($it['notes']) ?></p><?php endif; ?>
            <?php foreach ($mine as $f): ?>
              <a class="btn btn-ghost btn-xs" style="margin:.25rem .25rem 0 0" href="file.php?id=<?= (int) $f['id'] ?>"><i class="fa-solid fa-download"></i> <?= e($f['orig_name']) ?> <small>(<?= fileSizeLabel((int) $f['size']) ?>)</small></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if ($q['file_link']): ?><div class="list-row"><div class="list-row__main"><strong>Large files link</strong><small><a href="<?= e($q['file_link']) ?>" target="_blank" rel="noopener noreferrer"><?= e($q['file_link']) ?></a></small></div></div><?php endif; ?>
      <?php if ($q['notes']): ?><div class="card__body"><strong>Customer's message</strong><p style="white-space:pre-wrap;margin-top:.3rem"><?= e($q['notes']) ?></p></div><?php endif; ?>
    </section>

    <section class="card">
      <div class="card__head"><h2>Send the quote</h2></div>
      <form method="post" class="card__body">
        <?= csrfField() ?><input type="hidden" name="form" value="quote"><input type="hidden" name="id" value="<?= $id ?>">
        <div class="field" style="max-width:240px"><label>Total (R, incl. VAT)</label><input name="quoted_total" inputmode="decimal" value="<?= e($q['quoted_total'] ?? '') ?>" placeholder="e.g. 1450"></div>
        <div class="field"><label>Message to the customer</label><textarea name="quote_message" rows="5" placeholder="What's included, turnaround, delivery fee, how to pay (bank details)…"><?= e($q['quote_message'] ?? '') ?></textarea></div>
        <div class="form-foot"><button class="btn btn-primary" name="send" value="1"><i class="fa-solid fa-paper-plane"></i> Save &amp; email the quote</button><button class="btn btn-ghost">Save only</button></div>
      </form>
    </section>
  </div>

  <div>
    <section class="card">
      <div class="card__head"><h2>Customer</h2></div>
      <div class="card__body">
        <p><strong><?= e($q['name']) ?></strong><?= $q['company'] ? '<br>' . e($q['company']) : '' ?></p>
        <p><a href="mailto:<?= e($q['email']) ?>"><?= e($q['email']) ?></a><br><a href="tel:<?= e($q['phone']) ?>"><?= e($q['phone']) ?></a></p>
        <p><?= $q['delivery'] === 'deliver' ? '<i class="fa-solid fa-truck"></i> Deliver to ' . e($q['address']) : '<i class="fa-solid fa-store"></i> Collect' ?></p>
        <?php if ($q['needed_by']): ?><p><i class="fa-regular fa-calendar"></i> Needed by <strong><?= e(date('D j M Y', strtotime($q['needed_by']))) ?></strong></p><?php endif; ?>
        <p class="hint">Came from: <?= e($q['source'] ?: 'Direct') ?></p>
      </div>
    </section>
    <section class="card">
      <div class="card__head"><h2>Status &amp; notes</h2></div>
      <form method="post" class="card__body">
        <?= csrfField() ?><input type="hidden" name="form" value="status"><input type="hidden" name="id" value="<?= $id ?>">
        <div class="field"><label>Status</label><select name="status"><?php foreach (QUOTE_STATUSES as $k => $label): ?><option value="<?= $k ?>" <?= $q['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Internal notes (customer can't see)</label><textarea name="admin_notes" rows="4"><?= e($q['admin_notes'] ?? '') ?></textarea></div>
        <button class="btn btn-primary">Update</button>
      </form>
    </section>
    <form method="post" data-confirm="Delete <?= e($q['ref']) ?> and all its uploaded files? This can't be undone.">
      <?= csrfField() ?><input type="hidden" name="form" value="delete"><input type="hidden" name="id" value="<?= $id ?>">
      <button class="btn btn-ghost" style="color:var(--coral)"><i class="fa-regular fa-trash-can"></i> Delete request</button>
    </form>
  </div>
</div>
<?php adminClose(); ?>
