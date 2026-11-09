<?php
declare(strict_types=1);
// Admin AJAX: AI writing and connection tests. Always answers JSON.
require_once __DIR__ . '/_shell.php';
require_once BASE_DIR . '/includes/social.php';
require_once BASE_DIR . '/includes/blog.php';

$action = (string) ($_POST['action'] ?? '');

if ($action === 'product') {
    $p = product((string) (int) ($_POST['id'] ?? 0));
    if (!$p) jsonResponse(['ok' => false, 'error' => 'Product not found']);
    jsonResponse(aiProductCopy($p, $p['cat_name']));
}

if ($action === 'test_ai') {
    if (aiKey() === '') jsonResponse(['ok' => false, 'error' => 'Paste and save a Groq key first.']);
    $r = groqJson('Answer ONLY with JSON {"ok":true,"say":""}.', 'Say hello to Extra Printers in 5 words.', 200);
    jsonResponse($r['ok'] ? ['ok' => true, 'message' => 'Groq works (model ' . $r['model'] . '): ' . ($r['data']['say'] ?? '')] : ['ok' => false, 'error' => $r['error']]);
}

if ($action === 'test_email') {
    $err = sendMail(ADMIN_EMAIL, 'Test email from Extra Printers', emailLayout('It works!', '<p>Your website can send email. Quote requests will arrive like this.</p>'));
    jsonResponse($err ? ['ok' => false, 'error' => $err] : ['ok' => true, 'message' => 'Sent to ' . ADMIN_EMAIL . '. Check the inbox (and spam).']);
}

jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
