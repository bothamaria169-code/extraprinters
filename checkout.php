<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/quotes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('cart.php');
requireCsrf();

[$c, $err] = quoteCustomerFromPost();
if (!$err && !cartLines()) $err = 'Your quote cart is empty.';
if (!$err && rateLimited('quote', 6, 60)) $err = 'You have sent several requests already. Please WhatsApp us instead.';
if ($err) {
    $_SESSION['checkout_old'] = $c;
    flash('error', $err);
    redirect('cart.php');
}

$q = createQuote('order', $c, cartToLines());
unset($_SESSION['cart']);
redirect(quoteUrl($q) . '&new=1');
