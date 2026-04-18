<?php
return [
    'up' => function(PDO $db, string $dbType) {
        if ($dbType === 'mysql') {
            $cols = $db->query("SHOW COLUMNS FROM circuits")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $cols = array_column($db->query("PRAGMA table_info(circuits)")->fetchAll(), 'name');
        }

        if (!in_array('description', $cols)) {
            $db->exec("ALTER TABLE circuits ADD COLUMN description TEXT NULL");
        }

        $intType = $dbType === 'mysql' ? 'INT' : 'INTEGER';
        $engine  = $dbType === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
        $db->exec("
            CREATE TABLE IF NOT EXISTS circuit_update_requests (
                id $intType PRIMARY KEY " . ($dbType === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ",
                circuit_id $intType NOT NULL,
                field VARCHAR(50) NOT NULL,
                value TEXT NOT NULL,
                applied INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (circuit_id) REFERENCES circuits(id)
            )$engine
        ");
    },
    'down' => function(PDO $db, string $dbType) {
        $db->exec("DROP TABLE IF EXISTS circuit_update_requests");
    }
];