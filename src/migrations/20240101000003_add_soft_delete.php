<?php
/**
 * Migration: Add soft delete support
 */

return [
    'up' => function(PDO $db, string $dbType) {
        // Add deleted_at column to main tables
        $tables = ['circuits', 'clubs', 'players', 'matches'];

        foreach ($tables as $table) {
            if ($dbType === 'mysql') {
                $db->exec("ALTER TABLE $table ADD COLUMN deleted_at DATETIME NULL");
            } else {
                // SQLite doesn't support ADD COLUMN with constraints in one statement
                $db->exec("ALTER TABLE $table ADD COLUMN deleted_at DATETIME");
            }
        }

        // Add index for faster queries
        foreach ($tables as $table) {
            try {
                $db->exec("CREATE INDEX idx_{$table}_deleted ON $table(deleted_at)");
            } catch (PDOException $e) {
                // Index might already exist, ignore error 1061 (Duplicate key name)
                if ($e->getCode() != '42000' || strpos($e->getMessage(), '1061') === false) {
                    throw $e;
                }
            }
        }
    },

    'down' => function(PDO $db, string $dbType) {
        $tables = ['circuits', 'clubs', 'players', 'matches'];

        foreach ($tables as $table) {
            if ($dbType === 'mysql') {
                $db->exec("ALTER TABLE $table DROP COLUMN deleted_at");
            } else {
                // SQLite doesn't support DROP COLUMN easily, would need table recreation
                // For simplicity, we'll just mark this as not reversible
                throw new Exception("SQLite doesn't support DROP COLUMN. Migration down not supported.");
            }
        }
    }
];
