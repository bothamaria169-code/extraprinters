<?php
declare(strict_types=1);
// Private artwork download, admins only.
require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();

$s = getDB()->prepare('SELECT * FROM quote_files WHERE id = ?');
$s->execute([(int) ($_GET['id'] ?? 0)]);
$f = $s->fetch();
$path = $f ? realpath(ARTWORK_DIR . $f['stored']) : false;
if (!$f || !$path || !str_starts_with($path, realpath(ARTWORK_DIR)) || !is_file($path)) { http_response_code(404); exit('File not found'); }

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $f['orig_name']) . '"; filename*=UTF-8\'\'' . rawurlencode($f['orig_name']));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
