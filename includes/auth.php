<?php
declare(strict_types=1);

/**
 * Bootstrap for every page: config, database, helpers and the session.
 * Customers don't need accounts (quotes are guest checkouts with a private
 * link); only admins sign in.
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/mailer.php';

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('xp_sess');
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 14, // the cart survives two weeks
            'path'     => '/',
            'secure'   => str_starts_with(APP_URL, 'https://'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.gc_maxlifetime', (string) (60 * 60 * 24 * 14));
        session_start();
    }
}

function isLoggedIn(): bool
{
    startAppSession();
    return isset($_SESSION['user_id']);
}

function getCurrentUser(): ?array
{
    if (!isLoggedIn()) return null;
    $s = getDB()->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
    $s->execute([$_SESSION['user_id']]);
    $u = $s->fetch() ?: null;
    if (!$u) logoutUser();
    return $u;
}

function isAdmin(): bool
{
    return getCurrentUser() !== null;
}

function requireAdmin(): void
{
    if (!isAdmin()) redirect('login.php');
}

/** @return true|'invalid'|'locked' */
function loginUser(string $email, string $password): bool|string
{
    $email = strtolower(trim($email));
    if (tooManyAttempts($email)) return 'locked';
    $s = getDB()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $s->execute([$email]);
    $row = $s->fetch();
    if (!$row || !password_verify($password, (string) $row['password_hash'])) {
        recordAttempt($email, false);
        return 'invalid';
    }
    recordAttempt($email, true);
    startAppSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $row['id'];
    getDB()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$row['id']]);
    return true;
}

function logoutUser(): void
{
    startAppSession();
    unset($_SESSION['user_id']); // keep the visitor's cart
    session_regenerate_id(true);
}
