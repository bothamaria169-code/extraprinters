<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
siteOpen(['title' => 'Terms & Conditions | Extra Printers', 'path' => 'terms.php', 'description' => 'Quotes, artwork, proofs, payment, delivery and reprints at Extra Printers, Johannesburg.']);
?>
<header class="phead"><div class="wrap"><h1>Terms &amp; conditions</h1><p>The short version: we quote, you approve, we print. The details are below.</p></div></header>
<section class="section" style="padding-top:32px"><div class="wrap"><div class="prose">
  <h2>Quotes</h2><p>Quotes are valid for 14 days and based on the options and files you sent. If the artwork or quantities change, the price may change.</p>
  <h2>Artwork and proofs</h2><p>You are responsible for the spelling, content and rights to use your artwork. We check files for print problems (resolution, bleed, colour mode) and tell you if something needs fixing. For design jobs (R300 per artwork) we send a proof; printing starts only once you approve it, and we can't accept responsibility for errors in an approved proof.</p>
  <h2>Colour</h2><p>Screens and printers show colour differently. CMYK files give the closest match, but slight colour variation between screen and paper, or between print runs, is normal.</p>
  <h2>Payment</h2><p>Unless agreed otherwise, orders are paid in full before printing. Paper sales are paid before collection or delivery.</p>
  <h2>Collection and delivery</h2><p>Collect from us in <?= e(biz('city')) ?>, or we deliver across Gauteng at the delivery fee in your quote. Check your order when you receive it, and tell us about any problem within 3 business days.</p>
  <h2>Reprints and refunds</h2><p>If a fault is ours (a misprint, a wrong paper, damage in our delivery), we reprint at no cost. Custom printed items can't be returned for a change of mind. Unopened paper can be returned within 7 days in its original packaging.</p>
  <h2>Signage installation</h2><p>Installation quotes follow a site visit. You confirm we may install on the property, and any landlord or municipal approvals are your responsibility.</p>
</div></div></section>
<?php siteClose(); ?>
