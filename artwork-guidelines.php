<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';

$faq = [
    'Why CMYK and not RGB?' => 'Printers mix four inks: cyan, magenta, yellow and black (CMYK). Screens use light (RGB), which can show brighter colours than ink can print. Converting to CMYK yourself means no surprises on paper.',
    'What is bleed?' => 'Bleed is 3 mm of extra background on every edge, which is trimmed off after printing. Without it, a thin white line can appear at the edge.',
    'What is the safe zone?' => 'Keep text and logos at least 5 mm inside the trim line so nothing important gets cut off.',
    'Can I send a Word or PowerPoint file?' => 'Yes, but fonts and colours can shift. Export it as a PDF first if you can, or let us set it up properly for R300.',
    'My file is bigger than 50 MB.' => 'Upload it to WeTransfer or Google Drive and paste the link in the "Large files link" box when you send your quote.',
];
siteOpen([
    'title' => 'Artwork Guidelines: CMYK, PDF, Bleed & Sizes | Extra Printers',
    'description' => 'How to prepare print-ready artwork: CMYK colour, high-quality PDF, 300 dpi, 3 mm bleed and a 5 mm safe zone. Sizes for A-series paper, business cards and banners.',
    'path' => 'artwork-guidelines.php', 'schema' => [schemaFaq($faq)],
]);
$sizes = [
    ['A1', '594 × 841 mm', '600 × 847 mm'], ['A2', '420 × 594 mm', '426 × 600 mm'], ['A3', '297 × 420 mm', '303 × 426 mm'],
    ['A4', '210 × 297 mm', '216 × 303 mm'], ['A5', '148 × 210 mm', '154 × 216 mm'], ['A6', '105 × 148 mm', '111 × 154 mm'],
    ['DL', '99 × 210 mm', '105 × 216 mm'], ['Business card', '90 × 50 mm', '96 × 56 mm'], ['Pull up banner', '850 × 2000 mm', '850 × 2100 mm (100 mm extra at the bottom)'],
];
?>
<header class="phead"><div class="wrap">
  <span class="eyebrow eyebrow--cyan"><i class="fa-solid fa-palette"></i> Before you upload</span>
  <h1>Artwork guidelines</h1>
  <p>Follow these five rules and your print will look exactly like your screen. Not sure? Upload anyway; we check every file before printing, and we can fix or design it for R300.</p>
</div></header>
<section class="section" style="padding-top:32px">
  <div class="wrap">
    <div class="steps4" style="margin-bottom:40px">
      <div class="step4"><h3>CMYK colour</h3><p>Set your document to CMYK, not RGB. Black text: 100% K only.</p></div>
      <div class="step4"><h3>High-quality PDF</h3><p>Export as "Press Quality" or PDF/X-1a, with fonts embedded or outlined.</p></div>
      <div class="step4"><h3>300 dpi images</h3><p>Photos at 300 dpi at the printed size. Banners: 150 dpi is fine.</p></div>
      <div class="step4"><h3>3 mm bleed + 5 mm safe zone</h3><p>Extend backgrounds 3 mm past the edge; keep text 5 mm inside.</p></div>
    </div>
    <div class="split" style="align-items:start">
      <div>
        <h2 style="font-size:1.6rem;margin-bottom:1rem">Sizes with bleed</h2>
        <div class="form-card" style="padding:0;overflow:auto">
          <table class="tbl" style="width:100%;border-collapse:collapse;font-size:.92rem">
            <thead><tr style="text-align:left;background:var(--surface-2)"><th style="padding:.7rem 1rem">Product</th><th style="padding:.7rem 1rem">Finished size</th><th style="padding:.7rem 1rem">Artwork size (with bleed)</th></tr></thead>
            <tbody><?php foreach ($sizes as [$n, $f, $b]): ?><tr style="border-top:1px solid var(--line)"><td style="padding:.65rem 1rem"><strong><?= e($n) ?></strong></td><td style="padding:.65rem 1rem"><?= e($f) ?></td><td style="padding:.65rem 1rem"><?= e($b) ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
      <div>
        <h2 style="font-size:1.6rem;margin-bottom:1rem">Uploading</h2>
        <ul class="ticks">
          <li><strong>Best:</strong> PDF. Also accepted: AI, EPS, PSD, TIFF, JPG, PNG, SVG, CDR, ZIP</li>
          <li>Up to <?= MAX_ARTWORK_FILES ?> files per product, <?= MAX_ARTWORK_MB ?> MB each</li>
          <li>Bigger files: paste a WeTransfer / Google Drive link</li>
          <li>Double-sided? Two pages in one PDF (front, then back)</li>
          <li>Invoice books: tell us the starting number</li>
          <li>Your files are private: only our team can open them</li>
        </ul>
        <div class="faq" style="margin-top:1.4rem"><?php foreach ($faq as $q => $a): ?><details><summary><?= e($q) ?></summary><p><?= e($a) ?></p></details><?php endforeach; ?></div>
      </div>
    </div>
  </div>
</section>
<?php siteClose(); ?>
