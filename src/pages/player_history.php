<?php
/**
 * OpenElo - Player Circuit History
 * Shows all matches for a specific player in a specific circuit
 */

$db = Database::get();

$playerId = (int)($_GET['player'] ?? 0);
$circuitId = (int)($_GET['circuit'] ?? 0);

if (!$playerId || !$circuitId) {
    header('Location: ?page=circuits');
    exit;
}

// Get player info
$stmt = $db->prepare("
    SELECT p.*, c.name as club_name, c.id as club_id
    FROM players p
    JOIN clubs c ON c.id = p.club_id
    WHERE p.id = ?
");
$stmt->execute([$playerId]);
$player = $stmt->fetch();

if (!$player) {
    header('Location: ?page=circuits');
    exit;
}

// Get circuit info
$stmt = $db->prepare("SELECT * FROM circuits WHERE id = ?");
$stmt->execute([$circuitId]);
$circuit = $stmt->fetch();

if (!$circuit) {
    header('Location: ?page=player&id=' . $playerId);
    exit;
}

// Get player's rating in this circuit
$stmt = $db->prepare("
    SELECT rating, games_played
    FROM ratings
    WHERE player_id = ? AND circuit_id = ?
");
$stmt->execute([$playerId, $circuitId]);
$rating = $stmt->fetch();

// Get all approved matches for this player in this circuit with rating data
$stmt = $db->prepare("
    SELECT m.*,
        pw.id as white_id, pw.first_name as white_first, pw.last_name as white_last,
        pb.id as black_id, pb.first_name as black_first, pb.last_name as black_last,
        clw.name as white_club,
        clb.name as black_club,
        'match' as entry_type
    FROM matches m
    JOIN players pw ON pw.id = m.white_player_id
    JOIN players pb ON pb.id = m.black_player_id
    JOIN clubs clw ON clw.id = pw.club_id
    JOIN clubs clb ON clb.id = pb.club_id
    WHERE m.circuit_id = ?
        AND (m.white_player_id = ? OR m.black_player_id = ?)
        AND m.rating_applied = 1
        AND m.white_rating_before IS NOT NULL
        AND m.white_rating_before > 0
");
$stmt->execute([$circuitId, $playerId, $playerId]);
$matches = $stmt->fetchAll();

// Get all approved manual rating changes for this player in this circuit
$stmt = $db->prepare("
    SELECT
        requested_rating as new_rating,
        requested_category,
        created_at,
        'manual' as entry_type
    FROM manual_rating_requests
    WHERE player_id = ?
        AND circuit_id = ?
        AND completed = 1
        AND player_confirmed = 1
        AND president_confirmed = 1
        AND circuit_confirmed = 1
");
$stmt->execute([$playerId, $circuitId]);
$manualChanges = $stmt->fetchAll();

// Combine matches and manual changes, sort by date
$history = [];

// Add matches to history
foreach ($matches as $match) {
    $history[] = array_merge($match, ['sort_date' => $match['created_at']]);
}

// Add manual changes to history
foreach ($manualChanges as $change) {
    $history[] = array_merge($change, ['sort_date' => $change['created_at']]);
}

// Sort by date
usort($history, function($a, $b) {
    return strtotime($a['sort_date']) - strtotime($b['sort_date']);
});

$playerName = $player['first_name'] . ' ' . $player['last_name'];
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1>📊 <?= $lang === 'it' ? 'Storico Partite' : 'Match History' ?></h1>
            <div style="margin-top: 0.5rem; color: var(--text-secondary);">
                <a href="?page=player&id=<?= $playerId ?>"><?= htmlspecialchars($playerName) ?></a>
                <span style="margin: 0 0.5rem;">•</span>
                <a href="?page=circuit&id=<?= $circuitId ?>"><?= htmlspecialchars($circuit['name']) ?></a>
            </div>
        </div>
    </div>

    <?php if ($rating): ?>
    <div class="card" style="max-width: 400px; margin: 0 auto 2rem;">
        <div style="text-align: center;">
            <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                <?= $lang === 'it' ? 'Rating Attuale' : 'Current Rating' ?>
            </div>
            <div style="font-size: 2.5rem; font-weight: 700; color: var(--accent);">
                <?= $rating['rating'] ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                <?= $rating['games_played'] ?> <?= $rating['games_played'] == 1 ? ($lang === 'it' ? 'partita' : 'match') : ($lang === 'it' ? 'partite' : 'matches') ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <?php if (empty($matches)): ?>
        <div class="empty-state">
            <p><?= $lang === 'it' ? 'Nessuna partita registrata con dati di rating.' : 'No matches recorded with rating data.' ?></p>
            <p style="font-size: 0.9rem; color: var(--text-secondary);">
                <?= $lang === 'it'
                    ? 'Solo le partite create dopo l\'attivazione del sistema di tracking mostrano i parziali ELO.'
                    : 'Only matches created after the tracking system was activated show ELO changes.' ?>
            </p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= $lang === 'it' ? 'Data' : 'Date' ?></th>
                        <th><?= $lang === 'it' ? 'Avversario' : 'Opponent' ?></th>
                        <th><?= $lang === 'it' ? 'Circolo' : 'Club' ?></th>
                        <th><?= $lang === 'it' ? 'Risultato' : 'Result' ?></th>
                        <th><?= $lang === 'it' ? 'Rating' : 'Rating' ?></th>
                        <th><?= $lang === 'it' ? 'Variazione' : 'Change' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $matchNumber = 1;
                    foreach ($matches as $m):
                        $isWhite = ($m['white_id'] == $playerId);
                        $opponentId = $isWhite ? $m['black_id'] : $m['white_id'];
                        $opponentName = $isWhite
                            ? $m['black_first'] . ' ' . $m['black_last']
                            : $m['white_first'] . ' ' . $m['white_last'];
                        $opponentClub = $isWhite ? $m['black_club'] : $m['white_club'];

                        $ratingBefore = $isWhite ? $m['white_rating_before'] : $m['black_rating_before'];
                        $ratingChange = $isWhite ? $m['white_rating_change'] : $m['black_rating_change'];
                        $ratingAfter = $ratingBefore + $ratingChange;

                        // Determine result from player's perspective
                        if ($m['result'] === '0.5-0.5') {
                            $playerResult = '=';
                            $resultColor = 'var(--text-secondary)';
                        } elseif (($isWhite && $m['result'] === '1-0') || (!$isWhite && $m['result'] === '0-1')) {
                            $playerResult = 'W';
                            $resultColor = 'var(--success)';
                        } else {
                            $playerResult = 'L';
                            $resultColor = 'var(--error)';
                        }
                    ?>
                    <tr>
                        <td style="color: var(--text-secondary);"><?= $matchNumber++ ?></td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);">
                            <?= date('d/m/Y', strtotime($m['created_at'])) ?>
                        </td>
                        <td>
                            <?= $isWhite ? '♚' : '♔' ?>
                            <a href="?page=player&id=<?= $opponentId ?>">
                                <?= htmlspecialchars($opponentName) ?>
                            </a>
                        </td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);">
                            <?= htmlspecialchars($opponentClub) ?>
                        </td>
                        <td style="text-align: center;">
                            <strong style="color: <?= $resultColor ?>">
                                <?= $playerResult ?>
                            </strong>
                        </td>
                        <td style="text-align: center; font-size: 0.9rem;">
                            <span style="color: var(--text-secondary);"><?= $ratingBefore ?></span>
                            <span style="margin: 0 0.25rem;">→</span>
                            <strong><?= $ratingAfter ?></strong>
                        </td>
                        <td style="text-align: center; font-weight: bold; font-size: 1.1rem; color: <?= $ratingChange > 0 ? 'var(--success)' : ($ratingChange < 0 ? 'var(--error)' : 'var(--text-secondary)') ?>">
                            <?= $ratingChange > 0 ? '+' : '' ?><?= $ratingChange ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="?page=player&id=<?= $playerId ?>" class="btn btn-secondary">
            <?= $lang === 'it' ? '← Torna al Profilo' : '← Back to Profile' ?>
        </a>
    </div>
</div>
