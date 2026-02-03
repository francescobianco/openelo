<?php
/**
 * OpenElo - Database Layer
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $pdo = null;

    public static function get(): PDO {
        if (self::$pdo === null) {
            $dir = dirname(DB_PATH);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            self::$pdo = new PDO('sqlite:' . DB_PATH);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            self::init();
        }
        return self::$pdo;
    }

    private static function init(): void {
        $sql = "
        -- Circuits
        CREATE TABLE IF NOT EXISTS circuits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            owner_email TEXT NOT NULL,
            confirmed INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Clubs
        CREATE TABLE IF NOT EXISTS clubs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            president_email TEXT NOT NULL,
            confirmed INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        -- Circuit-Club membership
        CREATE TABLE IF NOT EXISTS circuit_clubs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            circuit_id INTEGER NOT NULL,
            club_id INTEGER NOT NULL,
            confirmed INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (circuit_id) REFERENCES circuits(id),
            FOREIGN KEY (club_id) REFERENCES clubs(id),
            UNIQUE(circuit_id, club_id)
        );

        -- Players
        CREATE TABLE IF NOT EXISTS players (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            club_id INTEGER,
            confirmed INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (club_id) REFERENCES clubs(id)
        );

        -- Ratings (per player per circuit)
        CREATE TABLE IF NOT EXISTS ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_id INTEGER NOT NULL,
            circuit_id INTEGER NOT NULL,
            rating INTEGER DEFAULT " . ELO_START . ",
            games_played INTEGER DEFAULT 0,
            FOREIGN KEY (player_id) REFERENCES players(id),
            FOREIGN KEY (circuit_id) REFERENCES circuits(id),
            UNIQUE(player_id, circuit_id)
        );

        -- Matches
        CREATE TABLE IF NOT EXISTS matches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            circuit_id INTEGER NOT NULL,
            club_id INTEGER NOT NULL,
            white_player_id INTEGER NOT NULL,
            black_player_id INTEGER NOT NULL,
            result TEXT NOT NULL,
            white_confirmed INTEGER DEFAULT 0,
            black_confirmed INTEGER DEFAULT 0,
            president_confirmed INTEGER DEFAULT 0,
            rating_applied INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (circuit_id) REFERENCES circuits(id),
            FOREIGN KEY (club_id) REFERENCES clubs(id),
            FOREIGN KEY (white_player_id) REFERENCES players(id),
            FOREIGN KEY (black_player_id) REFERENCES players(id)
        );

        -- Confirmations (unified token system)
        CREATE TABLE IF NOT EXISTS confirmations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            token TEXT NOT NULL UNIQUE,
            type TEXT NOT NULL,
            target_id INTEGER NOT NULL,
            email TEXT NOT NULL,
            role TEXT,
            confirmed INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NOT NULL
        );

        -- Indexes
        CREATE INDEX IF NOT EXISTS idx_confirmations_token ON confirmations(token);
        CREATE INDEX IF NOT EXISTS idx_ratings_circuit ON ratings(circuit_id);
        CREATE INDEX IF NOT EXISTS idx_matches_circuit ON matches(circuit_id);
        ";

        self::$pdo->exec($sql);
    }
}

/**
 * Generate secure token
 */
function generateToken(): string {
    return bin2hex(random_bytes(32));
}

/**
 * Create confirmation token
 */
function createConfirmation(string $type, int $targetId, string $email, ?string $role = null): string {
    $db = Database::get();
    $token = generateToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . TOKEN_EXPIRY_HOURS . ' hours'));

    $stmt = $db->prepare("
        INSERT INTO confirmations (token, type, target_id, email, role, expires_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$token, $type, $targetId, $email, $role, $expiresAt]);

    return $token;
}

/**
 * Verify and consume confirmation token
 */
function verifyConfirmation(string $token): ?array {
    $db = Database::get();

    $stmt = $db->prepare("
        SELECT * FROM confirmations
        WHERE token = ? AND confirmed = 0 AND expires_at > datetime('now')
    ");
    $stmt->execute([$token]);
    $conf = $stmt->fetch();

    if (!$conf) {
        return null;
    }

    // Mark as confirmed
    $stmt = $db->prepare("UPDATE confirmations SET confirmed = 1 WHERE id = ?");
    $stmt->execute([$conf['id']]);

    return $conf;
}
