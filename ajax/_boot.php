<?php
declare(strict_types=1);

/** Shared start for AJAX endpoints: POST only, CSRF checked, JSON out. */
require_once dirname(__DIR__) . '/includes/site.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['ok' => false, 'error' => 'POST only'], 405);
if (!csrfValid()) jsonResponse(['ok' => false, 'error' => 'Your session expired. Refresh the page and try again.'], 419);
