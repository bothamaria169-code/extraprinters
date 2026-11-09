<?php
declare(strict_types=1);

/**
 * AI blog: one article per scheduled day, working down the keyword list in
 * Admin → Blog (invoice books and paper first, per the SEO plan).
 */
require_once __DIR__ . '/ai_writers.php';
require_once __DIR__ . '/catalog.php';

const BLOG_DEFAULT_KEYWORDS = [
    'invoice books Johannesburg | invoice-books',
    'duplicate vs triplicate invoice books | invoice-books',
    'how to design an invoice book | invoice-books',
    'NCR paper explained | ncr-paper',
    'paper weights gsm guide | gloss-paper',
    'gloss vs matte paper | matte-paper',
    'paper supplier Johannesburg | gloss-paper',
    'till rolls sizes guide | till-rolls',
    'carbonless paper CB CFB CF | carbonless-paper',
    'sticker paper for labels | sticker-paper',
    'business card printing Johannesburg | business-cards',
    'A5 flyer printing tips | flyers',
    'pull up banner design tips | pull-up-banner',
    'branded calendars for business | calendars',
    'company profile printing guide | company-profiles',
];

function blogKeywords(): array
{
    $raw = get_setting('blog_keywords', implode("\n", BLOG_DEFAULT_KEYWORDS));
    return array_values(array_filter(array_map('trim', explode("\n", $raw))));
}

function blogDue(?int $now = null): bool
{
    $now ??= time();
    if (get_setting('blog_auto') !== '1' || !aiEnabled()) return false;
    $days = array_filter(explode(',', get_setting('blog_days', '2,5')));
    return in_array(date('N', $now), $days, true) && date('H', $now) >= '09' && get_setting('blog_last') !== date('Y-m-d', $now);
}

/** Writes and publishes the article for the next unused keyword. */
function blogWriteNext(?string $line = null): array
{
    $used = getDB()->query('SELECT keyword FROM posts')->fetchAll(PDO::FETCH_COLUMN);
    if ($line === null) {
        foreach (blogKeywords() as $k) if (!in_array(trim(explode('|', $k)[0]), $used, true)) { $line = $k; break; }
        $line ??= blogKeywords()[array_rand(blogKeywords())] ?? 'printing tips Johannesburg';
    }
    [$keyword, $slug] = array_map('trim', explode('|', $line . '|'));
    $product = $slug !== '' ? product($slug) : null;
    $r = aiArticle($keyword, $product);
    if (!$r['ok']) return $r;
    $base = slugify($r['title']);
    $s = $base;
    $taken = getDB()->prepare('SELECT COUNT(*) FROM posts WHERE slug = ?');
    for ($i = 2; $taken->execute([$s]) && $taken->fetchColumn(); $i++) $s = $base . '-' . $i;
    getDB()->prepare("INSERT INTO posts (slug, title, excerpt, body, keyword, product_id, status) VALUES (?, ?, ?, ?, ?, ?, 'published')")
        ->execute([$s, $r['title'], $r['excerpt'], $r['body'], $keyword, $product['id'] ?? null]);
    return ['ok' => true, 'title' => $r['title'], 'slug' => $s];
}
