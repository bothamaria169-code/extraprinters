<?php
declare(strict_types=1);

/**
 * Meta webhook for the chat bot: WhatsApp Cloud API, Messenger and Instagram.
 *
 *   Callback URL : https://extraprinters.co.za/webhook.php
 *   Verify token : Admin → Social & bots → Chat bot → Verify token
 *   Fields       : WhatsApp "messages"; Messenger + Instagram "messages"
 *
 * Every POST is checked against the app secret (X-Hub-Signature-256).
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/social.php';

// 1. Verification handshake (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode  = $_GET['hub_mode'] ?? '';
    $token = (string) ($_GET['hub_verify_token'] ?? '');
    $want  = get_setting('chat_verify_token');
    if ($mode === 'subscribe' && $want !== '' && hash_equals($want, $token)) {
        header('Content-Type: text/plain');
        echo (string) ($_GET['hub_challenge'] ?? '');
        exit;
    }
    http_response_code(403);
    exit('Verification failed');
}

// 2. Events (POST): check the signature, answer 200 at once, then work.
$raw = (string) file_get_contents('php://input');
$secret = trim(get_setting('meta_app_secret'));
$sig = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
if ($secret !== '' && !hash_equals('sha256=' . hash_hmac('sha256', $raw, $secret), $sig)) {
    http_response_code(403);
    exit;
}
http_response_code(200);
header('Content-Type: text/plain');
echo 'EVENT_RECEIVED';
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
ignore_user_abort(true);
set_time_limit(60);

$payload = json_decode($raw, true) ?: [];
$object = (string) ($payload['object'] ?? '');

try {
    foreach ($payload['entry'] ?? [] as $entry) {
        if ($object === 'whatsapp_business_account') {
            foreach ($entry['changes'] ?? [] as $change) {
                $v = $change['value'] ?? [];
                $names = [];
                foreach ($v['contacts'] ?? [] as $ct) $names[$ct['wa_id'] ?? ''] = $ct['profile']['name'] ?? '';
                foreach ($v['messages'] ?? [] as $m) {
                    $from = (string) ($m['from'] ?? '');
                    $text = match ($m['type'] ?? '') {
                        'text'        => (string) ($m['text']['body'] ?? ''),
                        'button'      => (string) ($m['button']['text'] ?? ''),
                        'interactive' => (string) ($m['interactive']['button_reply']['title'] ?? $m['interactive']['list_reply']['title'] ?? ''),
                        'image', 'document' => '[sent a file] ' . (string) ($m[$m['type']]['caption'] ?? ''),
                        default       => '',
                    };
                    if ($from !== '' && $text !== '') handleIncomingChat('whatsapp', $from, $text, $names[$from] ?? '');
                }
            }
        } elseif ($object === 'page' || $object === 'instagram') {
            $channel = $object === 'page' ? 'messenger' : 'instagram';
            $ownId = $object === 'page' ? get_setting('meta_page_id') : get_setting('meta_instagram_id');
            foreach ($entry['messaging'] ?? [] as $ev) {
                $from = (string) ($ev['sender']['id'] ?? '');
                if ($from === '' || $from === $ownId) continue;
                if (!empty($ev['message']['is_echo'])) {
                    // A team member replied from the inbox: let them take over.
                    pauseChat($channel, (string) ($ev['recipient']['id'] ?? ''));
                    continue;
                }
                $text = (string) ($ev['message']['text'] ?? $ev['postback']['title'] ?? '');
                if ($text === '' && !empty($ev['message']['attachments'])) $text = '[sent a file]';
                if ($text !== '') handleIncomingChat($channel, $from, $text);
            }
        }
    }
} catch (Throwable $e) {
    error_log('Extra Printers webhook: ' . $e->getMessage());
}
