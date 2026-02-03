<?php
/**
 * OpenElo - Configuration
 */

// Database (SQLite per semplicità)
define('DB_PATH', getenv('DB_PATH') ?: dirname(__DIR__) . '/data/openelo.db');

// Email settings
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.example.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@openelo.org');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'OpenELO');

// App settings
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
