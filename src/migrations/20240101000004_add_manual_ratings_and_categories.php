<?php
/**
 * Migration: Add manual rating requests and player categories
 */

return [
    'up' => function(PDO $db, string $dbType) {
        $autoIncrement = $dbType === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        $intType = $dbType === 'mysql' ? 'INT' : 'INTEGER';
        $textType = $dbType === 'mysql' ? 'VARCHAR(255)' : 'TEXT';
        $engine = $dbType === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

        // Add category column to players table
        if ($dbType === 'mysql') {
            $db->exec("ALTER TABLE players ADD COLUMN category VARCHAR(10) DEFAULT 'NC'");
        } else {
            $db->exec("ALTER TABLE players ADD COLUMN category TEXT DEFAULT 'NC'");
        }

        // Create manual rating requests table
        $db->exec("
            CREATE TABLE IF NOT EXISTS manual_rating_requests (
                id $intType PRIMARY KEY $autoIncrement,
                player_id $intType NOT NULL,
                circuit_id $intType NOT NULL,
                requested_rating $intType NOT NULL,
                requested_category $textType NOT NULL,
                player_confirmed $intType DEFAULT 0,
                president_confirmed $intType DEFAULT 0,
                circuit_confirmed $intType DEFAULT 0,
                applied $intType DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (player_id) REFERENCES players(id),
                FOREIGN KEY (circuit_id) REFERENCES circuits(id)
            )$engine
        ");

        $db->exec("CREATE INDEX idx_manual_rating_player ON manual_rating_requests(player_id)");
        $db->exec("CREATE INDEX idx_manual_rating_circuit ON manual_rating_requests(circuit_id)");
    },

    'down' => function(PDO $db, string $dbType) {
        $db->exec("DROP TABLE IF EXISTS manual_rating_requests");

        if ($dbType === 'mysql') {
            $db->exec("ALTER TABLE players DROP COLUMN category");
        }
    }
];
