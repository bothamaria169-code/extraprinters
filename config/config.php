<?php
declare(strict_types=1);

// ── Secrets ──────────────────────────────────────────────────────
// Passwords and keys live in config/secrets.php (not in git). Copy
// config/secrets.example.php to start one.
$secretsFile = __DIR__ . '/secrets.php';
$GLOBALS['XP_SECRETS'] = is_file($secretsFile) ? (require $secretsFile) : [];

function secret(string $key, mixed $default = ''): mixed
{
    return $GLOBALS['XP_SECRETS'][$key] ?? $default;
}

// ── App ──────────────────────────────────────────────────────────
define('APP_NAME', 'Extra Printers');
define('APP_TAGLINE', 'Paper, printing & signage in Johannesburg');
define('BASE_DIR', dirname(__DIR__));
date_default_timezone_set('Africa/Johannesburg');

$host = $_SERVER['HTTP_HOST'] ?? '';
define('IS_LOCAL', $host === ''
    ? PHP_OS_FAMILY === 'Windows'
    : (bool) preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$|\.(localhost|test)(:\d+)?$/', $host));

// APP_URL: from the environment, else the current host + install folder,
// else secrets.php's app_url (cron/CLI).
$appUrl = (string) getenv('APP_URL');
if ($appUrl === '' && $host !== '') {
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $basePath = '/extraprinters';
    if ($documentRoot && realpath(BASE_DIR) !== false) {
        $relativePath = str_replace('\\', '/', substr(realpath(BASE_DIR), strlen($documentRoot)));
        $basePath = '/' . trim($relativePath, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $appUrl = $scheme . '://' . $host . ($basePath === '/' ? '' : $basePath);
}
if ($appUrl === '') {
    $appUrl = IS_LOCAL ? 'http://localhost/extraprinters' : (string) secret('app_url', 'https://extraprinters.co.za');
}
define('APP_URL', rtrim($appUrl, '/'));
// The address Google should index, whatever host serves the page.
define('CANONICAL_URL', IS_LOCAL ? APP_URL : rtrim((string) secret('app_url', 'https://extraprinters.co.za'), '/'));

define('ADMIN_EMAIL', strtolower((string) secret('admin_email', 'admin@extraprinters.co.za')));
define('APP_SECRET', (string) secret('app_secret', 'change-me'));

// ── Database ─────────────────────────────────────────────────────
define('DB_HOST', (string) secret('db_host', 'localhost'));
define('DB_NAME', (string) secret(IS_LOCAL ? 'local_db_name' : 'db_name', 'extraprinters'));
define('DB_USER', (string) secret(IS_LOCAL ? 'local_db_user' : 'db_user', 'root'));
define('DB_PASS', (string) secret(IS_LOCAL ? 'local_db_pass' : 'db_pass', ''));

// ── Uploads ──────────────────────────────────────────────────────
// Artwork is private (blocked by .htaccess, downloaded through file.php by
// admins only). Product photos are public.
define('ARTWORK_DIR', BASE_DIR . '/uploads/artwork/');
define('PRODUCT_IMG_DIR', BASE_DIR . '/uploads/products/');
define('MAX_ARTWORK_MB', 50);
define('MAX_ARTWORK_FILES', 5);
const ARTWORK_EXT = ['pdf', 'ai', 'eps', 'psd', 'tif', 'tiff', 'jpg', 'jpeg', 'png', 'zip', 'cdr', 'svg'];

// ── Email / SMTP (PHPMailer) ──────────────────────────────────────
define('SMTP_HOST', (string) secret('smtp_host', ''));
define('SMTP_PORT', (int) secret('smtp_port', 465));
define('SMTP_USER', (string) secret('smtp_user', ''));
define('SMTP_PASS', (string) secret('smtp_pass', ''));

if (!IS_LOCAL) {
    ini_set('display_errors', '0');
}
