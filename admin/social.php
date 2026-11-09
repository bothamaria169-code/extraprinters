<?php
declare(strict_types=1);
require_once __DIR__ . '/_shell.php';
require_once BASE_DIR . '/includes/social.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';
    if ($form === 'keys') {
        set_setting('meta_app_id', preg_replace('/\D/', '', (string) $_POST['meta_app_id']));
        save_secret_setting('meta_app_secret', (string) ($_POST['meta_app_secret'] ?? ''));
        flash('success', 'Meta app keys saved. Now press Connect with Facebook.');
    } elseif ($form === 'disconnect') {
        MetaPoster::disconnect();
        flash('success', 'Disconnected.');
    } elseif ($form === 'autopost') {
        set_setting('autopost_on', !empty($_POST['autopost_on']) ? '1' : '0');
        set_setting('autopost_days', implode(',', array_intersect((array) ($_POST['days'] ?? []), array_keys(POST_DAYS))));
        set_setting('autopost_time', preg_match('/^\d{2}:\d{2}$/', (string) $_POST['autopost_time']) ? $_POST['autopost_time'] : '08:00');
        set_setting('autopost_fb', !empty($_POST['autopost_fb']) ? '1' : '0');
        set_setting('autopost_ig', !empty($_POST['autopost_ig']) ? '1' : '0');
        flash('success', 'Auto-posting saved.');
    } elseif ($form === 'post_now') {
        $p = !empty($_POST['product_id']) ? product((string) (int) $_POST['product_id']) : null;
        $res = autopostRun($p);
        $ok = array_filter($res, fn($r) => $r['ok']);
        flash($ok ? 'success' : 'error', implode(' · ', array_map(fn($k, $r) => ucfirst($k) . ': ' . ($r['ok'] ? 'posted' : $r['error']), array_keys($res), $res)));
    } elseif ($form === 'chatbot') {
        set_setting('chatbot_on', !empty($_POST['chatbot_on']) ? '1' : '0');
        set_setting('wa_phone_id', preg_replace('/\D/', '', (string) $_POST['wa_phone_id']));
        save_secret_setting('wa_token', (string) ($_POST['wa_token'] ?? ''));
        set_setting('chat_verify_token', preg_replace('/[^\w-]/', '', (string) $_POST['chat_verify_token']) ?: get_setting('chat_verify_token'));
        flash('success', 'Chat bot saved.');
    }
    audit('social_' . $form);
    redirect('admin/social.php');
}

if (get_setting('chat_verify_token') === '') set_setting('chat_verify_token', 'xp-' . bin2hex(random_bytes(6)));
$meta = MetaPoster::config();
$ap = autopostConfig();
$log = getDB()->query('SELECT s.*, p.name FROM social_posts s LEFT JOIN products p ON p.id = s.product_id ORDER BY s.id DESC LIMIT 15')->fetchAll();
$convos = getDB()->query("SELECT channel, sender, MAX(created_at) last, COUNT(*) n FROM chat_log GROUP BY channel, sender ORDER BY last DESC LIMIT 20")->fetchAll();
$open = isset($_GET['chat'], $_GET['ch']) ? chatHistory((string) $_GET['ch'], (string) $_GET['chat']) : [];

adminOpen('social', 'Social & bots', 'Auto-posts to Facebook and Instagram, plus an AI chat bot for WhatsApp, Messenger and Instagram.');
?>
<div class="grid-2 even">
  <section class="card">
    <div class="card__head"><div><h2>1. Connections</h2><p>🟦 developers.facebook.com: see SETUP-GUIDE §7</p></div></div>
    <div class="card__body">
      <?php if ($meta['token']): ?>
        <p><span class="tag tag-on">Connected</span> <i class="fa-brands fa-facebook"></i> <strong><?= e($meta['page_name']) ?></strong><?= $meta['instagram_name'] ? ' · <i class="fa-brands fa-instagram"></i> @' . e($meta['instagram_name']) : ' · <span class="hint">no Instagram linked</span>' ?></p>
        <form method="post" style="margin-top:.8rem"><?= csrfField() ?><input type="hidden" name="form" value="disconnect"><button class="btn btn-ghost btn-xs">Disconnect</button></form>
      <?php else: ?>
        <form method="post"><?= csrfField() ?><input type="hidden" name="form" value="keys">
          <div class="form-grid">
            <div class="field"><label>App ID</label><input name="meta_app_id" value="<?= e($meta['app_id']) ?>"></div>
            <div class="field"><label>App secret</label><input name="meta_app_secret" type="password" placeholder="<?= e(masked($meta['app_secret']) ?: 'Not set') ?>"></div>
          </div>
          <div class="form-foot"><button class="btn btn-ghost">Save keys</button>
            <?php if (MetaPoster::ready()): ?><a class="btn btn-primary" href="meta-connect.php?start=1"><i class="fa-brands fa-facebook"></i> Connect with Facebook</a><?php endif; ?></div>
        </form>
      <?php endif; ?>
      <?= copyBox('Valid OAuth Redirect URI', CANONICAL_URL . '/admin/meta-connect.php') ?>
    </div>
  </section>

  <section class="card">
    <div class="card__head"><div><h2>2. Auto-posting</h2><p>One product per slot, AI caption, product photo</p></div></div>
    <form method="post" class="card__body"><?= csrfField() ?><input type="hidden" name="form" value="autopost">
      <label class="chip-check" style="margin-bottom:.8rem"><input type="checkbox" name="autopost_on" value="1" <?= $ap['on'] ? 'checked' : '' ?>> Auto-posting on</label>
      <div class="chips" style="margin-bottom:.8rem"><?php foreach (POST_DAYS as $k => $d): ?><label class="chip-check"><input type="checkbox" name="days[]" value="<?= $k ?>" <?= in_array((string) $k, $ap['days'], true) ? 'checked' : '' ?>> <?= $d ?></label><?php endforeach; ?></div>
      <div class="form-grid"><div class="field"><label>Time</label><input type="time" name="autopost_time" value="<?= e($ap['time']) ?>"></div>
        <div class="field"><label>Post to</label><div class="chips"><label class="chip-check"><input type="checkbox" name="autopost_fb" value="1" <?= $ap['fb'] ? 'checked' : '' ?>> Facebook</label><label class="chip-check"><input type="checkbox" name="autopost_ig" value="1" <?= $ap['ig'] ? 'checked' : '' ?>> Instagram</label></div></div></div>
      <button class="btn btn-primary">Save schedule</button>
    </form>
    <form method="post" class="card__body" style="border-top:1px solid var(--line);display:flex;gap:.5rem;flex-wrap:wrap"><?= csrfField() ?><input type="hidden" name="form" value="post_now">
      <select name="product_id" class="input" style="flex:1"><option value="">Next product in line</option><?php foreach (products() as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select>
      <button class="btn btn-ghost" <?= $meta['token'] ? '' : 'disabled' ?>><i class="fa-solid fa-paper-plane"></i> Post one now</button>
    </form>
  </section>
</div>

<div class="grid-2 even">
  <section class="card" id="chat">
    <div class="card__head"><div><h2>3. Chat bot</h2><p>WhatsApp Cloud API + Messenger + Instagram DMs (SETUP-GUIDE §8)</p></div></div>
    <form method="post" class="card__body"><?= csrfField() ?><input type="hidden" name="form" value="chatbot">
      <label class="chip-check" style="margin-bottom:.8rem"><input type="checkbox" name="chatbot_on" value="1" <?= chatbotOn() ? 'checked' : '' ?>> Bot replies on</label>
      <div class="form-grid">
        <div class="field"><label>WhatsApp phone number ID</label><input name="wa_phone_id" value="<?= e(get_setting('wa_phone_id')) ?>"></div>
        <div class="field"><label>WhatsApp token</label><input name="wa_token" type="password" placeholder="<?= e(masked(get_setting('wa_token')) ?: 'Not set') ?>"></div>
      </div>
      <div class="field"><label>Verify token</label><input name="chat_verify_token" value="<?= e(get_setting('chat_verify_token')) ?>"></div>
      <?= copyBox('Webhook callback URL', CANONICAL_URL . '/webhook.php') ?>
      <p class="hint">The bot answers from your products and Business details, never invents prices, and stays quiet for 12 hours when someone asks for a person or you reply yourself. You're emailed when a person is needed.<?= aiKey() ? '' : ' <strong>Add a Groq key in Settings → AI writing first.</strong>' ?></p>
      <button class="btn btn-primary">Save chat bot</button>
    </form>
  </section>
  <section class="card">
    <div class="card__head"><h2>Recent chats</h2></div>
    <?php if ($open): ?>
      <div class="card__body"><a href="social.php#chat" class="hint">← All chats</a><div class="chat" style="margin-top:.6rem">
        <?php foreach ($open as $m): ?><div class="chat__msg <?= $m['direction'] === 'out' ? 'chat__msg--out' : '' ?>"><?= nl2br(e($m['body'])) ?><small><?= $m['direction'] === 'out' ? 'Bot' : 'Customer' ?></small></div><?php endforeach; ?>
      </div></div>
    <?php else: ?>
      <?php foreach ($convos as $c): ?>
        <div class="list-row"><div class="avatar"><i class="fa-brands fa-<?= $c['channel'] === 'messenger' ? 'facebook-messenger' : $c['channel'] ?>"></i></div>
          <div class="list-row__main"><strong><a href="?ch=<?= e($c['channel']) ?>&chat=<?= e(rawurlencode($c['sender'])) ?>#chat"><?= e($c['channel'] === 'whatsapp' ? '+' . $c['sender'] : ucfirst($c['channel']) . ' user') ?></a></strong><small><?= (int) $c['n'] ?> messages</small></div>
          <div class="list-row__side"><?= e(timeAgo($c['last'])) ?></div></div>
      <?php endforeach; if (!$convos): ?><div class="empty"><i class="fa-regular fa-comments"></i>No chats yet.</div><?php endif; ?>
    <?php endif; ?>
  </section>
</div>

<section class="card">
  <div class="card__head"><h2>Post log</h2></div>
  <table class="tbl"><thead><tr><th>When</th><th>Where</th><th>Product</th><th>Caption</th><th>Result</th></tr></thead><tbody>
  <?php foreach ($log as $l): ?>
    <tr><td data-label="When"><?= e(timeAgo($l['created_at'])) ?></td><td data-label="Where"><i class="fa-brands fa-<?= e($l['platform']) ?>"></i> <?= e(ucfirst($l['platform'])) ?></td>
      <td data-label="Product"><?= e($l['name'] ?? '—') ?></td><td data-label="Caption"><?= e(excerpt($l['caption'], 90)) ?></td>
      <td data-label="Result"><?= $l['ok'] ? '<span class="tag tag-on">Posted</span>' : '<span class="tag tag-off" title="' . e($l['error']) . '">Failed</span> <small class="hint">' . e(excerpt($l['error'], 60)) . '</small>' ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <?php if (!$log): ?><div class="empty"><i class="fa-solid fa-share-nodes"></i>Nothing posted yet.</div><?php endif; ?>
</section>
<?php adminClose(); ?>
