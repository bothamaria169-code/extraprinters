<?php
declare(strict_types=1);

// ── CSRF ──────────────────────────────────────────────────────────
function csrfToken(): string
{
    startAppSession();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrfToken() . '">';
}

function csrfValid(): bool
{
    startAppSession();
    $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return $sent !== '' && hash_equals((string) ($_SESSION['csrf'] ?? ''), $sent);
}

function requireCsrf(): void
{
    if (!csrfValid()) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

// ── Request info ──────────────────────────────────────────────────
function clientIp(): string
{
    return substr((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function userAgent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 400);
}

// ── Brute-force protection ────────────────────────────────────────
/** True when this email or IP has failed too often in the last 15 minutes. */
function tooManyAttempts(string $email): bool
{
    $s = getDB()->prepare(
        'SELECT
            SUM(email = ?) AS by_email,
            COUNT(*)       AS by_ip
         FROM login_attempts
         WHERE success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE) AND (email = ? OR ip = ?)'
    );
    $s->execute([$email, $email, clientIp()]);
    $r = $s->fetch() ?: [];
    return (int) ($r['by_email'] ?? 0) >= 6 || (int) ($r['by_ip'] ?? 0) >= 25;
}

function recordAttempt(string $email, bool $success): void
{
    getDB()->prepare('INSERT INTO login_attempts (email, ip, success) VALUES (?, ?, ?)')
        ->execute([substr($email, 0, 255), clientIp(), $success ? 1 : 0]);
}

/** Simple per-IP throttle for public forms (quote requests, uploads). */
function rateLimited(string $bucket, int $max, int $minutes): bool
{
    startAppSession();
    $now = time();
    $hits = array_filter($_SESSION['rl'][$bucket] ?? [], fn($t) => $t > $now - $minutes * 60);
    $hits[] = $now;
    $_SESSION['rl'][$bucket] = $hits;
    return count($hits) > $max;
}

// ── Signed values (private quote links) ───────────────────────────
function sign(string $value): string
{
    return substr(hash_hmac('sha256', $value, APP_SECRET), 0, 32);
}

// ── Flash messages ────────────────────────────────────────────────
function flash(string $type, string $message): void
{
    startAppSession();
    $_SESSION['flash'][] = [$type, $message];
}

function takeFlashes(): array
{
    startAppSession();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $to): never
{
    header('Location: ' . (str_starts_with($to, 'http') ? $to : APP_URL . '/' . ltrim($to, '/')));
    exit;
}
