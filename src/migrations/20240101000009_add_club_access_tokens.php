<?php
return [
    'up' => function(PDO $db, string $dbType) {
        $engine = $dbType === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
        $intType = $dbType === 'mysql' ? 'INT' : 'INTEGER';
        $db->exec("
            CREATE TABLE IF NOT EXISTS club_access_tokens (
                id $intType PRIMARY KEY " . ($dbType === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ",
                token VARCHAR(64) NOT NULL,
                club_id $intType NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (club_id) REFERENCES clubs(id)
            )$engine
        ");
        // Fix column type if table was previously created with TEXT (failed first run)
        if ($dbType === 'mysql') {
            try {
                $db->exec("ALTER TABLE club_access_tokens MODIFY token VARCHAR(64) NOT NULL");
            } catch (PDOException $e) {
                // ignore if already correct type
            }
        }
        try {
            $db->exec("CREATE UNIQUE INDEX idx_club_access_tokens_token ON club_access_tokens(token)");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), '1061') === false) throw $e;
        }
    },
    'down' => function(PDO $db, string $dbType) {
        $db->exec("DROP TABLE IF EXISTS club_access_tokens");
    }
];
