<?php
return [
    'up' => function(PDO $db, string $dbType) {
        if ($dbType === 'mysql') {
            $cols = $db->query("SHOW COLUMNS FROM ratings")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $cols = array_column($db->query("PRAGMA table_info(ratings)")->fetchAll(), 'name');
        }

        if (!in_array('ladder_position', $cols)) {
            $db->exec("ALTER TABLE ratings ADD COLUMN ladder_position INTEGER DEFAULT NULL");
        }
    },
    'down' => function(PDO $db, string $dbType) {
        // SQLite does not support DROP COLUMN in older versions; skip for SQLite
        if ($dbType === 'mysql') {
            $db->exec("ALTER TABLE ratings DROP COLUMN ladder_position");
        }
    }
];