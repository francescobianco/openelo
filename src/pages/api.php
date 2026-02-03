<?php
/**
 * OpenElo - Simple API for AJAX calls
 */

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'circuit_data':
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
            WHERE cc.circuit_id = ? AND cc.confirmed = 1 AND c.confirmed = 1
            ORDER BY c.name
        ");
        $stmt->execute([$circuitId]);
        $clubs = $stmt->fetchAll();

        // Get players with ratings in this circuit
        $stmt = $db->prepare("
            SELECT p.id, p.first_name, p.last_name, COALESCE(r.rating, ?) as rating
            FROM players p
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

    default:
        echo json_encode(['error' => 'Unknown action']);
        exit;
}
