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

        // Get players in this circuit with their club names
        $stmt = $db->prepare("
            SELECT p.id, p.first_name, p.last_name, c.name as club_name, r.rating
            FROM ratings r
            JOIN players p ON p.id = r.player_id
            JOIN clubs c ON c.id = p.club_id
            WHERE r.circuit_id = ? AND p.confirmed = 1
            ORDER BY p.last_name, p.first_name
        ");
        $stmt->execute([$circuitId]);
        $players = $stmt->fetchAll();

        echo json_encode(['players' => $players]);
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
            ORDER BY p.last_name, p.first_name
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
            WHERE c.confirmed = 1
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

    default:
        echo json_encode(['error' => 'Unknown action']);
        exit;
}
