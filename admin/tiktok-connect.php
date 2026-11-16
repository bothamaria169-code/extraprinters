<?php
declare(strict_types=1);

// TikTok login for photo-post drafts: start (?start) and callback (?code).
require_once __DIR__ . '/_shell.php';
require_once BASE_DIR . '/classes/TikTok.php';

if (isset($_GET['start'])) {
    if (!TikTok::ready()) { flash('error', 'Save the TikTok Client key and secret first.'); redirect('admin/social.php'); }
    $_SESSION['tiktok_state'] = bin2hex(random_bytes(16));
    header('Location: ' . TikTok::loginUrl($_SESSION['tiktok_state']));
    exit;
}

if (isset($_GET['error'])) {
    flash('error', 'TikTok connection cancelled: ' . ($_GET['error_description'] ?? $_GET['error']));
} elseif (isset($_GET['code'])) {
    if (!hash_equals((string) ($_SESSION['tiktok_state'] ?? ''), (string) ($_GET['state'] ?? ''))) {
        flash('error', 'That TikTok link expired. Press Connect TikTok again.');
    } else {
        $err = TikTok::exchangeCode((string) $_GET['code']);
        flash($err === '' ? 'success' : 'error', $err === '' ? 'TikTok connected' . (TikTok::config()['name'] ? ' as ' . TikTok::config()['name'] : '') . '.' : $err);
        if ($err === '') audit('tiktok_connected', TikTok::config()['name']);
    }
    unset($_SESSION['tiktok_state']);
}
redirect('admin/social.php');
