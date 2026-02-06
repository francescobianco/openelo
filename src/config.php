<?php
/**
 * OpenElo - Configuration
 */

// Load environment variables from env.php if it exists (for legacy hosting)
$envFile = dirname(__DIR__) . '/env.php';
if (file_exists($envFile)) {
    require_once $envFile;
}

// Database configuration
define('DB_TYPE', getenv('DB_TYPE') ?: 'sqlite'); // sqlite or mysql
define('DB_PATH', getenv('DB_PATH') ?: dirname(__DIR__) . '/data/openelo.db'); // SQLite only
define('DB_HOST', getenv('DB_HOST') ?: 'localhost'); // MySQL only
define('DB_PORT', getenv('DB_PORT') ?: '3306'); // MySQL only
define('DB_NAME', getenv('DB_NAME') ?: 'openelo'); // MySQL only
define('DB_USER', getenv('DB_USER') ?: 'root'); // MySQL only
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: ''); // MySQL only

// Email settings
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.example.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@openelo.org');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'OpenELO');
define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@openelo.org');

// App settings
define('APP_ENV', getenv('APP_ENV') ?: '');
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost:8080');
define('TOKEN_EXPIRY_HOURS', getenv('TOKEN_EXPIRY_HOURS') ?: 72);

// Elo settings
define('ELO_START', getenv('ELO_START') ?: 1200);
define('ELO_K_NEW', 40);      // < 30 partite
define('ELO_K_NORMAL', 20);   // rating < 2200
define('ELO_K_HIGH', 10);     // rating >= 2200
define('ELO_GAMES_THRESHOLD', 30);
define('ELO_HIGH_RATING', 2200);

// Lingue supportate
define('SUPPORTED_LANGS', ['it', 'en']);
define('DEFAULT_LANG', getenv('DEFAULT_LANG') ?: 'en');
