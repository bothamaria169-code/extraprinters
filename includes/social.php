<?php
declare(strict_types=1);

/**
 * Meta auto-bots (SWIFTDNA):
 *  1. Auto-posting: cron.php posts one product to Facebook + Instagram on the
 *     days and times set in Admin → Social & bots, with an AI caption.
 *  2. Chat bot: webhook.php answers WhatsApp, Messenger and Instagram DMs with
 *     Groq, using the shop's own products and contact details.
 */
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/ai_writers.php';
require_once BASE_DIR . '/classes/MetaPoster.php';

const POST_DAYS = ['1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '7' => 'Sun'];

// ── Auto-posting ──────────────────────────────────────────────────
function autopostConfig(): array
{
    return [
        'on'    => get_setting('autopost_on') === '1',
        'days'  => array_filter(explode(',', get_setting('autopost_days', '1,3,5'))),
        'time'  => get_setting('autopost_time', '08:00'),
        'fb'    => get_setting('autopost_fb', '1') === '1',
        'ig'    => get_setting('autopost_ig', '1') === '1',
    ];
}

/** True when a scheduled slot is due now and hasn't run yet today. */
function autopostDue(?int $now = null): bool
{
    $c = autopostConfig();
    $now ??= time();
    if (!$c['on'] || !in_array(date('N', $now), $c['days'], true)) return false;
    if (date('H:i', $now) < $c['time']) return false;
    return get_setting('autopost_last') !== date('Y-m-d', $now);
}

/** The active product posted longest ago, preferring ones with a real photo. */
function autopostPick(): ?array
{
    $rows = getDB()->query('SELECT p.*, c.slug AS cat_slug, c.name AS cat_name, c.section, c.color, c.icon FROM products p JOIN categories c ON c.id = p.category_id
                            WHERE p.is_active = 1 ORDER BY p.last_posted_at IS NOT NULL, p.last_posted_at, p.featured DESC, p.sort')->fetchAll();
    foreach ($rows as $r) if (productImages($r)) return $r;
    return $rows[0] ?? null;
}

/** Posts one product now. @return array<string, array{ok: bool, error: string}> */
function autopostRun(?array $p = null): array
{
    $p ??= autopostPick();
    if (!$p) return ['all' => ['ok' => false, 'error' => 'No products to post.']];
    $c = autopostConfig();
    $img = productImages($p)[0] ?? null;
    // Meta downloads the photo itself, so it must be a public https address.
    $img = $img ? preg_replace('/\?v=\d+$/', '', str_replace(APP_URL, CANONICAL_URL, $img)) : CANONICAL_URL . '/assets/img/og-default.jpg';
    $out = [];
    foreach (['facebook' => $c['fb'], 'instagram' => $c['ig']] as $platform => $want) {
        if (!$want || !MetaPoster::connected($platform)) continue;
        $caption = aiCaption($p, $platform);
        $r = MetaPoster::publish($platform, $caption, $img, 'image');
        getDB()->prepare('INSERT INTO social_posts (platform, product_id, caption, ok, ext_id, error) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$platform, $p['id'], $caption, $r['ok'] ? 1 : 0, $r['id'], mb_substr($r['error'], 0, 500)]);
        $out[$platform] = ['ok' => $r['ok'], 'error' => $r['error']];
    }
    getDB()->prepare('UPDATE products SET last_posted_at = NOW() WHERE id = ?')->execute([$p['id']]);
    return $out ?: ['all' => ['ok' => false, 'error' => 'Facebook / Instagram are not connected yet.']];
}

// ── Chat bot ──────────────────────────────────────────────────────
function chatbotOn(): bool
{
    return get_setting('chatbot_on') === '1';
}

function chatHistory(string $channel, string $sender): array
{
    $s = getDB()->prepare('SELECT direction, body FROM chat_log WHERE channel = ? AND sender = ? AND created_at > (NOW() - INTERVAL 2 DAY) ORDER BY id DESC LIMIT 10');
    $s->execute([$channel, $sender]);
    return array_reverse($s->fetchAll());
}

function chatLog(string $channel, string $sender, string $direction, string $body): void
{
    getDB()->prepare('INSERT INTO chat_log (channel, sender, direction, body) VALUES (?, ?, ?, ?)')->execute([$channel, $sender, $direction, mb_substr($body, 0, 4000)]);
}

/** A human asked for, or a team member replied recently: the bot stays quiet for 12 hours. */
function chatPaused(string $channel, string $sender): bool
{
    return get_setting("chat_pause_{$channel}_" . md5($sender)) > date('Y-m-d H:i:s');
}

function pauseChat(string $channel, string $sender, int $hours = 12): void
{
    set_setting("chat_pause_{$channel}_" . md5($sender), date('Y-m-d H:i:s', time() + $hours * 3600));
}

/** Handles one incoming message and sends the reply. */
function handleIncomingChat(string $channel, string $sender, string $text, string $name = ''): void
{
    $text = trim($text);
    if ($text === '') return;
    $history = chatHistory($channel, $sender);
    chatLog($channel, $sender, 'in', $text);
    if (!chatbotOn() || chatPaused($channel, $sender)) return;

    $wantsHuman = (bool) preg_match('/\b(human|agent|person|consultant|manager|speak to someone|call me)\b/i', $text);
    $ai = $wantsHuman ? null : aiChatReply($history, $text);
    $handoff = $wantsHuman || ($ai['handoff'] ?? false);
    $reply = $ai['reply'] ?? ($wantsHuman
        ? 'No problem, a team member will reply here during business hours (' . biz('hours') . ').'
        : 'Thanks for your message! A team member will reply during business hours (' . biz('hours') . '). You can also get a quote at ' . CANONICAL_URL);

    $sent = match ($channel) {
        'whatsapp'  => whatsappSend($sender, $reply),
        default     => MetaPoster::sendMessage($sender, $reply),
    };
    if ($sent['ok']) chatLog($channel, $sender, 'out', $reply);
    else error_log("Extra Printers chat bot ({$channel}) send failed: " . $sent['error']);

    if ($handoff) {
        pauseChat($channel, $sender);
        $to = array_unique(array_filter([get_setting('notify_email', biz('email')), ADMIN_EMAIL]));
        $link = $channel === 'whatsapp' ? '<p><a href="https://wa.me/' . e(preg_replace('/\D/', '', $sender)) . '">Open the chat on WhatsApp</a></p>' : '<p>Reply in Meta Business Suite → Inbox.</p>';
        sendMail($to, 'Chat needs a person: ' . ($name ?: $sender) . ' (' . ucfirst($channel) . ')',
            emailLayout('A customer wants to talk to a person', '<p><strong>' . e($name ?: $sender) . '</strong> on ' . ucfirst($channel) . ' wrote:</p><blockquote>' . nl2br(e($text)) . '</blockquote>' . $link
                . '<p style="color:#7c8899;font-size:13px">The bot stays quiet in this chat for 12 hours.</p>'));
    }
}

/** WhatsApp Cloud API text message. */
function whatsappSend(string $to, string $text): array
{
    $phoneId = preg_replace('/\D/', '', get_setting('wa_phone_id'));
    $token = trim(get_setting('wa_token'));
    if ($phoneId === '' || $token === '') return ['ok' => false, 'id' => '', 'error' => 'WhatsApp is not set up'];
    $ch = curl_init('https://graph.facebook.com/' . MetaPoster::GRAPH_VERSION . '/' . $phoneId . '/messages');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'text', 'text' => ['body' => mb_substr($text, 0, 4000), 'preview_url' => true]])]);
    $res = json_decode((string) curl_exec($ch), true) ?: [];
    curl_close($ch);
    $id = (string) ($res['messages'][0]['id'] ?? '');
    return ['ok' => $id !== '', 'id' => $id, 'error' => (string) ($res['error']['message'] ?? ($id ? '' : 'No reply from WhatsApp'))];
}
