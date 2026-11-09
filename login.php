<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if (isAdmin()) redirect('admin/');
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $email = postText('email', 190);
    $r = loginUser($email, (string) ($_POST['password'] ?? ''));
    if ($r === true) redirect('admin/');
    flash('error', $r === 'locked' ? 'Too many attempts. Wait 15 minutes and try again.' : 'That email and password don\'t match.');
}
pageHead('Admin sign in – Extra Printers', ['css' => ['style.css', 'site.css']]);
?>
<body style="min-height:100vh;display:grid;place-items:center;padding:16px;background:var(--bg)">
<main style="width:100%;max-width:400px">
  <div class="cmyk-bar" style="border-radius:8px 8px 0 0;overflow:hidden"><i></i><i></i><i></i><i></i></div>
  <form method="post" class="form-card" style="border-radius:0 0 var(--r-lg) var(--r-lg)" data-busy>
    <div style="margin-bottom:1.2rem"><?= brandMark() ?></div>
    <h1 style="font-size:1.4rem;margin-bottom:1rem">Admin sign in</h1>
    <?= flashHtml() ?>
    <?= csrfField() ?>
    <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" required autocomplete="username" value="<?= e($email) ?>" autofocus></div>
    <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
    <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
    <p class="hint" style="margin-top:1rem">Forgot it? Set <code>admin_reset_password</code> in config/secrets.php and load any page once.</p>
  </form>
  <p style="text-align:center;margin-top:1rem"><a href="<?= e(url('')) ?>">← Back to the website</a></p>
</main>
</body></html>
