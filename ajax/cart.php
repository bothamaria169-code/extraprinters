<?php
declare(strict_types=1);
require_once __DIR__ . '/_boot.php';

$action = (string) ($_POST['action'] ?? '');

if ($action === 'add') {
    if (rateLimited('cart_add', 60, 10)) jsonResponse(['ok' => false, 'error' => 'Too many changes. Wait a minute and try again.'], 429);
    $p = product((string) (int) ($_POST['product_id'] ?? 0));
    if (!$p || !$p['is_active']) jsonResponse(['ok' => false, 'error' => 'That product is not available.']);
    if (cartCount() >= 25) jsonResponse(['ok' => false, 'error' => 'Your quote has 25 products. Send it first, then start another.']);
    $r = cartAdd($p, (array) ($_POST['opt'] ?? []), (string) ($_POST['custom_qty'] ?? ''), (string) ($_POST['notes'] ?? ''));
    if (is_array($r)) jsonResponse(['ok' => false, 'error' => $r['error']]);
    jsonResponse(['ok' => true, 'key' => $r, 'count' => cartCount()]);
}

$key = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['key'] ?? ''));

if ($action === 'remove') {
    cartRemove($key);
    jsonResponse(['ok' => true, 'count' => cartCount()]);
}

if ($action === 'notes') {
    if (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key]['notes'] = mb_substr(trim((string) ($_POST['notes'] ?? '')), 0, 1000);
    jsonResponse(['ok' => true]);
}

jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
