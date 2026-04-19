<?php
/**
 * OpenElo - API for AJAX calls
 */

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'circuit_players':
        $circuitId = (int)($_GET['circuit_id'] ?? 0);

        if (!$circuitId) {
            echo json_encode(['error' => 'Missing circuit_id']);
            exit;
        }

        $db = Database::get();

        $stmtCircuit = $db->prepare("SELECT formula FROM circuits WHERE id = ?");
        $stmtCircuit->execute([$circuitId]);
        $circuitFormula = $stmtCircuit->fetchColumn() ?: 'classic_elo';

        // Get players in this circuit via club membership
        $stmt = $db->prepare("
            SELECT p.id, p.first_name, p.last_name, c.name as club_name,
                (SELECT r.rating FROM ratings r WHERE r.player_id = p.id AND r.circuit_id = ?) as rating,
                (SELECT r.ladder_position FROM ratings r WHERE r.player_id = p.id AND r.circuit_id = ?) as ladder_position
            FROM players p
            JOIN clubs c ON c.id = p.club_id
            JOIN circuit_clubs cc ON cc.club_id = p.club_id
            WHERE cc.circuit_id = ? AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
            AND p.confirmed = 1 AND p.deleted_at IS NULL AND c.deleted_at IS NULL
            ORDER BY LOWER(p.last_name), LOWER(p.first_name)
        ");
        $stmt->execute([$circuitId, $circuitId, $circuitId]);
        $players = $stmt->fetchAll();

        $stmtClubCount = $db->prepare("SELECT COUNT(*) FROM circuit_clubs WHERE circuit_id = ? AND club_confirmed = 1 AND circuit_confirmed = 1");
        $stmtClubCount->execute([$circuitId]);
        $clubCount = (int)$stmtClubCount->fetchColumn();

        echo json_encode(['players' => $players, 'formula' => $circuitFormula, 'club_count' => $clubCount]);
        exit;

    case 'circuit_data':
        // Legacy endpoint - keep for compatibility
        $circuitId = (int)($_GET['circuit_id'] ?? 0);

        if (!$circuitId) {
            echo json_encode(['error' => 'Missing circuit_id']);
            exit;
        }

        $db = Database::get();

        // Get clubs in circuit
        $stmt = $db->prepare("
            SELECT c.id, c.name FROM clubs c
            JOIN circuit_clubs cc ON cc.club_id = c.id
            WHERE cc.circuit_id = ? AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
            ORDER BY c.name
        ");
        $stmt->execute([$circuitId]);
        $clubs = $stmt->fetchAll();

        // Get players with ratings in this circuit
        $stmt = $db->prepare("
            SELECT p.id, p.first_name, p.last_name, c.name as club_name, COALESCE(r.rating, ?) as rating
            FROM players p
            JOIN clubs c ON c.id = p.club_id
            LEFT JOIN ratings r ON r.player_id = p.id AND r.circuit_id = ?
            WHERE p.confirmed = 1
            ORDER BY LOWER(p.last_name), LOWER(p.first_name)
        ");
        $stmt->execute([ELO_START, $circuitId]);
        $players = $stmt->fetchAll();

        echo json_encode([
            'clubs' => $clubs,
            'players' => $players
        ]);
        exit;

    case 'club_circuits':
        // Get circuits a club can join (not already member)
        $clubId = (int)($_GET['club_id'] ?? 0);

        if (!$clubId) {
            echo json_encode(['error' => 'Missing club_id']);
            exit;
        }

        $db = Database::get();

        $stmt = $db->prepare("
            SELECT c.* FROM circuits c
            WHERE c.confirmed = 1 AND c.deleted_at IS NULL
            AND NOT EXISTS (
                SELECT 1 FROM circuit_clubs cc
                WHERE cc.circuit_id = c.id AND cc.club_id = ?
            )
            ORDER BY c.name
        ");
        $stmt->execute([$clubId]);
        $circuits = $stmt->fetchAll();

        echo json_encode(['circuits' => $circuits]);
        exit;

    case 'demo_seed':
        // Authenticate via X-App-Secret header
        $secret = APP_SECRET;
        if (empty($secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Demo seed is disabled (APP_SECRET not configured)']);
            exit;
        }

        $incoming = $_SERVER['HTTP_X_APP_SECRET'] ?? '';
        if (!hash_equals($secret, $incoming)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or missing X-App-Secret header']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }

        $raw = file_get_contents('php://input');
        $seed = json_decode($raw, true);
        if (!$seed || !isset($seed['circuit'], $seed['clubs'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON: missing circuit or clubs']);
            exit;
        }

        $db = Database::get();

        try {
            $db->beginTransaction();

            // Upsert circuit by name
            $circuitName  = $seed['circuit']['name'];
            $circuitEmail = $seed['circuit']['owner_email'];
            $circuitFormula = $seed['circuit']['formula'] ?? 'classic_elo';

            $stmtFind = $db->prepare("SELECT id FROM circuits WHERE name = ?");
            $stmtFind->execute([$circuitName]);
            $circuitId = $stmtFind->fetchColumn();

            if ($circuitId) {
                $stmt = $db->prepare("UPDATE circuits SET owner_email = ?, formula = ?, confirmed = 1 WHERE id = ?");
                $stmt->execute([$circuitEmail, $circuitFormula, $circuitId]);
                $circuitStatus = 'updated';
            } else {
                $stmt = $db->prepare("INSERT INTO circuits (name, owner_email, formula, confirmed) VALUES (?, ?, ?, 1)");
                $stmt->execute([$circuitName, $circuitEmail, $circuitFormula]);
                $circuitId = (int)$db->lastInsertId();
                $circuitStatus = 'created';
            }

            $stats = ['circuit_id' => $circuitId, 'circuit_status' => $circuitStatus, 'clubs' => [], 'players_created' => 0, 'players_updated' => 0];

            foreach ($seed['clubs'] as $clubData) {
                // Upsert club by name
                $stmtFind = $db->prepare("SELECT id FROM clubs WHERE name = ?");
                $stmtFind->execute([$clubData['name']]);
                $clubId = $stmtFind->fetchColumn();

                if ($clubId) {
                    $stmt = $db->prepare("UPDATE clubs SET president_email = ?, confirmed = 1 WHERE id = ?");
                    $stmt->execute([$clubData['president_email'], $clubId]);
                    $clubCreated = false;
                } else {
                    $stmt = $db->prepare("INSERT INTO clubs (name, president_email, confirmed) VALUES (?, ?, 1)");
                    $stmt->execute([$clubData['name'], $clubData['president_email']]);
                    $clubId = (int)$db->lastInsertId();
                    $clubCreated = true;
                }

                // Upsert circuit_clubs membership
                $stmtCheck = $db->prepare("SELECT id FROM circuit_clubs WHERE circuit_id = ? AND club_id = ?");
                $stmtCheck->execute([$circuitId, $clubId]);
                if (!$stmtCheck->fetchColumn()) {
                    $stmt = $db->prepare("INSERT INTO circuit_clubs (circuit_id, club_id, club_confirmed, circuit_confirmed) VALUES (?, ?, 1, 1)");
                    $stmt->execute([$circuitId, $clubId]);
                } else {
                    $stmt = $db->prepare("UPDATE circuit_clubs SET club_confirmed = 1, circuit_confirmed = 1 WHERE circuit_id = ? AND club_id = ?");
                    $stmt->execute([$circuitId, $clubId]);
                }

                $clubStats = ['club_id' => $clubId, 'name' => $clubData['name'], 'status' => $clubCreated ? 'created' : 'updated', 'players' => []];

                foreach ($clubData['players'] as $playerData) {
                    // Upsert player by email
                    $stmtFind = $db->prepare("SELECT id FROM players WHERE email = ?");
                    $stmtFind->execute([$playerData['email']]);
                    $playerId = $stmtFind->fetchColumn();

                    if ($playerId) {
                        $stmt = $db->prepare("UPDATE players SET first_name = ?, last_name = ?, club_id = ?, category = ?, confirmed = 1 WHERE id = ?");
                        $stmt->execute([
                            $playerData['first_name'],
                            $playerData['last_name'],
                            $clubId,
                            $playerData['category'] ?? 'NC',
                            $playerId
                        ]);
                        $stats['players_updated']++;
                        $clubStats['players'][] = ['id' => $playerId, 'status' => 'updated'];
                    } else {
                        $stmt = $db->prepare("INSERT INTO players (first_name, last_name, email, club_id, category, confirmed) VALUES (?, ?, ?, ?, ?, 1)");
                        $stmt->execute([
                            $playerData['first_name'],
                            $playerData['last_name'],
                            $playerData['email'],
                            $clubId,
                            $playerData['category'] ?? 'NC'
                        ]);
                        $playerId = (int)$db->lastInsertId();
                        $stats['players_created']++;
                        $clubStats['players'][] = ['id' => $playerId, 'status' => 'created'];
                    }
                }

                $stats['clubs'][] = $clubStats;
            }

            $db->commit();
            echo json_encode(['ok' => true, 'stats' => $stats]);

        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;

    default:
        echo json_encode(['error' => 'Unknown action']);
        exit;
}
