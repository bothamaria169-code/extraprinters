<?php
declare(strict_types=1);

/**
 * AI writing on Groq's free API (OpenAI-compatible): product descriptions,
 * SEO blog articles, social captions and chat-bot replies.
 *
 * Groq retires models from time to time, so the model is not fixed: the key's
 * own model list is read from Groq, the best one in GROQ_PREFERRED is used,
 * and a retired model is swapped for the next best automatically.
 *
 * Key: console.groq.com → API Keys → Admin → Settings → AI writing.
 */
const GROQ_PREFERRED = [
    'openai/gpt-oss-120b', 'qwen/qwen3.6-27b', 'moonshotai/kimi-k2-instruct-0905', 'moonshotai/kimi-k2-instruct',
    'openai/gpt-oss-20b', 'meta-llama/llama-4-maverick-17b-128e-instruct', 'meta-llama/llama-4-scout-17b-16e-instruct',
    'qwen/qwen3-32b', 'llama-3.3-70b-versatile',
];

/** Chat models this key can use, from Groq (cached for a day). Empty on failure. */
function groqModels(bool $refresh = false): array
{
    $cached = json_decode(get_setting('groq_models_cache'), true) ?: [];
    if (!$refresh && !empty($cached['at']) && $cached['at'] > time() - 86400 && !empty($cached['ids'])) return $cached['ids'];
    if (aiKey() === '') return [];
    $ch = curl_init('https://api.groq.com/openai/v1/models');
    curl_setopt_array($ch, [CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . aiKey()], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8]);
    $res = json_decode((string) curl_exec($ch), true) ?: [];
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return $cached['ids'] ?? [];
    $ids = [];
    foreach ($res['data'] ?? [] as $m) {
        $id = (string) ($m['id'] ?? '');
        if ($id === '' || (isset($m['active']) && !$m['active'])) continue;
        // Speech, moderation and agent systems can't write letters.
        if (preg_match('/whisper|tts|playai|orpheus|guard|safeguard|prompt-guard|compound|distil/i', $id)) continue;
        $ids[] = $id;
    }
    sort($ids);
    set_setting('groq_models_cache', json_encode(['at' => time(), 'ids' => $ids]));
    return $ids;
}

/** The best available model: the admin's choice if Groq still offers it, else the first preferred one. */
function groqModel(bool $refresh = false): string
{
    $chosen = get_setting('groq_model');
    $ids = groqModels($refresh);
    if (!$ids) return $chosen ?: GROQ_PREFERRED[0];
    if ($chosen !== '' && in_array($chosen, $ids, true)) return $chosen;
    foreach (GROQ_PREFERRED as $m) if (in_array($m, $ids, true)) return $m;
    return $ids[0];
}

/** Pulls the JSON object out of a reply that may wrap it in text or ``` fences. */
function jsonFromText(string $text): ?array
{
    $d = json_decode($text, true);
    if (is_array($d)) return $d;
    if (preg_match('/\{.*\}/s', $text, $m)) {
        $d = json_decode($m[0], true);
        if (is_array($d)) return $d;
    }
    return null;
}
function aiKey(): string
{
    return trim(get_setting('groq_api_key', (string) secret('groq_api_key', '')));
}

function aiEnabled(): bool
{
    return aiKey() !== '' && get_setting('ai_enabled', '1') === '1';
}


/**
 * One chat completion that must answer with JSON.
 *
 * @return array{ok: bool, data: array, error: string}
 */
function groqJson(string $system, string $user, int $maxTokens = 900): array
{
    $model = groqModel();
    $r = groqCall($model, $system, $user, $maxTokens, true);
    // Model retired or not on this key: re-read the list, switch, try once more.
    if (!$r['ok'] && preg_match('/does not exist|do not have access|decommission|deprecat|model_not_found|not supported/i', $r['error'])) {
        $next = groqModel(true);
        if ($next === $model) {
            $ids = groqModels();
            foreach (GROQ_PREFERRED as $m) if ($m !== $model && (!$ids || in_array($m, $ids, true))) { $next = $m; break; }
        }
        if ($next !== $model) {
            set_setting('groq_model', $next);
            error_log("Extra Printers: Groq model {$model} unavailable, switched to {$next}");
            $r = groqCall($next, $system, $user, $maxTokens, true);
        }
    }
    // Some models reject JSON mode; ask again for plain text and read the JSON out of it.
    if (!$r['ok'] && preg_match('/json|response_format/i', $r['error'])) {
        $r = groqCall(groqModel(), $system, $user, $maxTokens, false);
    }
    return $r;
}

/** @return array{ok: bool, data: array, error: string, model: string} */
function groqCall(string $model, string $system, string $user, int $maxTokens, bool $jsonMode): array
{
    $reasoning = (bool) preg_match('#^openai/gpt-oss|qwen3#', $model);
    $body = [
        'model'       => $model,
        'temperature' => 0.5,
        // Reasoning models spend tokens thinking before they answer.
        'max_completion_tokens' => $reasoning ? $maxTokens + 2500 : $maxTokens,
        'messages'    => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]],
    ];
    if ($jsonMode) $body['response_format'] = ['type' => 'json_object'];
    if (str_starts_with($model, 'openai/gpt-oss')) $body['reasoning_effort'] = 'low';
    if (str_starts_with($model, 'qwen/qwen3')) $body['reasoning_format'] = 'hidden';

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . aiKey(), 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 50,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);
    $raw  = (string) curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err !== '') return ['ok' => false, 'data' => [], 'error' => $err, 'model' => $model];
    $res = json_decode($raw, true) ?: [];
    if ($code !== 200) return ['ok' => false, 'data' => [], 'error' => (string) ($res['error']['message'] ?? 'HTTP ' . $code), 'model' => $model];
    $data = jsonFromText((string) ($res['choices'][0]['message']['content'] ?? ''));
    return $data !== null ? ['ok' => true, 'data' => $data, 'error' => '', 'model' => $model] : ['ok' => false, 'data' => [], 'error' => 'The AI answer was not readable.', 'model' => $model];
}

