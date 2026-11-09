<?php
declare(strict_types=1);

/**
 * What the AI writes for Extra Printers. The Groq plumbing is in ai.php.
 */
require_once __DIR__ . '/ai.php';

function logAi(string $kind, bool $ok): void
{
    getDB()->prepare('INSERT INTO ai_requests (kind, ok) VALUES (?, ?)')->execute([$kind, $ok ? 1 : 0]);
}

/** What the AI knows about the shop: contact details, rules and every product with its link. */
function shopContext(bool $withProducts = true): string
{
    $lines = [
        'Business: ' . biz('name') . ', a printing company and paper supplier in ' . biz('city') . ', South Africa.',
        'Address: ' . (bizAddress() ?: biz('city')),
        'Phone: ' . (biz('phone') ?: 'see website') . ' | WhatsApp: ' . (biz('whatsapp') ?: 'see website') . ' | Email: ' . biz('email'),
        'Hours: ' . biz('hours'),
        'Areas served: ' . biz('area') . ' (collection or delivery across Gauteng).',
        'How to order: add products to the cart on the website, upload artwork, send the quote request; or WhatsApp us.',
        'Artwork: PDF in CMYK, 300 dpi, 3 mm bleed, 5 mm safe zone, fonts outlined. Up to 5 files of 50 MB per product.',
        'Graphic design: R300 per artwork. Prices for everything else are quoted on request.',
        'Website: ' . CANONICAL_URL,
    ];
    if ($withProducts) {
        $rows = getDB()->query('SELECT name, slug, tagline, price_from, price_note FROM products WHERE is_active = 1 ORDER BY sort')->fetchAll();
        $lines[] = 'Products:';
        foreach ($rows as $r) {
            $lines[] = "- {$r['name']}: {$r['tagline']}" . ($r['price_from'] ? ' (from ' . money($r['price_from']) . ' ' . $r['price_note'] . ')' : '') . ' — ' . CANONICAL_URL . '/product/' . $r['slug'];
        }
    }
    return implode("\n", $lines);
}

/**
 * Product description + SEO title/description for Admin → Products → "Write with AI".
 * @return array{ok: bool, description?: string, seo_title?: string, seo_description?: string, error?: string}
 */
function aiProductCopy(array $product, string $category): array
{
    if (!aiEnabled()) return ['ok' => false, 'error' => 'AI writing is not switched on. Add a Groq key in Settings.'];
    $opts = implode('; ', array_map(fn($g) => $g['label'] . ': ' . implode(', ', $g['values']), json_decode((string) $product['options'], true) ?: []));
    $system = <<<'SYS'
You write product copy for a Johannesburg print shop's website. South African English. Answer ONLY with JSON:
{"description":"","seo_title":"","seo_description":""}
- description: 110–170 words, 2–3 short paragraphs separated by a blank line, plain text (no markdown). Say who it is for, the options available, and why to order from us (Johannesburg collection or Gauteng delivery, help with artwork). Naturally include the main keyword and "Johannesburg" once. No prices unless given. No made-up claims (no "same day", no awards).
- seo_title: max 60 characters, like "<Product> Printing Johannesburg | Extra Printers".
- seo_description: max 155 characters, ending with a call to action ("Get a quote", "WhatsApp us").
SYS;
    $r = groqJson($system, "PRODUCT: {$product['name']}\nCATEGORY: {$category}\nTAGLINE: {$product['tagline']}\nOPTIONS: {$opts}\n\nSHOP\n" . shopContext(false));
    $d = trim((string) ($r['data']['description'] ?? ''));
    $ok = $r['ok'] && mb_strlen($d) > 80;
    logAi('product', $ok);
    if (!$ok) return ['ok' => false, 'error' => 'The AI could not write it just now' . ($r['error'] ? ' (' . $r['error'] . ')' : '') . '.'];
    return ['ok' => true, 'description' => $d,
        'seo_title' => mb_substr(trim((string) ($r['data']['seo_title'] ?? '')), 0, 70),
        'seo_description' => mb_substr(trim((string) ($r['data']['seo_description'] ?? '')), 0, 160)];
}

/**
 * One SEO blog article around a keyword, linking to the matching product.
 * @return array{ok: bool, title?: string, excerpt?: string, body?: string, error?: string}
 */
function aiArticle(string $keyword, ?array $product): array
{
    if (!aiEnabled()) return ['ok' => false, 'error' => 'AI writing is not switched on.'];
    $system = <<<'SYS'
You write helpful blog articles for a Johannesburg print shop so it ranks on Google. South African English. Answer ONLY with JSON:
{"title":"","excerpt":"","body":""}
- title: max 65 characters, contains the keyword, useful (a guide, checklist or comparison), not clickbait.
- excerpt: max 155 characters.
- body: 600–850 words in simple HTML using only <h2>, <h3>, <p>, <ul>, <li>, <strong>. Start with a short intro paragraph (no <h1>). Practical, specific advice (sizes, paper weights, quantities, artwork tips, how to choose). Mention Johannesburg naturally 2–3 times. End with an <h2>Get a quote from Extra Printers</h2> section inviting readers to add the product to their quote or WhatsApp us.
- Never invent prices, statistics, awards or customer names.
SYS;
    $user = "KEYWORD: {$keyword}\n" . ($product ? "MAIN PRODUCT: {$product['name']} — {$product['tagline']}\n" : '') . "\nSHOP\n" . shopContext();
    $r = groqJson($system, $user, 2600);
    $body = trim((string) ($r['data']['body'] ?? ''));
    $ok = $r['ok'] && mb_strlen(strip_tags($body)) > 1500;
    logAi('article', $ok);
    if (!$ok) return ['ok' => false, 'error' => $r['error'] ?: 'The article came back too short.'];
    return ['ok' => true,
        'title'   => mb_substr(trim((string) $r['data']['title']), 0, 190),
        'excerpt' => mb_substr(trim((string) ($r['data']['excerpt'] ?? '')), 0, 290),
        'body'    => strip_tags($body, '<h2><h3><p><ul><ol><li><strong><em>')];
}

/** Social caption for a product post. Template fallback when AI is off or fails. */
function aiCaption(array $product, string $platform): string
{
    $link = CANONICAL_URL . '/product/' . $product['slug'];
    $fallback = "{$product['name']} 🖨️\n{$product['tagline']}.\n\n✅ Printed in Johannesburg\n✅ Upload your artwork online or let us design it (R300)\n✅ Collection or delivery across Gauteng\n\n"
              . ($platform === 'instagram' ? 'Get a quote: link in bio or WhatsApp us' : "Get a quote: {$link}")
              . "\n\n#ExtraPrinters #PrintingJohannesburg #Johannesburg #PrintShop #SmallBusinessSA";
    if (!aiEnabled()) return $fallback;
    $system = 'You write social media posts for a Johannesburg print shop. South African English, upbeat but not cheesy, 2–4 relevant emojis. '
            . 'Answer ONLY with JSON {"caption":""}. 50–90 words: a hook in the first line, 3 short benefit lines, a call to action, then 6–9 hashtags including #ExtraPrinters and #PrintingJohannesburg. '
            . ($platform === 'instagram' ? 'Instagram: no links; say "link in bio" or "WhatsApp us".' : 'Facebook: include this link once: ' . $link)
            . ' Never invent prices or discounts.';
    $r = groqJson($system, "PRODUCT: {$product['name']} — {$product['tagline']}\n\nSHOP\n" . shopContext(false), 500);
    $c = trim((string) ($r['data']['caption'] ?? ''));
    logAi('caption', $c !== '');
    return $c !== '' ? $c : $fallback;
}

/**
 * Chat-bot reply for WhatsApp / Messenger / Instagram DMs.
 * @param array<int, array{direction: string, body: string}> $history oldest first
 * @return array{reply: string, handoff: bool}|null
 */
function aiChatReply(array $history, string $message): ?array
{
    if (!aiEnabled()) return null;
    $system = "You are the friendly assistant for Extra Printers on WhatsApp and Messenger. Reply in the customer's language (English, isiZulu, Afrikaans, Sesotho and so on), in at most 80 words, plain text, at most one emoji.\n"
            . "- Answer only from the SHOP facts below. Never make up prices, stock, turnaround times or discounts. For prices, say we'll send a quote and share the product link so they can add it to their quote with their artwork.\n"
            . "- Ask for the details a printer needs (size, quantity, paper, sides, deadline) when they are missing.\n"
            . "- If they ask for a person, are upset, or the question is outside the facts, say a team member will reply during business hours, and set handoff to true.\n"
            . 'Answer ONLY with JSON {"reply":"","handoff":false}.' . "\n\nSHOP\n" . shopContext();
    $convo = '';
    foreach (array_slice($history, -8) as $h) $convo .= ($h['direction'] === 'in' ? 'Customer: ' : 'You: ') . $h['body'] . "\n";
    $r = groqJson($system, $convo . 'Customer: ' . $message, 400);
    $reply = trim((string) ($r['data']['reply'] ?? ''));
    logAi('chat', $reply !== '');
    return $reply === '' ? null : ['reply' => $reply, 'handoff' => !empty($r['data']['handoff'])];
}
