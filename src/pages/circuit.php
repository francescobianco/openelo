<?php
/**
 * OpenElo - Circuit Detail & Rankings
 */

$db = Database::get();

$circuitId = (int)($_GET['id'] ?? 0);

// Get circuit
$stmt = $db->prepare("SELECT * FROM circuits WHERE id = ? AND confirmed = 1");
$stmt->execute([$circuitId]);
$circuit = $stmt->fetch();

if (!$circuit) {
    header('Location: ?page=circuits');
    exit;
}

// Get clubs in circuit
$stmt = $db->prepare("
    SELECT cl.* FROM clubs cl
    JOIN circuit_clubs cc ON cc.club_id = cl.id
    WHERE cc.circuit_id = ? AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
    ORDER BY cl.name
");
$stmt->execute([$circuitId]);
$clubs = $stmt->fetchAll();

// Get rankings
$stmt = $db->prepare("
    SELECT p.*, r.rating, r.games_played, cl.name as club_name, cl.id as club_id
    FROM ratings r
    JOIN players p ON p.id = r.player_id
    JOIN clubs cl ON cl.id = p.club_id
    WHERE r.circuit_id = ? AND p.confirmed = 1
    ORDER BY r.rating DESC
");
$stmt->execute([$circuitId]);
$rankings = $stmt->fetchAll();

// Get recent matches
$stmt = $db->prepare("
    SELECT m.*,
        pw.id as white_id, pw.first_name as white_first, pw.last_name as white_last,
        pb.id as black_id, pb.first_name as black_first, pb.last_name as black_last,
        cw.name as white_club, cb.name as black_club
    FROM matches m
    JOIN players pw ON pw.id = m.white_player_id
    JOIN players pb ON pb.id = m.black_player_id
    JOIN clubs cw ON cw.id = pw.club_id
    JOIN clubs cb ON cb.id = pb.club_id
    WHERE m.circuit_id = ? AND m.rating_applied = 1
    ORDER BY m.created_at DESC
    LIMIT 20
");
$stmt->execute([$circuitId]);
$matches = $stmt->fetchAll();

// Current tab
$tab = $_GET['tab'] ?? 'rankings';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($circuit['name']) ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= count($clubs) ?> <?= __('circuit_clubs') ?></span>
                <span><?= count($rankings) ?> <?= __('circuit_players') ?></span>
                <span><?= count($matches) ?> <?= __('circuit_matches') ?></span>
            </div>
        </div>
        <a href="?page=submit&circuit=<?= $circuitId ?>" class="btn btn-primary"><?= __('nav_submit_result') ?></a>
    </div>

    <div class="tabs">
        <a href="?page=circuit&id=<?= $circuitId ?>&tab=rankings" class="tab <?= $tab === 'rankings' ? 'active' : '' ?>"><?= __('rankings_title') ?></a>
        <a href="?page=circuit&id=<?= $circuitId ?>&tab=clubs" class="tab <?= $tab === 'clubs' ? 'active' : '' ?>"><?= __('circuit_clubs') ?></a>
        <a href="?page=circuit&id=<?= $circuitId ?>&tab=matches" class="tab <?= $tab === 'matches' ? 'active' : '' ?>"><?= __('circuit_matches') ?></a>
    </div>

    <?php if ($tab === 'rankings'): ?>
    <div class="card">
        <?php if (empty($rankings)): ?>
        <div class="empty-state">
            <p><?= $lang === 'it' ? 'Nessun giocatore ancora. Inizia a registrare partite!' : 'No players yet. Start submitting matches!' ?></p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= __('rankings_position') ?></th>
                        <th><?= __('rankings_player') ?></th>
                        <th><?= __('rankings_club') ?></th>
                        <th><?= __('rankings_rating') ?></th>
                        <th><?= __('rankings_games') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankings as $i => $player): ?>
                    <tr>
                        <td class="rank"><?= $i + 1 ?></td>
                        <td><a href="?page=player&id=<?= $player['id'] ?>"><?= htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) ?></a></td>
                        <td><a href="?page=club&id=<?= $player['club_id'] ?>"><?= htmlspecialchars($player['club_name']) ?></a></td>
                        <td class="rating"><?= $player['rating'] ?></td>
                        <td><?= $player['games_played'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif ($tab === 'clubs'): ?>
    <div class="circuits-grid">
        <?php if (empty($clubs)): ?>
        <div class="empty-state">
            <p><?= $lang === 'it' ? 'Nessun circolo ancora.' : 'No clubs yet.' ?></p>
        </div>
        <?php else: ?>
        <?php foreach ($clubs as $club): ?>
        <div class="circuit-card">
            <h3><a href="?page=club&id=<?= $club['id'] ?>"><?= htmlspecialchars($club['name']) ?></a></h3>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php elseif ($tab === 'matches'): ?>
    <div class="card">
        <?php if (empty($matches)): ?>
        <div class="empty-state">
            <p><?= $lang === 'it' ? 'Nessuna partita ancora.' : 'No matches yet.' ?></p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= __('form_white') ?></th>
                        <th><?= __('form_result') ?></th>
                        <th><?= __('form_black') ?></th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matches as $match): ?>
                    <tr>
                        <td>
                            <a href="?page=player&id=<?= $match['white_id'] ?>"><?= htmlspecialchars($match['white_first'] . ' ' . $match['white_last']) ?></a>
                            <small style="color: var(--text-secondary);">(<?= htmlspecialchars($match['white_club']) ?>)</small>
                        </td>
                        <td><strong><?= $match['result'] ?></strong></td>
                        <td>
                            <a href="?page=player&id=<?= $match['black_id'] ?>"><?= htmlspecialchars($match['black_first'] . ' ' . $match['black_last']) ?></a>
                            <small style="color: var(--text-secondary);">(<?= htmlspecialchars($match['black_club']) ?>)</small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($match['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
