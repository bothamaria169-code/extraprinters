<?php
declare(strict_types=1);

/**
 * Quote requests: from the cart (type order), the design form (design), the
 * installation form (installation) and the contact form (contact). Every
 * request lands in Admin → Quotes and is emailed to the shop and the customer.
 */
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/tracking.php';

const QUOTE_STATUSES = [
    'new' => 'New', 'quoted' => 'Quote sent', 'approved' => 'Approved', 'in_production' => 'Printing',
    'ready' => 'Ready', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
];
const QUOTE_TYPES = ['order' => 'Print order', 'design' => 'Design request', 'installation' => 'Signage installation', 'contact' => 'Enquiry'];

function quoteUrl(array $q): string
{
    return APP_URL . '/quote.php?ref=' . rawurlencode($q['ref']) . '&t=' . $q['token'];
}

function findQuote(string $ref, string $token): ?array
{
    $s = getDB()->prepare('SELECT * FROM quotes WHERE ref = ?');
    $s->execute([$ref]);
    $q = $s->fetch();
    return $q && hash_equals($q['token'], $token) ? $q : null;
}

function quoteItems(int $id): array
{
    $s = getDB()->prepare('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY id');
    $s->execute([$id]);
    return $s->fetchAll();
}

function quoteFiles(int $id): array
{
    $s = getDB()->prepare('SELECT * FROM quote_files WHERE quote_id = ? ORDER BY id');
    $s->execute([$id]);
    return $s->fetchAll();
}

/** Validates the customer block of any quote form. @return array{0: array, 1: string} [data, error] */
function quoteCustomerFromPost(): array
{
    $d = [
        'name'     => postText('name', 120),
        'company'  => postText('company', 160),
        'email'    => strtolower(postText('email', 190)),
        'phone'    => postText('phone', 40),
        'delivery' => ($_POST['delivery'] ?? '') === 'deliver' ? 'deliver' : 'collect',
        'address'  => postText('address', 255),
        'needed_by'=> preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['needed_by'] ?? '')) ? $_POST['needed_by'] : null,
        'notes'    => postText('notes', 4000),
        'file_link'=> filter_var(postText('file_link', 500), FILTER_VALIDATE_URL) ?: '',
    ];
    if ($d['name'] === '') return [$d, 'Please enter your name.'];
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) return [$d, 'Please enter a valid email address so we can send your quote.'];
    if (strlen(preg_replace('/\D/', '', $d['phone'])) < 9) return [$d, 'Please enter a phone or WhatsApp number.'];
    if ($d['delivery'] === 'deliver' && $d['address'] === '') return [$d, 'Please enter the delivery address.'];
    if (empty($_POST['consent'])) return [$d, 'Please agree that we may use your details to quote you.'];
    if (trim((string) ($_POST['website'] ?? '')) !== '') return [$d, 'Something went wrong. Please try again.']; // honeypot
    return [$d, ''];
}

/**
 * Creates the quote with its items and files, then sends the emails.
 * @param array<int, array{product: array|null, name: string, options: array, quantity: string, notes: string, files: array, dir: string}> $lines
 */
function createQuote(string $type, array $c, array $lines): array
{
    $db = getDB();
    $db->beginTransaction();
    $token = bin2hex(random_bytes(16));
    $db->prepare('INSERT INTO quotes (ref, token, type, name, company, email, phone, delivery, address, needed_by, notes, file_link, source, ip)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
       ->execute(['TMP' . bin2hex(random_bytes(4)), $token, $type, $c['name'], $c['company'], $c['email'], $c['phone'], $c['delivery'],
                  $c['address'], $c['needed_by'], $c['notes'], $c['file_link'], firstTouch(), clientIp()]);
    $id = (int) $db->lastInsertId();
    $ref = 'XP' . date('ym') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    $db->prepare('UPDATE quotes SET ref = ? WHERE id = ?')->execute([$ref, $id]);

    $dest = ARTWORK_DIR . $ref . '/';
    $insItem = $db->prepare('INSERT INTO quote_items (quote_id, product_id, product_name, options, quantity, unit_price, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insFile = $db->prepare('INSERT INTO quote_files (quote_id, item_id, orig_name, stored, size) VALUES (?, ?, ?, ?, ?)');
    foreach ($lines as $l) {
        $p = $l['product'];
        $insItem->execute([$id, $p['id'] ?? null, $l['name'], json_encode($l['options'], JSON_UNESCAPED_UNICODE), $l['quantity'],
                           $p && $p['fixed_price'] ? $p['price_from'] : null, $l['notes']]);
        $itemId = (int) $db->lastInsertId();
        foreach ($l['files'] as $f) {
            if (!is_dir($dest)) mkdir($dest, 0775, true);
            if (@rename($l['dir'] . $f['stored'], $dest . $f['stored'])) {
                $insFile->execute([$id, $itemId, $f['name'], $ref . '/' . $f['stored'], $f['size']]);
            }
        }
    }
    $db->commit();

    $s = $db->prepare('SELECT * FROM quotes WHERE id = ?');
    $s->execute([$id]);
    $q = $s->fetch();
    sendQuoteEmails($q);
    metaCapiLead($q);
    return $q;
}

/** Lines from the session cart, ready for createQuote(). */
function cartToLines(): array
{
    $out = [];
    foreach (cartLines() as $l) {
        $opts = $l['options'];
        $qty = '';
        foreach ($opts as $label => $val) if (preg_match('/quantity|number of|qty/i', $label)) $qty = $val;
        $out[] = ['product' => $l['product'], 'name' => $l['product']['name'], 'options' => $opts, 'quantity' => $qty,
                  'notes' => $l['notes'], 'files' => $l['files'], 'dir' => cartDir()];
    }
    return $out;
}

function sendQuoteEmails(array $q): void
{
    $items = quoteItems((int) $q['id']);
    $files = quoteFiles((int) $q['id']);
    $list = '';
    foreach ($items as $it) {
        $opts = json_decode((string) $it['options'], true) ?: [];
        $list .= '<li style="margin-bottom:8px"><strong>' . e($it['product_name']) . '</strong><br><span style="color:#4a5768;font-size:13px">'
               . e(implode(' · ', array_map(fn($k, $v) => "$k: $v", array_keys($opts), $opts))) . '</span>'
               . ($it['notes'] ? '<br><em style="font-size:13px">' . e($it['notes']) . '</em>' : '') . '</li>';
    }
    $list = $list ? '<ul style="padding-left:18px">' . $list . '</ul>' : '';
    $type = QUOTE_TYPES[$q['type']] ?? 'Request';

    // To the customer
    $wa = biz('whatsapp') ? '<p>Questions? <a href="' . e(waLink("Hi Extra Printers, about my quote {$q['ref']}")) . '">WhatsApp us</a> with your reference.</p>' : '';
    $body = '<p>Hi ' . e(strtok($q['name'], ' ')) . ',</p><p>Thanks for your ' . strtolower($type) . '. Your reference is <strong>' . e($q['ref']) . '</strong>. '
          . 'We\'re checking your details' . ($files ? ' and the ' . count($files) . ' file(s) you uploaded' : '') . ', and will send your quote shortly.</p>'
          . $list . $wa;
    sendMail($q['email'], "We've received your request {$q['ref']} – Extra Printers", emailLayout('Request received', $body, 'View your request', quoteUrl($q)));

    // To the shop
    $rows = ['Reference' => $q['ref'], 'Type' => $type, 'Name' => $q['name'], 'Company' => $q['company'] ?: '—', 'Email' => $q['email'], 'Phone' => $q['phone'],
             'Delivery' => $q['delivery'] === 'deliver' ? 'Deliver to: ' . $q['address'] : 'Collect', 'Needed by' => $q['needed_by'] ?: '—',
             'Files' => count($files) . ($q['file_link'] ? ' + link' : ''), 'Came from' => $q['source']];
    $phone = preg_replace('/\D/', '', (string) $q['phone']);
    if (str_starts_with($phone, '0')) $phone = '27' . substr($phone, 1);
    $admin = emailRows($rows) . $list . ($q['notes'] ? '<p><strong>Notes:</strong><br>' . nl2br(e($q['notes'])) . '</p>' : '')
           . ($phone ? '<p><a href="https://wa.me/' . $phone . '">Reply on WhatsApp</a></p>' : '');
    $to = array_unique(array_filter([get_setting('notify_email', biz('email')), ADMIN_EMAIL]));
    sendMail($to, "New {$type} {$q['ref']} from {$q['name']}", emailLayout('New ' . strtolower($type), $admin, 'Open in admin', APP_URL . '/admin/quote.php?id=' . $q['id']), [], $q['email']);
}

/** Sends the priced quote from Admin → Quotes. */
function sendPricedQuote(array $q): string
{
    $review = get_setting('google_review_url');
    $body = '<p>Hi ' . e(strtok($q['name'], ' ')) . ',</p><p>Here is your quote for <strong>' . e($q['ref']) . '</strong>:</p>'
          . '<p style="font-size:26px;font-weight:800;margin:10px 0">' . money($q['quoted_total']) . '</p>'
          . ($q['quote_message'] ? '<p>' . nl2br(e($q['quote_message'])) . '</p>' : '')
          . '<p>Reply to this email or WhatsApp us to approve it, and we\'ll start printing.</p>'
          . ($review ? '<p style="font-size:13px;color:#7c8899">Happy with us? <a href="' . e($review) . '">Leave us a Google review</a>, it helps a lot.</p>' : '');
    return sendMail($q['email'], "Your quote {$q['ref']} – Extra Printers", emailLayout('Your quote is ready', $body, 'View your quote', quoteUrl($q)), [], get_setting('notify_email', biz('email')));
}
