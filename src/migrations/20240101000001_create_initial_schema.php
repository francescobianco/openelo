<?php
/**
 * Migration: Create initial OpenElo schema
 */

return [
    'up' => function(PDO $db, string $dbType) {
        $autoIncrement = $dbType === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        $intType = $dbType === 'mysql' ? 'INT' : 'INTEGER';
        $textType = $dbType === 'mysql' ? 'VARCHAR(255)' : 'TEXT';
        $datetimeDefault = $dbType === 'mysql' ? 'CURRENT_TIMESTAMP' : "datetime('now')";
        $engine = $dbType === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

        // Circuits
        $db->exec("
            CREATE TABLE IF NOT EXISTS circuits (
                id $intType PRIMARY KEY $autoIncrement,
                name $textType NOT NULL,
                owner_email $textType NOT NULL,
                confirmed $intType DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )$engine
        ");

        // Clubs
        $db->exec("
            CREATE TABLE IF NOT EXISTS clubs (
                id $intType PRIMARY KEY $autoIncrement,
                name $textType NOT NULL,
                president_email $textType NOT NULL,
                president_confirmed $intType DEFAULT 0,
                confirmed $intType DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )$engine
        ");

        // Circuit-Club membership
        $db->exec("
            CREATE TABLE IF NOT EXISTS circuit_clubs (
                id $intType PRIMARY KEY $autoIncrement,
                circuit_id $intType NOT NULL,
                club_id $intType NOT NULL,
                club_confirmed $intType DEFAULT 0,
                circuit_confirmed $intType DEFAULT 0,
                is_primary $intType DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (circuit_id) REFERENCES circuits(id),
                FOREIGN KEY (club_id) REFERENCES clubs(id)
            )$engine
        ");

        if ($dbType === 'mysql') {
            $db->exec("ALTER TABLE circuit_clubs ADD UNIQUE KEY unique_circuit_club (circuit_id, club_id)");
        } else {
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS unique_circuit_club ON circuit_clubs(circuit_id, club_id)");
        }

        // Players
        $db->exec("
            CREATE TABLE IF NOT EXISTS players (
                id $intType PRIMARY KEY $autoIncrement,
                first_name $textType NOT NULL,
                last_name $textType NOT NULL,
                email $textType NOT NULL UNIQUE,
                club_id $intType NOT NULL,
                player_confirmed $intType DEFAULT 0,
                president_confirmed $intType DEFAULT 0,
                confirmed $intType DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (club_id) REFERENCES clubs(id)
            )$engine
        ");

        // Club transfers
        $db->exec("
            CREATE TABLE IF NOT EXISTS club_transfers (
                id $intType PRIMARY KEY $autoIncrement,
                player_id $intType NOT NULL,
                from_club_id $intType,
                to_club_id $intType NOT NULL,
                player_confirmed $intType DEFAULT 0,
                president_confirmed $intType DEFAULT 0,
                completed $intType DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (player_id) REFERENCES players(id),
                FOREIGN KEY (from_club_id) REFERENCES clubs(id),
                FOREIGN KEY (to_club_id) REFERENCES clubs(id)
            )$engine
        ");

        // Ratings
        $db->exec("
            CREATE TABLE IF NOT EXISTS ratings (
                id $intType PRIMARY KEY $autoIncrement,
                player_id $intType NOT NULL,
                circuit_id $intType NOT NULL,
                rating $intType DEFAULT " . ELO_START . ",
                games_played $intType DEFAULT 0,
                FOREIGN KEY (player_id) REFERENCES players(id),
                FOREIGN KEY (circuit_id) REFERENCES circuits(id)
            )$engine
        ");

        if ($dbType === 'mysql') {
            $db->exec("ALTER TABLE ratings ADD UNIQUE KEY unique_player_circuit (player_id, circuit_id)");
        } else {
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS unique_player_circuit ON ratings(player_id, circuit_id)");
        }

        // Matches
        $db->exec("
            CREATE TABLE IF NOT EXISTS matches (
                id $intType PRIMARY KEY $autoIncrement,
                circuit_id $intType NOT NULL,
                white_player_id $intType NOT NULL,
                black_player_id $intType NOT NULL,
                result $textType NOT NULL,
                white_confirmed $intType DEFAULT 0,
                black_confirmed $intType DEFAULT 0,
                president_confirmed $intType DEFAULT 0,
                rating_applied $intType DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (circuit_id) REFERENCES circuits(id),
                FOREIGN KEY (white_player_id) REFERENCES players(id),
                FOREIGN KEY (black_player_id) REFERENCES players(id)
            )$engine
        ");

        // Confirmations
        $db->exec("
            CREATE TABLE IF NOT EXISTS confirmations (
                id $intType PRIMARY KEY $autoIncrement,
                token $textType NOT NULL UNIQUE,
                type $textType NOT NULL,
                target_id $intType NOT NULL,
                email $textType NOT NULL,
                role $textType,
                confirmed $intType DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL
            )$engine
        ");

        // Indexes
        $db->exec("CREATE INDEX IF NOT EXISTS idx_confirmations_token ON confirmations(token)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_ratings_circuit ON ratings(circuit_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_matches_circuit ON matches(circuit_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_players_club ON players(club_id)");

        // Schema version (legacy, will be removed in future migration)
        $db->exec("
            CREATE TABLE IF NOT EXISTS schema_version (
                version $intType NOT NULL
            )$engine
        ");

        $db->exec("DELETE FROM schema_version");
        $db->exec("INSERT INTO schema_version (version) VALUES (2)");
    },

    'down' => function(PDO $db, string $dbType) {
        $tables = [
            'schema_version',
            'confirmations',
            'matches',
            'ratings',
            'club_transfers',
            'players',
            'circuit_clubs',
            'clubs',
            'circuits'
        ];

        foreach ($tables as $table) {
            $db->exec("DROP TABLE IF EXISTS $table");
        }
    }
];
