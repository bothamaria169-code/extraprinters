<?php
declare(strict_types=1);

// ── Responses & strings ───────────────────────────────────────────
function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function e(mixed $val): string
{
    return htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function postText(string $key, int $len = 255): string
{
    return mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $len);
}

function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s), '-'));
    return substr($s, 0, 110) ?: 'item';
}

function money(float|string|null $n): string
{
    return $n === null || $n === '' ? '' : 'R' . number_format((float) $n, ((float) $n == floor((float) $n)) ? 0 : 2, '.', ' ');
}

function timeAgo(?string $dt): string
{
    if (!$dt) return '—';
    $s = time() - strtotime($dt);
    return match (true) {
        $s < 60      => 'just now',
        $s < 3600    => floor($s / 60) . ' min ago',
        $s < 86400   => floor($s / 3600) . ' h ago',
        $s < 172800  => 'yesterday',
        $s < 2592000 => floor($s / 86400) . ' days ago',
        default      => date('j M Y', strtotime($dt)),
    };
}

function excerpt(?string $text, int $len = 160): string
{
    $t = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $text))));
    return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len)) . '…' : $t;
}

function url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

// ── Business details (Admin → Settings → Business details) ────────
// Keep these identical to the Google Business Profile (NAP consistency).
function biz(string $key): string
{
    $defaults = [
        'name'     => 'Extra Printers',
        'street'   => '',
        'suburb'   => '',
        'city'     => 'Johannesburg',
        'province' => 'Gauteng',
        'postcode' => '',
        'phone'    => '',
        'whatsapp' => '27630693955', // +27 63 069 3955
        'email'    => 'quotes@extraprinters.co.za',
        'hours'    => 'Mon–Fri 08:00–17:00 · Sat 08:00–13:00',
        'area'     => 'Johannesburg, Sandton, Randburg, Roodepoort, Soweto, Midrand, Germiston',
        'facebook' => '', 'instagram' => '', 'tiktok' => '', 'linkedin' => '',
    ];
    return get_setting('biz_' . $key, $defaults[$key] ?? '');
}

function bizAddress(): string
{
    return implode(', ', array_filter([biz('street'), biz('suburb'), biz('city'), biz('postcode')]));
}

/** wa.me link with a pre-filled message. Falls back to the contact page. */
function waLink(string $message): string
{
    $n = preg_replace('/\D/', '', biz('whatsapp'));
    if ($n === '') return url('contact.php');
    if (str_starts_with($n, '0')) $n = '27' . substr($n, 1);
    return 'https://wa.me/' . $n . '?text=' . rawurlencode($message);
}

function telLink(): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', biz('phone'));
}

// ── Attribution (first touch) ─────────────────────────────────────
function captureAttribution(): void
{
    startAppSession();
    if (!empty($_SESSION['first_touch'])) return;
    $parts = [];
    foreach (['utm_source', 'utm_medium', 'utm_campaign'] as $k) {
        if (!empty($_GET[$k])) $parts[] = substr(preg_replace('/[^\w.\- ]/', '', (string) $_GET[$k]), 0, 60);
    }
    if (!empty($_GET['gclid'])) $parts[] = 'Google Ads';
    if (!empty($_GET['fbclid'])) $parts[] = 'Facebook';
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if (!$parts && $ref !== '' && !str_starts_with($ref, APP_URL)) $parts[] = (string) parse_url($ref, PHP_URL_HOST);
    $_SESSION['first_touch'] = $parts ? implode(' / ', array_unique(array_filter($parts))) : 'Direct';
}

function firstTouch(): string
{
    return substr((string) ($_SESSION['first_touch'] ?? 'Direct'), 0, 255);
}

// ── Admin audit trail ─────────────────────────────────────────────
function audit(string $action, string $target = '', string $detail = ''): void
{
    getDB()->prepare('INSERT INTO audit_log (admin_id, action, target, detail, ip) VALUES (?, ?, ?, ?, ?)')
        ->execute([$_SESSION['user_id'] ?? null, $action, mb_substr($target, 0, 200), $detail, clientIp()]);
}

// ── Pagination ────────────────────────────────────────────────────
function pager(int $total, int $page, int $per, array $query): string
{
    $pages = (int) ceil($total / $per);
    if ($pages <= 1) return '';
    $link = function (int $p, string $label, bool $on = false) use ($query) {
        $q = http_build_query(array_merge($query, ['page' => $p]));
        return '<a class="' . ($on ? 'is-on' : '') . '" href="?' . e($q) . '">' . $label . '</a>';
    };
    $out = '<nav class="pager">';
    if ($page > 1) $out .= $link($page - 1, '<i class="fa-solid fa-chevron-left"></i>');
    for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++) $out .= $link($p, (string) $p, $p === $page);
    if ($page < $pages) $out .= $link($page + 1, '<i class="fa-solid fa-chevron-right"></i>');
    return $out . '<span>' . number_format($total) . ' results</span></nav>';
}
