<?php
declare(strict_types=1);
// Served at /sitemap.xml (see .htaccess). Submit it in Google Search Console.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/catalog.php';
require_once __DIR__ . '/includes/seo.php';

header('Content-Type: application/xml; charset=UTF-8');
$u = CANONICAL_URL;
$urls = [[$u . '/', '1.0', 'weekly']];
foreach (array_keys(landings()) as $slug) $urls[] = ["{$u}/{$slug}", in_array($slug, ['invoice-books-johannesburg', 'paper-supplier-johannesburg'], true) ? '0.95' : '0.8', 'monthly'];
foreach (array_keys(SECTIONS) as $s) $urls[] = ["{$u}/shop.php?section={$s}", '0.7', 'weekly'];
foreach (categories() as $c) $urls[] = ["{$u}/shop/{$c['slug']}", $c['section'] === 'paper' ? '0.8' : '0.7', 'weekly'];
foreach (getDB()->query('SELECT p.slug, p.updated_at, c.section FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1') as $p) {
    $urls[] = ["{$u}/product/{$p['slug']}", in_array($p['slug'], ['invoice-books', 'ncr-paper', 'gloss-paper', 'matte-paper', 'till-rolls'], true) ? '0.9' : '0.7', 'weekly', $p['updated_at']];
}
foreach (['artwork-guidelines.php' => '0.6', 'request.php?type=design' => '0.7', 'request.php?type=installation' => '0.6', 'contact.php' => '0.6', 'blog.php' => '0.6'] as $path => $pr) $urls[] = ["{$u}/{$path}", $pr, 'monthly'];
foreach (getDB()->query("SELECT slug, published_at FROM posts WHERE status = 'published'") as $p) $urls[] = ["{$u}/blog/{$p['slug']}", '0.6', 'yearly', $p['published_at']];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $row) {
    echo '  <url><loc>' . htmlspecialchars($row[0], ENT_XML1) . '</loc>'
       . (isset($row[3]) ? '<lastmod>' . date('Y-m-d', strtotime($row[3])) . '</lastmod>' : '')
       . '<changefreq>' . $row[2] . '</changefreq><priority>' . $row[1] . "</priority></url>\n";
}
echo '</urlset>';
