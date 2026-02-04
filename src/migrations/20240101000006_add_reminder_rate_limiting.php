<?php
/**
 * Migration: Add Reminder Rate Limiting
 *
 * Creates a table to track reminder emails sent, enabling rate limiting
 * to prevent abuse of the "send reminder" functionality.
 */

return [
    'up' => function($db, $dbType) {
        if ($dbType === 'mysql') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS reminder_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_reminder_logs_ip (ip_address, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } else {
            // SQLite
            $db->exec("
                CREATE TABLE IF NOT EXISTS reminder_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    ip_address TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_reminder_logs_ip ON reminder_logs(ip_address, created_at)");
        }
    },

    'down' => function($db, $dbType) {
        $db->exec("DROP TABLE IF EXISTS reminder_logs");
    }
];
