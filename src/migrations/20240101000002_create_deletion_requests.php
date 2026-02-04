<?php
/**
 * Migration: Create deletion requests table
 */

return [
    'up' => function(PDO $db, string $dbType) {
        $autoIncrement = $dbType === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        $intType = $dbType === 'mysql' ? 'INT' : 'INTEGER';
        $textType = $dbType === 'mysql' ? 'VARCHAR(255)' : 'TEXT';
        $engine = $dbType === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

        $db->exec("
            CREATE TABLE IF NOT EXISTS deletion_requests (
                id $intType PRIMARY KEY $autoIncrement,
                entity_type $textType NOT NULL,
                entity_id $intType NOT NULL,
                requester_email $textType NOT NULL,
                reason $textType,
                status $textType DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )$engine
        ");

        // MySQL doesn't support IF NOT EXISTS for indexes, but since we just created the table, indexes don't exist yet
        $db->exec("CREATE INDEX idx_deletion_entity ON deletion_requests(entity_type, entity_id)");
        $db->exec("CREATE INDEX idx_deletion_status ON deletion_requests(status)");
    },

    'down' => function(PDO $db, string $dbType) {
        $db->exec("DROP TABLE IF EXISTS deletion_requests");
    }
];
