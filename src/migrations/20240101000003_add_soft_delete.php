<?php
/**
 * Migration: Add soft delete support
 */

return [
    'up' => function(PDO $db, string $dbType) {
        $tables = ['circuits', 'clubs', 'players', 'matches'];

        // Helper: check if a column exists
        $columnExists = function(string $table, string $column) use ($db, $dbType): bool {
            if ($dbType === 'mysql') {
                $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
                return $stmt->rowCount() > 0;
            } else {
                $stmt = $db->query("PRAGMA table_info($table)");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                    if ($col['name'] === $column) return true;
                }
                return false;
            }
        };

        // Add deleted_at column, skip if already present
        foreach ($tables as $table) {
            if (!$columnExists($table, 'deleted_at')) {
                if ($dbType === 'mysql') {
                    $db->exec("ALTER TABLE `$table` ADD COLUMN deleted_at DATETIME NULL");
                } else {
                    $db->exec("ALTER TABLE $table ADD COLUMN deleted_at DATETIME");
                }
            }

            // Assert: column must exist after the operation
            if (!$columnExists($table, 'deleted_at')) {
                throw new RuntimeException("Migration assertion failed: column 'deleted_at' not found on table '$table' after ALTER TABLE.");
            }
        }

        // Add index for faster queries, skip if already present
        foreach ($tables as $table) {
            try {
                $db->exec("CREATE INDEX idx_{$table}_deleted ON $table(deleted_at)");
            } catch (PDOException $e) {
                // Ignore "index already exists" for both MySQL (1061) and SQLite
                $msg = $e->getMessage();
                if (strpos($msg, '1061') === false && strpos($msg, 'already exists') === false) {
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
