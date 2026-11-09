<?php
declare(strict_types=1);

// A POST bigger than post_max_size arrives empty (no CSRF token either), so
// say what really happened before the usual checks.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'That file is too big for the server. Paste a WeTransfer or Google Drive link in the notes instead.']);
    exit;
}
require_once __DIR__ . '/_boot.php';

$key = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['key'] ?? ''));

if (($_POST['action'] ?? '') === 'remove') {
    cartDetach($key, preg_replace('/[^a-f0-9]/', '', (string) ($_POST['file'] ?? '')));
    jsonResponse(['ok' => true]);
}

if (rateLimited('upload', 40, 30)) jsonResponse(['ok' => false, 'error' => 'Too many uploads. Wait a few minutes, or share a link in the notes.'], 429);
if (empty($_FILES['artwork'])) jsonResponse(['ok' => false, 'error' => 'No file arrived. Please try again.']);

$r = cartAttach($key, $_FILES['artwork']);
if (!$r['ok']) jsonResponse($r);
jsonResponse(['ok' => true, 'file' => ['id' => $r['file']['id'], 'name' => $r['file']['name'], 'size' => fileSizeLabel($r['file']['size'])]]);
