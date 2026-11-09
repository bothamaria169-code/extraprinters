<?php
declare(strict_types=1);

/**
 * The cart lives in the session (no account needed). Artwork uploaded from
 * the cart goes to uploads/artwork/cart/<visitor>/ and moves under the quote
 * when the request is sent. cron.php clears cart folders older than 14 days.
 */
require_once __DIR__ . '/catalog.php';

function cart(): array
{
    startAppSession();
    return $_SESSION['cart'] ?? [];
}

function cartCount(): int
{
    return count(cart());
}

function cartDir(): string
{
    startAppSession();
    $_SESSION['cart_dir'] ??= bin2hex(random_bytes(12));
    $dir = ARTWORK_DIR . 'cart/' . $_SESSION['cart_dir'] . '/';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    return $dir;
}

/**
 * Checks the posted options against the product and adds a line.
 * @return string|array the new line's key, or ['error' => …]
 */
function cartAdd(array $p, array $posted, string $customQty, string $notes): string|array
{
    $chosen = [];
    foreach (productOptions($p) as $g) {
        $val = trim((string) ($posted[$g['key']] ?? ''));
        if ($val === '__custom' && isset($g['custom_above'])) {
            $n = (int) preg_replace('/\D/', '', $customQty);
            if ($n <= $g['custom_above']) {
                return ['error' => 'For a custom ' . strtolower($g['label']) . ', enter more than ' . number_format($g['custom_above'], 0, '.', ' ') . '.'];
            }
            $val = number_format($n, 0, '.', ' ') . ' (custom)';
        } elseif (!in_array($val, $g['values'], true)) {
            return ['error' => 'Please choose the ' . strtolower($g['label']) . '.'];
        }
        $chosen[$g['label']] = $val;
    }
    $key = bin2hex(random_bytes(6));
    startAppSession();
    $_SESSION['cart'][$key] = [
        'product_id' => (int) $p['id'],
        'options'    => $chosen,
        'notes'      => mb_substr(trim($notes), 0, 1000),
        'files'      => [],
        'added'      => time(),
    ];
    return $key;
}

function cartRemove(string $key): void
{
    startAppSession();
    foreach ($_SESSION['cart'][$key]['files'] ?? [] as $f) @unlink(cartDir() . $f['stored']);
    unset($_SESSION['cart'][$key]);
}

/** Cart lines joined with their products (lines whose product was removed are dropped). */
function cartLines(): array
{
    $out = [];
    foreach (cart() as $key => $line) {
        $p = product((string) $line['product_id']);
        if (!$p) continue;
        $out[$key] = $line + ['product' => $p];
    }
    return $out;
}

/** Estimated total of the fixed-price lines (graphic design at R300 per artwork). */
function cartFixedTotal(): float
{
    $t = 0.0;
    foreach (cartLines() as $l) {
        if ($l['product']['fixed_price'] && $l['product']['price_from'] !== null) {
            $qty = (int) preg_replace('/\D/', '', (string) (end($l['options']) ?: '1')) ?: 1;
            $t += $qty * (float) $l['product']['price_from'];
        }
    }
    return $t;
}

/**
 * Saves one uploaded artwork file against a cart line.
 * @return array{ok: bool, file?: array, error?: string}
 */
function cartAttach(string $key, array $upload): array
{
    startAppSession();
    if (!isset($_SESSION['cart'][$key])) return ['ok' => false, 'error' => 'That item is no longer in your cart.'];
    if (count($_SESSION['cart'][$key]['files']) >= MAX_ARTWORK_FILES) return ['ok' => false, 'error' => 'You can upload up to ' . MAX_ARTWORK_FILES . ' files per product. Put extra files in a ZIP or share a link in the notes.'];
    $check = checkArtworkUpload($upload);
    if ($check !== '') return ['ok' => false, 'error' => $check];

    $orig = mb_substr(basename((string) $upload['name']), 0, 200);
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $stored = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($upload['tmp_name'], cartDir() . $stored)) return ['ok' => false, 'error' => 'The file could not be saved. Please try again.'];
    $file = ['id' => substr($stored, 0, 8), 'name' => $orig, 'stored' => $stored, 'size' => (int) $upload['size']];
    $_SESSION['cart'][$key]['files'][] = $file;
    return ['ok' => true, 'file' => $file];
}

function cartDetach(string $key, string $fileId): void
{
    startAppSession();
    foreach ($_SESSION['cart'][$key]['files'] ?? [] as $i => $f) {
        if ($f['id'] === $fileId) {
            @unlink(cartDir() . $f['stored']);
            array_splice($_SESSION['cart'][$key]['files'], $i, 1);
            return;
        }
    }
}

/** '' when the upload is acceptable, else a message for the customer. */
function checkArtworkUpload(array $upload): string
{
    $err = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) return 'That file is bigger than ' . MAX_ARTWORK_MB . ' MB. Compress it, or paste a WeTransfer / Google Drive link in the notes.';
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file((string) $upload['tmp_name'])) return 'The upload did not finish. Please try again.';
    if ((int) $upload['size'] > MAX_ARTWORK_MB * 1024 * 1024) return 'That file is bigger than ' . MAX_ARTWORK_MB . ' MB. Paste a WeTransfer / Google Drive link in the notes instead.';
    $ext = strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ARTWORK_EXT, true)) return 'Please upload PDF (best), AI, EPS, PSD, TIFF, JPG, PNG, SVG, CDR or ZIP.';
    return '';
}

function fileSizeLabel(int $bytes): string
{
    return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, round($bytes / 1024)) . ' KB';
}
