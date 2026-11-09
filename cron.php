<?php
declare(strict_types=1);

/**
 * Scheduled work. cPanel → Cron Jobs → every 15 minutes:
 *   /usr/local/bin/php /home/tczghmfy/public_html/cron.php >/dev/null 2>&1
 *
 *  - Facebook / Instagram auto-post (days + time in Admin → Social & bots)
 *  - AI blog article on the next SEO keyword (days in Admin → Blog)
 *  - Removes abandoned cart uploads (14 days) and old artwork (12 months)
 */
if (PHP_SAPI !== 'cli' && ($_GET['key'] ?? '') === '') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/social.php';
require_once __DIR__ . '/includes/blog.php';
if (PHP_SAPI !== 'cli' && !hash_equals(sign('cron'), (string) $_GET['key'])) { http_response_code(403); exit; }

$log = [];
$lock = fopen(sys_get_temp_dir() . '/extraprinters-cron.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) exit("Already running\n");

// 1. Social auto-post
if (autopostDue()) {
    set_setting('autopost_last', date('Y-m-d'));
    foreach (autopostRun() as $platform => $r) $log[] = "post {$platform}: " . ($r['ok'] ? 'ok' : $r['error']);
}

// 2. Blog article
if (blogDue()) {
    set_setting('blog_last', date('Y-m-d'));
    $r = blogWriteNext();
    $log[] = 'article: ' . ($r['ok'] ? $r['title'] : $r['error']);
}

// 3. Clean-up
$cartRoot = ARTWORK_DIR . 'cart/';
foreach (glob($cartRoot . '*', GLOB_ONLYDIR) ?: [] as $dir) {
    if (filemtime($dir) < time() - 14 * 86400) {
        array_map('unlink', glob($dir . '/*') ?: []);
        @rmdir($dir);
    }
}
$old = getDB()->query("SELECT id, ref FROM quotes WHERE status IN ('completed','cancelled') AND updated_at < (NOW() - INTERVAL 12 MONTH)")->fetchAll();
foreach ($old as $q) {
    $dir = ARTWORK_DIR . $q['ref'];
    if (is_dir($dir)) { array_map('unlink', glob($dir . '/*') ?: []); @rmdir($dir); }
    getDB()->prepare('DELETE FROM quote_files WHERE quote_id = ?')->execute([$q['id']]);
}

set_setting('cron_last', date('Y-m-d H:i:s'));
echo $log ? implode("\n", $log) . "\n" : "Nothing due\n";
