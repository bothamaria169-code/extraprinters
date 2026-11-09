<?php
declare(strict_types=1);

/**
 * Sections, categories and products, plus product photos with an
 * easy-to-replace placeholder.
 */

const SECTIONS = [
    'paper'    => ['Paper', 'All kinds of paper in A1–A4, every weight', 'fa-scroll', 'cyan'],
    'printing' => ['Printing', 'Business stationery, marketing print and branded goods', 'fa-print', 'magenta'],
    'signage'  => ['Signage', 'Banners, displays, flags and lightboxes', 'fa-sign-hanging', 'yellow'],
    'services' => ['Services', 'Graphic design and signage installation', 'fa-pen-ruler', 'blue'],
];

function categories(?string $section = null): array
{
    static $all = null;
    $all ??= getDB()->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS n FROM categories c ORDER BY c.sort')->fetchAll();
    return $section ? array_values(array_filter($all, fn($c) => $c['section'] === $section)) : $all;
}

function category(string $slug): ?array
{
    foreach (categories() as $c) if ($c['slug'] === $slug) return $c;
    return null;
}

/** @param array{section?: string, category_id?: int, featured?: bool, q?: string, limit?: int} $f */
function products(array $f = []): array
{
    $sql = 'SELECT p.*, c.slug AS cat_slug, c.name AS cat_name, c.section, c.color, c.icon FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1';
    $args = [];
    if (!empty($f['section']))     { $sql .= ' AND c.section = ?'; $args[] = $f['section']; }
    if (!empty($f['category_id'])) { $sql .= ' AND p.category_id = ?'; $args[] = $f['category_id']; }
    if (!empty($f['featured']))    { $sql .= ' AND p.featured = 1'; }
    if (!empty($f['q']))           { $sql .= ' AND (p.name LIKE ? OR p.tagline LIKE ? OR c.name LIKE ?)'; $q = '%' . $f['q'] . '%'; array_push($args, $q, $q, $q); }
    $sql .= ' ORDER BY c.sort, p.sort';
    if (!empty($f['limit'])) $sql .= ' LIMIT ' . (int) $f['limit'];
    $s = getDB()->prepare($sql);
    $s->execute($args);
    return $s->fetchAll();
}

function product(string $slugOrId): ?array
{
    $s = getDB()->prepare('SELECT p.*, c.slug AS cat_slug, c.name AS cat_name, c.section, c.color, c.icon FROM products p JOIN categories c ON c.id = p.category_id WHERE ' . (ctype_digit($slugOrId) ? 'p.id' : 'p.slug') . ' = ?');
    $s->execute([$slugOrId]);
    return $s->fetch() ?: null;
}

function productOptions(array $p): array
{
    return json_decode((string) $p['options'], true) ?: [];
}

function productUrl(array $p): string
{
    return APP_URL . '/product/' . $p['slug'];
}

function categoryUrl(array $c): string
{
    return APP_URL . '/shop/' . $c['slug'];
}

/**
 * Photo URLs for a product, in order:
 *  1. photos uploaded in Admin → Products (uploads/products/…)
 *  2. a file named after the slug in assets/products/ (e.g. invoice-books.jpg)
 *  3. [] → the caller shows the branded placeholder
 */
function productImages(array $p): array
{
    $out = [];
    foreach (json_decode((string) ($p['images'] ?? ''), true) ?: [] as $f) {
        if (is_file(PRODUCT_IMG_DIR . basename($f))) $out[] = APP_URL . '/uploads/products/' . rawurlencode(basename($f));
    }
    if (!$out) {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            if (is_file(BASE_DIR . "/assets/products/{$p['slug']}.{$ext}")) {
                $out[] = APP_URL . "/assets/products/{$p['slug']}.{$ext}?v=" . filemtime(BASE_DIR . "/assets/products/{$p['slug']}.{$ext}");
                break;
            }
        }
    }
    return $out;
}

/** The main photo, or the placeholder inline. Square, lazy-loaded. */
function productImageHtml(array $p, string $size = 'card', bool $eager = false): string
{
    $imgs = productImages($p);
    if ($imgs) {
        return '<img src="' . e($imgs[0]) . '" alt="' . e($p['name'] . ' – Extra Printers Johannesburg') . '" width="600" height="600"'
             . ($eager ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async">';
    }
    return placeholderHtml($p['name'], $p['color'] ?? 'blue', $p['icon'] ?? 'fa-print', $size);
}

/**
 * The branded placeholder: colour block in the category's CMYK colour with
 * the logo petals, the category icon and the product name. Replace it by
 * uploading a photo; nothing else needs changing.
 */
function placeholderHtml(string $name, string $color, string $icon, string $size = 'card'): string
{
    return '<div class="ph ph--' . e($color) . ' ph--' . e($size) . '" role="img" aria-label="' . e($name) . '">'
         . '<span class="ph__petals"><i></i><i></i><i></i><i></i></span>'
         . '<i class="fa-solid ' . e($icon) . ' ph__icon"></i>'
         . '<span class="ph__name">' . e($name) . '</span>'
         . '<span class="ph__tag">Mockup coming soon</span></div>';
}

function priceHtml(array $p): string
{
    if ($p['price_from'] !== null) {
        return '<span class="price">' . ($p['fixed_price'] ? '' : '<small>from</small> ') . money($p['price_from'])
             . ($p['price_note'] ? ' <small>' . e($p['price_note']) . '</small>' : '') . '</span>';
    }
    return '<span class="price price--quote"><i class="fa-solid fa-file-invoice"></i> Get a quote</span>';
}

function productWaMessage(array $p): string
{
    return "Hi Extra Printers, I'd like a quote for {$p['name']}. " . productUrl($p);
}

function productCard(array $p): string
{
    ob_start(); ?>
    <article class="pcard pcard--<?= e($p['color']) ?>">
      <a href="<?= e(productUrl($p)) ?>" class="pcard__img"><?= productImageHtml($p) ?></a>
      <div class="pcard__body">
        <a href="<?= e(categoryUrl(['slug' => $p['cat_slug']])) ?>" class="pcard__cat"><?= e($p['cat_name']) ?></a>
        <h3><a href="<?= e(productUrl($p)) ?>"><?= e($p['name']) ?></a></h3>
        <p><?= e($p['tagline']) ?></p>
        <div class="pcard__foot">
          <?= priceHtml($p) ?>
          <div class="pcard__btns">
            <a class="btn btn-wa btn-sm" href="<?= e(waLink(productWaMessage($p))) ?>" target="_blank" rel="noopener" data-track="whatsapp_click" data-product="<?= e($p['slug']) ?>" aria-label="WhatsApp about <?= e($p['name']) ?>"><i class="fa-brands fa-whatsapp"></i></a>
            <a class="btn btn-primary btn-sm" href="<?= e(productUrl($p)) ?>"><i class="fa-solid fa-cart-plus"></i> Add</a>
          </div>
        </div>
      </div>
    </article>
    <?php return (string) ob_get_clean();
}
