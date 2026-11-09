<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
siteOpen(['title' => 'Privacy Policy (POPIA) | Extra Printers', 'path' => 'privacy.php', 'description' => 'How Extra Printers collects, uses and protects your personal information and artwork files under POPIA.']);
$mail = e(biz('email'));
?>
<header class="phead"><div class="wrap"><h1>Privacy policy</h1><p>Last updated <?= date('j F Y', filemtime(__FILE__)) ?>. How we look after your information under the Protection of Personal Information Act (POPIA).</p></div></header>
<section class="section" style="padding-top:32px"><div class="wrap"><div class="prose">
  <h2>What we collect</h2>
  <p>When you request a quote, design or installation we collect your name, company, email, phone number, delivery address, your messages and the files you upload. Our website also uses Google Analytics and the Meta Pixel to measure visits and adverts, using cookies.</p>
  <h2>Why we use it</h2>
  <ul><li>To prepare quotes, print your order and deliver it</li><li>To contact you about your order by email, phone or WhatsApp</li><li>To improve our website and adverts (anonymous statistics)</li></ul>
  <p>We never sell your information, and we don't send marketing unless you ask for it.</p>
  <h2>Your artwork</h2>
  <p>Uploaded files are stored privately on our server and only opened by our team to quote and print your job. We delete artwork 12 months after your order unless you ask us to keep it for reprints.</p>
  <h2>Chat messages</h2>
  <p>If you message us on WhatsApp, Messenger or Instagram, an automated assistant may answer common questions. Messages are kept so our team can follow up.</p>
  <h2>Your rights</h2>
  <p>You may ask to see, correct or delete your personal information, or object to how we use it. Email <a href="mailto:<?= $mail ?>"><?= $mail ?></a>. You may also complain to the Information Regulator (inforegulator.org.za).</p>
  <h2>Data deletion (Facebook and Instagram)</h2>
  <p>To delete any information we received through Facebook or Instagram, email <a href="mailto:<?= $mail ?>"><?= $mail ?></a> with the subject "Delete my data". We remove it within 30 days and confirm by email.</p>
  <h2>Contact</h2>
  <p><?= e(biz('name')) ?><?= bizAddress() ? ', ' . e(bizAddress()) : '' ?> · <a href="mailto:<?= $mail ?>"><?= $mail ?></a><?= biz('phone') ? ' · ' . e(biz('phone')) : '' ?></p>
</div></div></section>
<?php siteClose(); ?>
