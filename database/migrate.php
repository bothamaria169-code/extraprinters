<?php
declare(strict_types=1);

/**
 * Self-running migrations: every page load checks the schema version and
 * creates or alters what is missing. Adding a step = bump SCHEMA_VERSION and
 * add an `if ($v < N)` block. The product catalogue is seeded once.
 */
const SCHEMA_VERSION = 1;

function runMigrations(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS app_settings (k VARCHAR(80) PRIMARY KEY, v MEDIUMTEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $v = (int) ($db->query("SELECT v FROM app_settings WHERE k = 'schema_version'")->fetchColumn() ?: 0);
    if ($v >= SCHEMA_VERSION) return;

    $t = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    if ($v < 1) {
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            full_name VARCHAR(120) NOT NULL DEFAULT '',
            password_hash VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) $t");
        $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL, ip VARCHAR(45) NOT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (email), INDEX (ip, created_at)) $t");
        $db->exec("CREATE TABLE IF NOT EXISTS audit_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id INT UNSIGNED NULL, action VARCHAR(60) NOT NULL,
            target VARCHAR(200) NOT NULL DEFAULT '', detail TEXT NULL, ip VARCHAR(45) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (created_at)) $t");

        $db->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            section ENUM('paper','printing','signage','services') NOT NULL,
            slug VARCHAR(80) NOT NULL UNIQUE, name VARCHAR(120) NOT NULL,
            blurb VARCHAR(255) NOT NULL DEFAULT '', icon VARCHAR(60) NOT NULL DEFAULT 'fa-print',
            color ENUM('cyan','magenta','yellow','blue') NOT NULL DEFAULT 'blue',
            sort INT NOT NULL DEFAULT 0) $t");
        $db->exec("CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id INT UNSIGNED NOT NULL,
            slug VARCHAR(120) NOT NULL UNIQUE, name VARCHAR(160) NOT NULL,
            tagline VARCHAR(255) NOT NULL DEFAULT '', description TEXT NULL,
            options JSON NULL, images JSON NULL,
            price_from DECIMAL(10,2) NULL, price_note VARCHAR(80) NOT NULL DEFAULT '',
            fixed_price TINYINT(1) NOT NULL DEFAULT 0,
            needs_artwork TINYINT(1) NOT NULL DEFAULT 1,
            featured TINYINT(1) NOT NULL DEFAULT 0, sort INT NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1,
            seo_title VARCHAR(160) NOT NULL DEFAULT '', seo_description VARCHAR(255) NOT NULL DEFAULT '',
            last_posted_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
            INDEX (is_active, sort), FULLTEXT (name, tagline, description)) $t");

        $db->exec("CREATE TABLE IF NOT EXISTS quotes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ref VARCHAR(20) NOT NULL UNIQUE, token CHAR(32) NOT NULL,
            type ENUM('order','design','installation','contact') NOT NULL DEFAULT 'order',
            status ENUM('new','quoted','approved','in_production','ready','completed','cancelled') NOT NULL DEFAULT 'new',
            name VARCHAR(120) NOT NULL, company VARCHAR(160) NOT NULL DEFAULT '',
            email VARCHAR(190) NOT NULL, phone VARCHAR(40) NOT NULL DEFAULT '',
            delivery ENUM('collect','deliver') NOT NULL DEFAULT 'collect', address VARCHAR(255) NOT NULL DEFAULT '',
            needed_by DATE NULL, notes TEXT NULL, file_link VARCHAR(500) NOT NULL DEFAULT '',
            quoted_total DECIMAL(10,2) NULL, quote_message TEXT NULL, admin_notes TEXT NULL,
            source VARCHAR(255) NOT NULL DEFAULT '', ip VARCHAR(45) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (status, created_at), INDEX (email)) $t");
        $db->exec("CREATE TABLE IF NOT EXISTS quote_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, quote_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NULL, product_name VARCHAR(160) NOT NULL,
            options JSON NULL, quantity VARCHAR(60) NOT NULL DEFAULT '', unit_price DECIMAL(10,2) NULL, notes TEXT NULL,
            FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE) $t");
        $db->exec("CREATE TABLE IF NOT EXISTS quote_files (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, quote_id INT UNSIGNED NOT NULL, item_id INT UNSIGNED NULL,
            orig_name VARCHAR(255) NOT NULL, stored VARCHAR(255) NOT NULL, size INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE) $t");

        $db->exec("CREATE TABLE IF NOT EXISTS posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(160) NOT NULL UNIQUE, title VARCHAR(200) NOT NULL,
            excerpt VARCHAR(300) NOT NULL DEFAULT '', body MEDIUMTEXT NOT NULL, keyword VARCHAR(120) NOT NULL DEFAULT '',
            product_id INT UNSIGNED NULL, status ENUM('draft','published') NOT NULL DEFAULT 'published',
            published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (status, published_at)) $t");
        $db->exec("CREATE TABLE IF NOT EXISTS ai_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(40) NOT NULL, ok TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) $t");
        $db->exec("CREATE TABLE IF NOT EXISTS social_posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, platform VARCHAR(20) NOT NULL, product_id INT UNSIGNED NULL,
            caption TEXT NOT NULL, ok TINYINT(1) NOT NULL DEFAULT 0, ext_id VARCHAR(100) NOT NULL DEFAULT '', error VARCHAR(500) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (created_at)) $t");
        $db->exec("CREATE TABLE IF NOT EXISTS chat_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, channel VARCHAR(20) NOT NULL, sender VARCHAR(80) NOT NULL,
            direction ENUM('in','out') NOT NULL, body TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (channel, sender, created_at)) $t");

        require_once dirname(__DIR__) . '/includes/catalog_seed.php';
        seedCatalogue($db);
    }
    $db->prepare("REPLACE INTO app_settings (k, v) VALUES ('schema_version', ?)")->execute([(string) SCHEMA_VERSION]);
}

function seedAdmin(PDO $db): void
{
    $pw = (string) secret('admin_seed_password', '');
    if ($pw === '' || $pw === 'CHANGE_ME') return;
    if ($db->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0) return;
    $db->prepare("INSERT INTO users (email, full_name, password_hash) VALUES (?, 'Administrator', ?)")
       ->execute([ADMIN_EMAIL, password_hash($pw, PASSWORD_DEFAULT)]);
}
