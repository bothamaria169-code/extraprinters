<?php
// Extra Printers secrets: copy to config/secrets.php and fill in.
// config/secrets.php is in .gitignore: NEVER commit it. Upload it once to the
// server with cPanel File Manager; git deploys leave it alone.
return [
    // Live database (cPanel → MySQL Databases)
    'db_host' => 'localhost',
    'db_name' => 'CPANELUSER_extraprinters',
    'db_user' => 'CPANELUSER_extraprinters',
    'db_pass' => 'CHANGE_ME',

    // Local XAMPP database (used automatically on localhost)
    'local_db_name' => 'extraprinters',
    'local_db_user' => 'root',
    'local_db_pass' => '',

    // Public address of the live site, no trailing slash
    'app_url' => 'https://extraprinters.co.za',

    // Long random string used to sign private links (quote pages, files).
    // Generate one: php -r "echo bin2hex(random_bytes(32));"
    'app_secret' => 'CHANGE_ME',

    // Admin account: created on the first page load if it doesn't exist yet.
    // New quote requests are emailed to this address too.
    'admin_email'         => 'admin@extraprinters.co.za',
    'admin_seed_password' => 'CHANGE_ME',

    // Locked out of admin? Set a new password here and load any page once.
    'admin_reset_password' => '',
    'admin_reset_emails'   => [],

    // Mailbox that sends quote emails (cPanel → Email Accounts).
    // Leave smtp_pass blank to use PHP mail(). Admin → Settings can override.
    'smtp_host' => 'mail.extraprinters.co.za',
    'smtp_port' => 465,
    'smtp_user' => 'quotes@extraprinters.co.za',
    'smtp_pass' => '',

    // Optional: Groq key (can also be pasted in Admin → Settings → AI writing)
    'groq_api_key' => '',
];
