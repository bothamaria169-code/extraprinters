<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

$paperCats = categories('paper');
$paper     = products(['section' => 'paper']);
$printing  = products(['section' => 'printing']);
$signage   = products(['section' => 'signage']);
$design    = product('graphic-design');
$heroImg   = is_file(BASE_DIR . '/assets/img/hero.webp') ? [assetV('img/hero.webp')] : [];
$posts     = getDB()->query("SELECT slug, title, excerpt, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 3")->fetchAll();

$faq = [
    'Do you sell paper in Johannesburg?' => 'Yes, paper is our speciality. We sell gloss, matte, NCR, carbonless, self-carbonated, sticker and business card paper in A1, A2, A3 and A4, plus till rolls for retail machines, by the pack, ream or box.',
    'Can you print invoice books with my logo?' => 'Yes. We print numbered duplicate, triplicate and quadruplicate invoice books in A6, A5, A4 and A3, with your logo and business details on every set.',
    'How do I send my artwork?' => 'Add a product to your quote cart, then upload your files right there: up to 5 files of 50 MB per product. A CMYK PDF at 300 dpi with 3 mm bleed works best. Bigger files can go in as a WeTransfer or Google Drive link.',
    'I don\'t have a design. Can you help?' => 'Yes. Our designers create print-ready artwork for R300 per artwork. Add "Graphic Design" to your quote or fill in the design request form.',
    'How do I get a price?' => 'Choose your product options, add them to your quote cart and send the request. We reply with a quote by email and WhatsApp. You can also WhatsApp us straight from any product.',
    'Do you deliver?' => 'You can collect from us in Johannesburg, or we deliver across Gauteng, including Sandton, Randburg, Roodepoort, Soweto, Midrand and Germiston.',
];

siteOpen([
    'title'       => 'Extra Printers | Paper Supplier & Printing Company in Johannesburg',
    'description' => 'Johannesburg paper supplier and printer: gloss, matte, NCR and sticker paper in A1–A4, invoice books, business cards, flyers, banners and signage. Upload artwork and get a quote.',
    'path'        => '',
    'schema'      => [schemaFaq($faq), ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Extra Printers', 'url' => CANONICAL_URL . '/',
        'potentialAction' => ['@type' => 'SearchAction', 'target' => CANONICAL_URL . '/shop.php?q={search_term_string}', 'query-input' => 'required name=search_term_string']]],
]);
?>
<section class="hero">
  <div class="wrap hero__grid">
    <div>
      <span class="eyebrow eyebrow--magenta"><i class="fa-solid fa-location-dot"></i> Paper &amp; print specialists in Johannesburg</span>
      <h1>Paper, printing &amp; signage, <span class="grad-text">done extra well.</span></h1>
      <p class="hero__lead">Buy every kind of paper from A4 to A1, print invoice books, business cards, flyers and banners, then upload your artwork and get a fast quote. No design yet? We'll create it for <strong>R300</strong>.</p>
      <div class="hero__cta">
        <a href="#paper" class="btn btn-primary btn-lg"><i class="fa-solid fa-scroll"></i> Shop paper</a>
        <a href="<?= e(url('invoice-books-johannesburg')) ?>" class="btn btn-magenta btn-lg"><i class="fa-solid fa-book"></i> Invoice books</a>
        <a href="<?= e(waLink("Hi Extra Printers, I'd like a quote.")) ?>" class="btn btn-wa btn-lg" target="_blank" rel="noopener" data-track="whatsapp_click"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
      </div>
      <div class="hero__chips">
        <?php foreach (['ncr-paper' => ['NCR paper', 'c-magenta'], 'till-rolls' => ['Till rolls', 'c-yellow'], 'business-cards' => ['Business cards', 'c-blue'], 'flyers' => ['Flyers', 'c-cyan'], 'pull-up-banner' => ['Pull up banners', 'c-magenta']] as $slug => [$label, $cls]): ?>
          <a class="chip" href="<?= e(url('product/' . $slug)) ?>"><i class="fa-solid fa-circle <?= $cls ?>"></i> <?= e($label) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="hero__art">
      <div class="hero__img">
        <?php if ($heroImg): ?><img src="<?= e($heroImg[0]) ?>" alt="Branded desk calendar printed by Extra Printers in Johannesburg" width="600" height="600" fetchpriority="high">
        <?php else: ?><?= placeholderHtml('Extra Printers', 'blue', 'fa-print', 'hero') ?><?php endif; ?>
      </div>
      <div class="float float--1"><span class="float__ico"><i class="fa-solid fa-book"></i></span><div><strong>Invoice books</strong>A6 · A5 · A4 · A3</div></div>
      <div class="float float--2"><span class="float__ico"><i class="fa-solid fa-cloud-arrow-up"></i></span><div><strong>Upload artwork</strong>PDF up to <?= MAX_ARTWORK_MB ?> MB</div></div>
      <div class="float float--3"><span class="float__ico"><i class="fa-solid fa-pen-nib"></i></span><div><strong>Design for R300</strong>per artwork</div></div>
    </div>
  </div>
</section>

<div class="trust"><div class="wrap trust__in">
  <div class="trust__item"><i class="fa-solid fa-scroll"></i><div><strong>Paper specialists</strong><span>A1–A4, 80–400 gsm</span></div></div>
  <div class="trust__item"><i class="fa-solid fa-cloud-arrow-up"></i><div><strong>Upload artwork online</strong><span>Straight from your cart</span></div></div>
  <div class="trust__item"><i class="fa-solid fa-pen-ruler"></i><div><strong>Design for R300</strong><span>Print-ready artwork</span></div></div>
  <div class="trust__item"><i class="fa-solid fa-truck-fast"></i><div><strong>Collect or deliver</strong><span>Across Gauteng</span></div></div>
</div></div>

<section class="section" id="paper">
  <div class="wrap">
    <div class="section__head">
      <span class="eyebrow eyebrow--cyan"><i class="fa-solid fa-scroll"></i> Paper shop</span>
      <h2>All kinds of paper, A1 to A4</h2>
      <p>Gloss, matte, NCR, carbonless, self-carbonated, sticker and business card paper in every weight, plus till rolls for retail machines. Pick a type, choose your size and grams, and add it to your quote.</p>
    </div>
    <div class="filters" data-filter-group="paperGrid" role="toolbar" aria-label="Filter paper by type">
      <button type="button" data-filter="all" class="is-on" aria-pressed="true">All paper</button>
      <?php foreach ($paperCats as $c): ?>
        <button type="button" data-filter="<?= e($c['slug']) ?>" aria-pressed="false"><span class="dot dot--<?= e($c['color']) ?>"></span><?= e($c['name']) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="pgrid" id="paperGrid">
      <?php foreach ($paper as $p): ?><div data-cat="<?= e($p['cat_slug']) ?>"><?= productCard($p) ?></div><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="wrap split">
    <div>
      <span class="eyebrow eyebrow--magenta"><i class="fa-solid fa-book"></i> Most ordered</span>
      <h2 style="font-size:clamp(1.8rem,3.6vw,2.6rem);margin:.6rem 0">Invoice books with your logo, numbered and ready to use</h2>
      <p class="muted">Duplicate, triplicate or quadruplicate NCR books in A6, A5, A4 and A3, with your logo, VAT number and banking details. We continue your numbering from your last book.</p>
      <ul class="ticks">
        <li>50 or 100 numbered sets per book</li>
        <li>White, pink, yellow and blue copies</li>
        <li>Perforated originals and a writing board</li>
        <li>No layout? We design it for R300</li>
      </ul>
      <a href="<?= e(url('product/invoice-books')) ?>" class="btn btn-magenta btn-lg"><i class="fa-solid fa-cart-plus"></i> Configure invoice books</a>
    </div>
    <div class="spec-grid">
      <div class="spec"><strong>A6 – A3</strong><span>Four book sizes</span></div>
      <div class="spec"><strong>2 · 3 · 4 part</strong><span>Duplicate to quadruplicate</span></div>
      <div class="spec"><strong>5 – 250+</strong><span>Books per order</span></div>
      <div class="spec"><strong>Numbered</strong><span>Every set, in sequence</span></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section__head">
      <span class="eyebrow eyebrow--magenta"><i class="fa-solid fa-print"></i> Printing</span>
      <h2>Business stationery, marketing print &amp; branded goods</h2>
      <p>From 100 business cards to 10 000 flyers, with custom runs above that. Choose your quantity, upload artwork and we'll quote.</p>
    </div>
    <div class="tiles" style="margin-bottom:28px">
      <?php foreach (array_merge(categories('printing'), categories('signage'), categories('services')) as $c): ?>
        <a class="tile tile--<?= e($c['color']) ?>" href="<?= e(categoryUrl($c)) ?>"><i class="fa-solid <?= e($c['icon']) ?>"></i><strong><?= e($c['name']) ?></strong><span><?= e($c['blurb']) ?></span><small><?= (int) $c['n'] ?> products <i class="fa-solid fa-arrow-right"></i></small></a>
      <?php endforeach; ?>
    </div>
    <div class="pgrid">
      <?php foreach ($printing as $p): ?><?= productCard($p) ?><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="wrap">
    <div class="section__head">
      <span class="eyebrow eyebrow--yellow"><i class="fa-solid fa-sign-hanging"></i> Signage</span>
      <h2>Banners, flags, gazebos &amp; lightboxes</h2>
      <p>Event and shop-front branding that gets seen, with installation available across Johannesburg.</p>
    </div>
    <div class="pgrid">
      <?php foreach ($signage as $p): ?><?= productCard($p) ?><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section__head section__head--center">
      <span class="eyebrow"><i class="fa-solid fa-route"></i> How it works</span>
      <h2>From idea to print in four steps</h2>
    </div>
    <div class="steps4">
      <div class="step4"><h3>Choose your product</h3><p>Pick the size, paper, quantity and finish. Need help? Tap WhatsApp on any product.</p></div>
      <div class="step4"><h3>Upload your artwork</h3><p>Drop your print-ready PDF into the cart, or order a design for R300.</p></div>
      <div class="step4"><h3>Get your quote</h3><p>We check your files and send a quote by email and WhatsApp.</p></div>
      <div class="step4"><h3>Collect or delivery</h3><p>Approve, pay, and collect in Johannesburg or get it delivered in Gauteng.</p></div>
    </div>
  </div>
</section>

<section class="section section--tint">
  <div class="wrap split">
    <div class="panel panel--grad">
      <span class="eyebrow eyebrow--yellow"><i class="fa-solid fa-pen-nib"></i> Graphic design</span>
      <div class="price-big" style="margin:1rem 0 .3rem">R300</div>
      <p>per artwork: logos, flyers, invoice book layouts, business cards, banners and more. Print-ready and in CMYK.</p>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1.3rem;position:relative;z-index:1">
        <a href="<?= e(url('request.php?type=design')) ?>" class="btn btn-light"><i class="fa-solid fa-wand-magic-sparkles"></i> Request a design</a>
        <?php if ($design): ?><a href="<?= e(productUrl($design)) ?>" class="btn btn-ghost" style="background:transparent;color:#fff;border-color:#ffffff40"><i class="fa-solid fa-cart-plus"></i> Add to quote</a><?php endif; ?>
      </div>
    </div>
    <div>
      <span class="eyebrow eyebrow--cyan"><i class="fa-solid fa-palette"></i> Artwork guidelines</span>
      <h2 style="font-size:clamp(1.7rem,3.4vw,2.4rem);margin:.6rem 0">Send files that print perfectly</h2>
      <div class="spec-grid" style="margin:1rem 0 1.2rem">
        <div class="spec"><strong>CMYK</strong><span>Colour mode, not RGB</span></div>
        <div class="spec"><strong>PDF</strong><span>High quality, fonts outlined</span></div>
        <div class="spec"><strong>300 dpi</strong><span>Image resolution</span></div>
        <div class="spec"><strong>3 mm</strong><span>Bleed on every edge</span></div>
      </div>
      <p class="muted small">Up to <?= MAX_ARTWORK_FILES ?> files per product, <?= MAX_ARTWORK_MB ?> MB each. Larger files? Paste a WeTransfer or Google Drive link in the notes.</p>
      <a href="<?= e(url('artwork-guidelines.php')) ?>" class="btn btn-ghost" style="margin-top:1rem"><i class="fa-solid fa-book-open"></i> Read the full guide</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap split">
    <div>
      <span class="eyebrow"><i class="fa-solid fa-circle-question"></i> Questions</span>
      <h2 style="font-size:clamp(1.8rem,3.6vw,2.5rem);margin:.6rem 0 1rem">Good to know</h2>
      <p class="muted">Serving <?= e(implode(', ', array_slice(AREAS, 0, 8))) ?> and the rest of Gauteng.</p>
      <div class="areas" style="margin-top:1rem"><?php foreach (AREAS as $a): ?><span><?= e($a) ?></span><?php endforeach; ?></div>
    </div>
    <div class="faq">
      <?php foreach ($faq as $q => $a): ?><details><summary><?= e($q) ?></summary><p><?= e($a) ?></p></details><?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($posts): ?>
<section class="section section--tint">
  <div class="wrap">
    <div class="section__head"><span class="eyebrow eyebrow--yellow"><i class="fa-solid fa-lightbulb"></i> Printing tips</span><h2>From the print room</h2></div>
    <div class="post-cards">
      <?php foreach ($posts as $post): ?>
        <article class="post-card"><small><?= e(date('j M Y', strtotime($post['published_at']))) ?></small><h3><a href="<?= e(url('blog/' . $post['slug'])) ?>"><?= e($post['title']) ?></a></h3><p><?= e($post['excerpt']) ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php siteClose(); ?>
