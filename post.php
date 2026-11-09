<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

$s = getDB()->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'published'");
$s->execute([preg_replace('/[^a-z0-9-]/', '', (string) ($_GET['slug'] ?? ''))]);
$post = $s->fetch();
if (!$post) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$prod = $post['product_id'] ? product((string) $post['product_id']) : null;

$article = ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post['title'], 'description' => $post['excerpt'],
    'datePublished' => date('c', strtotime($post['published_at'])), 'author' => ['@type' => 'Organization', 'name' => 'Extra Printers'],
    'publisher' => ['@id' => CANONICAL_URL . '/#business'], 'mainEntityOfPage' => CANONICAL_URL . '/blog/' . $post['slug'],
    'image' => CANONICAL_URL . '/assets/img/og-default.jpg'];
siteOpen(['title' => $post['title'] . ' | Extra Printers', 'description' => $post['excerpt'], 'path' => 'blog/' . $post['slug'], 'type' => 'article', 'active' => 'blog',
          'schema' => [$article, schemaBreadcrumb([['Home', CANONICAL_URL . '/'], ['Tips', CANONICAL_URL . '/blog.php'], [$post['title'], CANONICAL_URL . '/blog/' . $post['slug']]])]]);
?>
<header class="phead"><div class="wrap">
  <?= breadcrumbsHtml([['Home', APP_URL . '/'], ['Tips', url('blog.php')], [$post['title'], '']]) ?>
  <h1 style="max-width:860px"><?= e($post['title']) ?></h1>
  <p><?= e(date('j F Y', strtotime($post['published_at']))) ?> · Extra Printers, Johannesburg</p>
</div></header>
<section class="section" style="padding-top:32px"><div class="wrap split" style="align-items:start;grid-template-columns:1fr 320px">
  <article class="prose"><?= $post['body'] /* AI output is stripped to safe tags in ai_writers.php */ ?></article>
  <?php if ($prod): ?><aside style="position:sticky;top:92px"><?= productCard($prod) ?></aside><?php endif; ?>
</div></section>
<?php siteClose(); ?>
