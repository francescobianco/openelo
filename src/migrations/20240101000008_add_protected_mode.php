<?php
/**
 * Migration: Add protected mode for clubs and view tokens for players
 */

return [
    'up' => function(PDO $db, string $dbType) {
        // Add protected_mode to clubs
        $stmt = $dbType === 'mysql'
            ? $db->query("SHOW COLUMNS FROM clubs LIKE 'protected_mode'")
            : $db->query("PRAGMA table_info(clubs)");
        $exists = false;
        if ($dbType === 'mysql') {
            $exists = $stmt->rowCount() > 0;
        } else {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                if ($col['name'] === 'protected_mode') { $exists = true; break; }
            }
        }
        if (!$exists) {
            $db->exec($dbType === 'mysql'
                ? "ALTER TABLE clubs ADD COLUMN protected_mode TINYINT DEFAULT 0 NOT NULL"
                : "ALTER TABLE clubs ADD COLUMN protected_mode INTEGER DEFAULT 0"
            );
        }

        // Add view_token to players
        $stmt = $dbType === 'mysql'
            ? $db->query("SHOW COLUMNS FROM players LIKE 'view_token'")
            : $db->query("PRAGMA table_info(players)");
        $exists = false;
        if ($dbType === 'mysql') {
            $exists = $stmt->rowCount() > 0;
        } else {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                if ($col['name'] === 'view_token') { $exists = true; break; }
            }
        }
        if (!$exists) {
            $db->exec($dbType === 'mysql'
                ? "ALTER TABLE players ADD COLUMN view_token VARCHAR(64) NULL"
                : "ALTER TABLE players ADD COLUMN view_token TEXT"
            );
        }

        // Generate view tokens for all existing players that don't have one
        $players = $db->query("SELECT id FROM players WHERE view_token IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $db->prepare("UPDATE players SET view_token = ? WHERE id = ?");
        foreach ($players as $id) {
            $stmt->execute([bin2hex(random_bytes(32)), $id]);
        }

        // Unique index
        try {
            $db->exec("CREATE UNIQUE INDEX idx_players_view_token ON players(view_token)");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), '1061') === false) {
                throw $e;
            }
        }
    },

    'down' => function(PDO $db, string $dbType) {
        if ($dbType === 'mysql') {
            $db->exec("ALTER TABLE clubs DROP COLUMN protected_mode");
            $db->exec("ALTER TABLE players DROP COLUMN view_token");
        } else {
            throw new Exception("SQLite doesn't support DROP COLUMN. Migration down not supported.");
        }
    }
];
