<?php
/**
 * OpenElo - Elo Calculation
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Calculate expected score
 */
function expectedScore(int $ratingA, int $ratingB): float {
    return 1 / (1 + pow(10, ($ratingB - $ratingA) / 400));
}

/**
 * Get K factor for a player in a circuit
 */
function getKFactor(int $playerId, int $circuitId): int {
    $db = Database::get();

    $stmt = $db->prepare("
        SELECT rating, games_played FROM ratings
        WHERE player_id = ? AND circuit_id = ?
    ");
    $stmt->execute([$playerId, $circuitId]);
    $data = $stmt->fetch();

    if (!$data) {
        return ELO_K_NEW;
    }

    if ($data['games_played'] < ELO_GAMES_THRESHOLD) {
        return ELO_K_NEW;
    }

    if ($data['rating'] >= ELO_HIGH_RATING) {
        return ELO_K_HIGH;
    }

    return ELO_K_NORMAL;
}

/**
 * Get or create rating for player in circuit
 */
function getOrCreateRating(int $playerId, int $circuitId): array {
    $db = Database::get();

    $stmt = $db->prepare("
        SELECT * FROM ratings WHERE player_id = ? AND circuit_id = ?
    ");
    $stmt->execute([$playerId, $circuitId]);
    $rating = $stmt->fetch();

    if (!$rating) {
        $stmt = $db->prepare("
            INSERT INTO ratings (player_id, circuit_id, rating, games_played)
            VALUES (?, ?, ?, 0)
        ");
        $stmt->execute([$playerId, $circuitId, ELO_START]);

        return [
            'player_id' => $playerId,
            'circuit_id' => $circuitId,
            'rating' => ELO_START,
            'games_played' => 0
        ];
    }

    return $rating;
}

/**
 * Apply rating change for a confirmed match
 * Result: '1-0' = white wins, '0-1' = black wins, '0.5-0.5' = draw
 */
function applyRatingChange(int $matchId): bool {
    $db = Database::get();

    // Get match
    $stmt = $db->prepare("SELECT * FROM matches WHERE id = ? AND rating_applied = 0");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch();

    if (!$match) {
        return false;
    }

    // Check all confirmations
    if (!$match['white_confirmed'] || !$match['black_confirmed'] || !$match['president_confirmed']) {
        return false;
    }

    $circuitId = $match['circuit_id'];
    $whiteId = $match['white_player_id'];
    $blackId = $match['black_player_id'];

    // Get ratings
    $whiteRating = getOrCreateRating($whiteId, $circuitId);
    $blackRating = getOrCreateRating($blackId, $circuitId);

    // Calculate expected scores
    $expectedWhite = expectedScore($whiteRating['rating'], $blackRating['rating']);
    $expectedBlack = 1 - $expectedWhite;

    // Actual scores based on result
    switch ($match['result']) {
        case '1-0':
            $scoreWhite = 1;
            $scoreBlack = 0;
            break;
        case '0-1':
            $scoreWhite = 0;
            $scoreBlack = 1;
            break;
        case '0.5-0.5':
            $scoreWhite = 0.5;
            $scoreBlack = 0.5;
            break;
        default:
            return false;
    }

    // Get K factors
    $kWhite = getKFactor($whiteId, $circuitId);
    $kBlack = getKFactor($blackId, $circuitId);

    // Calculate new ratings
    $newWhiteRating = round($whiteRating['rating'] + $kWhite * ($scoreWhite - $expectedWhite));
    $newBlackRating = round($blackRating['rating'] + $kBlack * ($scoreBlack - $expectedBlack));

    // Update ratings
    $stmt = $db->prepare("
        UPDATE ratings SET rating = ?, games_played = games_played + 1
        WHERE player_id = ? AND circuit_id = ?
    ");
    $stmt->execute([$newWhiteRating, $whiteId, $circuitId]);
    $stmt->execute([$newBlackRating, $blackId, $circuitId]);

    // Mark match as processed
    $stmt = $db->prepare("UPDATE matches SET rating_applied = 1 WHERE id = ?");
    $stmt->execute([$matchId]);

    return true;
}
