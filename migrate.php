<?php
/**
 * OpenElo - Database Migration Runner
 *
 * Usage:
 *   CLI: php -f migrate.php
 *   Browser: visit /migrate.php (for legacy hosting)
 */

define('SRC_PATH', __DIR__ . '/src');
require_once SRC_PATH . '/config.php';

// Detect if running in CLI or browser
$isCli = php_sapi_name() === 'cli';

/**
 * Output helper (CLI or HTML)
 */
function output($message, $type = 'info') {
    global $isCli;

    $colors = [
        'success' => $isCli ? "\033[32m" : 'green',
        'error' => $isCli ? "\033[31m" : 'red',
        'warning' => $isCli ? "\033[33m" : 'orange',
        'info' => $isCli ? "\033[36m" : 'blue',
    ];
    $reset = $isCli ? "\033[0m" : '';

    if ($isCli) {
        echo $colors[$type] . $message . $reset . PHP_EOL;
    } else {
        $color = $colors[$type];
        echo "<div style='color: $color; margin: 5px 0;'>$message</div>";
    }
}

/**
 * Get database connection
 */
function getDb(): PDO {
    if (DB_TYPE === 'mysql') {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
    } else {
        // SQLite
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
}

/**
 * Get list of executed migrations
 */
function getExecutedMigrations(PDO $db): array {
    try {
        $result = $db->query("SELECT migration FROM migrations ORDER BY id");
        return $result->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Table doesn't exist yet
        return [];
    }
}

/**
 * Mark migration as executed
 */
function markMigrationExecuted(PDO $db, string $migration): void {
    $stmt = $db->prepare("INSERT INTO migrations (migration) VALUES (?)");
    $stmt->execute([$migration]);
}

/**
 * Run migrations
 */
function runMigrations(): void {
    global $isCli;

    if (!$isCli) {
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>OpenElo - Database Migrations</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        h1 { color: #4ec9b0; }
        .container { max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
<div class='container'>
<h1>OpenElo - Database Migrations</h1>";
    }

    try {
        output("=== Starting migrations ===", 'info');
        output("Database type: " . DB_TYPE, 'info');

        $db = getDb();
        $executedMigrations = getExecutedMigrations($db);
        $registeredMigrations = require SRC_PATH . '/migrations.php';

        output("Found " . count($registeredMigrations) . " registered migrations", 'info');
        output("Already executed: " . count($executedMigrations), 'info');

        $pending = array_diff($registeredMigrations, $executedMigrations);

        if (empty($pending)) {
            output("✓ No pending migrations. Database is up to date!", 'success');
        } else {
            output("Running " . count($pending) . " pending migrations...", 'warning');

            foreach ($pending as $migration) {
                output("Running: $migration", 'info');

                $migrationFile = SRC_PATH . '/migrations/' . $migration . '.php';
                if (!file_exists($migrationFile)) {
                    output("✗ Migration file not found: $migration", 'error');
                    continue;
                }

                $migrationCode = require $migrationFile;

                if (!isset($migrationCode['up']) || !is_callable($migrationCode['up'])) {
                    output("✗ Invalid migration (missing 'up' callable): $migration", 'error');
                    continue;
                }

                try {
                    // MySQL DDL statements cause implicit commit, so we can't use transactions
                    // SQLite supports transactional DDL, but for consistency we don't use transactions for migrations
                    $migrationCode['up']($db, DB_TYPE);
                    markMigrationExecuted($db, $migration);
                    output("✓ Success: $migration", 'success');
                } catch (Exception $e) {
                    output("✗ Failed: $migration - " . $e->getMessage(), 'error');
                    throw $e;
                }
            }

            output("✓ All migrations completed successfully!", 'success');
        }

    } catch (Exception $e) {
        output("✗ Migration error: " . $e->getMessage(), 'error');
        if (!$isCli) {
            echo "</div></body></html>";
        }
        exit(1);
    }

    if (!$isCli) {
        echo "</div></body></html>";
    }
}

// Run migrations
runMigrations();
