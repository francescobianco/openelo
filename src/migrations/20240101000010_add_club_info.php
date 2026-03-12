<?php
return [
    'up' => function(PDO $db, string $dbType) {
        // Add location and website columns to clubs (idempotent)
        if ($dbType === 'mysql') {
            $cols = $db->query("SHOW COLUMNS FROM clubs")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $cols = array_column($db->query("PRAGMA table_info(clubs)")->fetchAll(), 'name');
        }

        if (!in_array('location', $cols)) {
            $db->exec("ALTER TABLE clubs ADD COLUMN location TEXT");
        }
        if (!in_array('website', $cols)) {
            $db->exec("ALTER TABLE clubs ADD COLUMN website TEXT");
        }

        // Create club_update_requests table
        $intType = $dbType === 'mysql' ? 'INT' : 'INTEGER';
        $engine  = $dbType === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
        $db->exec("
            CREATE TABLE IF NOT EXISTS club_update_requests (
                id $intType PRIMARY KEY " . ($dbType === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ",
                club_id $intType NOT NULL,
                name TEXT NOT NULL,
                location TEXT,
                website TEXT,
                applied INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (club_id) REFERENCES clubs(id)
            )$engine
        ");
    },
    'down' => function(PDO $db, string $dbType) {
        $db->exec("DROP TABLE IF EXISTS club_update_requests");
    }
];
