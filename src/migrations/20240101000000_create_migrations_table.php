<?php
/**
 * Migration: Create migrations tracking table
 */

return [
    'up' => function(PDO $db, string $dbType) {
        if ($dbType === 'mysql') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            // SQLite
            $db->exec("
                CREATE TABLE IF NOT EXISTS migrations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration TEXT NOT NULL UNIQUE,
                    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }
    },

    'down' => function(PDO $db, string $dbType) {
        $db->exec("DROP TABLE IF EXISTS migrations");
    }
];
