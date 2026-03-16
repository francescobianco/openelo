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
 * Calculate expected rating changes for a match
 * Returns array with white/black ratings and their expected changes
 */
function calculateRatingChanges(int $whiteId, int $blackId, int $circuitId, string $result): array {
    // Get current ratings
    $whiteRating = getOrCreateRating($whiteId, $circuitId);
    $blackRating = getOrCreateRating($blackId, $circuitId);

    // Calculate expected scores
    $expectedWhite = expectedScore($whiteRating['rating'], $blackRating['rating']);
    $expectedBlack = 1 - $expectedWhite;

    // Actual scores based on result
    switch ($result) {
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
            return [
                'white_rating' => $whiteRating['rating'],
                'black_rating' => $blackRating['rating'],
                'white_change' => 0,
                'black_change' => 0
            ];
    }

    // Get K factors
    $kWhite = getKFactor($whiteId, $circuitId);
    $kBlack = getKFactor($blackId, $circuitId);

    // Calculate rating changes
    $whiteChange = round($kWhite * ($scoreWhite - $expectedWhite));
    $blackChange = round($kBlack * ($scoreBlack - $expectedBlack));

    return [
        'white_rating' => $whiteRating['rating'],
        'black_rating' => $blackRating['rating'],
        'white_change' => $whiteChange,
        'black_change' => $blackChange
    ];
}

/**
 * Get or create ladder position for a player in a ladder circuit.
 * New players are assigned the next available (last) position.
 */
function getOrCreateLadderPosition(int $playerId, int $circuitId): int {
    $db = Database::get();

    $stmt = $db->prepare("SELECT ladder_position FROM ratings WHERE player_id = ? AND circuit_id = ?");
    $stmt->execute([$playerId, $circuitId]);
    $row = $stmt->fetch();

    if ($row && $row['ladder_position'] !== null) {
        return (int)$row['ladder_position'];
    }

    // Assign next available position
    $stmt = $db->prepare("SELECT COALESCE(MAX(ladder_position), 0) + 1 FROM ratings WHERE circuit_id = ?");
    $stmt->execute([$circuitId]);
    $nextPos = (int)$stmt->fetchColumn();

    if ($row) {
        $stmt = $db->prepare("UPDATE ratings SET ladder_position = ? WHERE player_id = ? AND circuit_id = ?");
        $stmt->execute([$nextPos, $playerId, $circuitId]);
    } else {
        $stmt = $db->prepare("INSERT INTO ratings (player_id, circuit_id, rating, games_played, ladder_position) VALUES (?, ?, ?, 0, ?)");
        $stmt->execute([$playerId, $circuitId, ELO_START, $nextPos]);
    }

    return $nextPos;
}

/**
 * Apply ladder position change for a confirmed match (ladder_3up_scorrimento formula).
 * If the lower-ranked player wins, they take the higher-ranked player's spot and everyone
 * in between slides down one position.
 * If the higher-ranked player wins, no positions change.
 */
function applyLadderPositionChange(int $matchId): bool {
    $db = Database::get();

    $stmt = $db->prepare("SELECT * FROM matches WHERE id = ? AND rating_applied = 0");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch();

    if (!$match) {
        return false;
    }

    if (!$match['white_confirmed'] || !$match['black_confirmed'] || !$match['president_confirmed']) {
        return false;
    }

    $circuitId = $match['circuit_id'];
    $whiteId   = $match['white_player_id'];
    $blackId   = $match['black_player_id'];

    $whitePos = getOrCreateLadderPosition($whiteId, $circuitId);
    $blackPos = getOrCreateLadderPosition($blackId, $circuitId);

    $result = $match['result'];

    if ($result === '1-0') {
        $winnerId  = $whiteId;
        $loserId   = $blackId;
        $winnerPos = $whitePos;
        $loserPos  = $blackPos;
    } elseif ($result === '0-1') {
        $winnerId  = $blackId;
        $loserId   = $whiteId;
        $winnerPos = $blackPos;
        $loserPos  = $whitePos;
    } else {
        // Draw — not expected, mark applied and bail
        $stmt = $db->prepare("UPDATE matches SET rating_applied = 1 WHERE id = ?");
        $stmt->execute([$matchId]);
        return true;
    }

    if ($winnerPos > $loserPos) {
        // Lower-ranked player (higher number) beat the higher-ranked one → sliding
        // Shift everyone between loserPos and winnerPos-1 (inclusive) down by 1
        $stmt = $db->prepare("
            UPDATE ratings SET ladder_position = ladder_position + 1
            WHERE circuit_id = ? AND ladder_position >= ? AND ladder_position < ?
        ");
        $stmt->execute([$circuitId, $loserPos, $winnerPos]);

        // Winner moves to the top spot
        $stmt = $db->prepare("UPDATE ratings SET ladder_position = ? WHERE player_id = ? AND circuit_id = ?");
        $stmt->execute([$loserPos, $winnerId, $circuitId]);
    }
    // If higher-ranked player wins, no position changes

    // Increment games_played for both
    $stmt = $db->prepare("UPDATE ratings SET games_played = games_played + 1 WHERE player_id = ? AND circuit_id = ?");
    $stmt->execute([$winnerId, $circuitId]);
    $stmt->execute([$loserId, $circuitId]);

    $stmt = $db->prepare("UPDATE matches SET rating_applied = 1 WHERE id = ?");
    $stmt->execute([$matchId]);

    return true;
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

    // Use stored rating changes (frozen at match creation time)
    $whiteChange = $match['white_rating_change'];
    $blackChange = $match['black_rating_change'];

    // Get current ratings
    $whiteRating = getOrCreateRating($whiteId, $circuitId);
    $blackRating = getOrCreateRating($blackId, $circuitId);

    // Calculate new ratings using the frozen changes
    $newWhiteRating = $whiteRating['rating'] + $whiteChange;
    $newBlackRating = $blackRating['rating'] + $blackChange;

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
