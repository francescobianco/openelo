<?php
/**
 * OpenElo - Circuit Detail & Rankings
 */

require_once SRC_PATH . '/mail.php';

$db = Database::get();

$circuitId = (int)($_GET['id'] ?? 0);
$message = null;
$messageType = null;

// Handle resend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend_circuit') {
    $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ?");
    $stmt->execute([$circuitId]);
    $circuitData = $stmt->fetch();

    if ($circuitData && !$circuitData['confirmed']) {
        $token = createConfirmation('circuit', $circuitId, $circuitData['owner_email']);
        sendCircuitConfirmation($circuitData['owner_email'], $circuitData['name'], $token);
        $message = $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!';
        $messageType = 'success';
    }
}

// Get circuit (allow access even if not confirmed)
$stmt = $db->prepare("SELECT * FROM circuits WHERE id = ?");
$stmt->execute([$circuitId]);
$circuit = $stmt->fetch();

if (!$circuit) {
    header('Location: ?page=circuits');
    exit;
}

// Check pending confirmations
$pendingConfirmations = [];
if (!$circuit['confirmed']) {
    $pendingConfirmations[] = [
        'type' => 'circuit',
        'description' => $lang === 'it'
            ? 'Conferma del responsabile circuito (' . htmlspecialchars($circuit['owner_email']) . ')'
            : 'Circuit manager confirmation (' . htmlspecialchars($circuit['owner_email']) . ')'
    ];
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
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= $message ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($pendingConfirmations)): ?>
    <div class="alert alert-warning">
        <h3 style="margin-top: 0;">⏳ <?= $lang === 'it' ? 'Approvazioni in attesa' : 'Pending Approvals' ?></h3>
        <p><?= $lang === 'it' ? 'Questo circuito non è ancora visibile pubblicamente. Sono necessarie le seguenti approvazioni:' : 'This circuit is not yet publicly visible. The following approvals are required:' ?></p>
        <ul style="margin: 1rem 0;">
            <?php foreach ($pendingConfirmations as $pending): ?>
            <li style="margin: 0.5rem 0;">
                <?= $pending['description'] ?>
                <?php if ($pending['type'] === 'circuit'): ?>
                <form method="POST" style="display: inline; margin-left: 1rem;">
                    <input type="hidden" name="action" value="resend_circuit">
                    <button type="submit" class="btn btn-sm"><?= $lang === 'it' ? 'Invia di nuovo richiesta' : 'Resend request' ?></button>
                </form>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($circuit['name']) ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= count($clubs) ?> <?= __('circuit_clubs') ?></span>
                <span><?= count($rankings) ?> <?= __('circuit_players') ?></span>
                <span><?= count($matches) ?> <?= __('circuit_matches') ?></span>
            </div>
        </div>
        <?php if ($circuit['confirmed']): ?>
        <a href="?page=submit&circuit=<?= $circuitId ?>" class="btn btn-primary"><?= __('nav_submit_result') ?></a>
        <?php endif; ?>
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
                        <td class="rank <?= $i < 3 ? 'rank-' . ($i + 1) : '' ?>"><?= $i + 1 ?></td>
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

    <!-- Deletion Request Link -->
    <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border);">
        <details style="display: inline-block; text-align: left; max-width: 500px;">
            <summary style="cursor: pointer; color: var(--text-secondary); font-size: 0.9rem;">
                🗑 <?= $lang === 'it' ? 'Segnala / Richiedi Eliminazione' : 'Report / Request Deletion' ?>
            </summary>
            <form method="POST" action="?page=deletion" style="margin-top: 1rem; padding: 1rem; background: var(--bg-card); border-radius: 8px;">
                <input type="hidden" name="entity_type" value="circuit">
                <input type="hidden" name="entity_id" value="<?= $circuitId ?>">
                <div class="form-group">
                    <label><?= $lang === 'it' ? 'Tua Email' : 'Your Email' ?></label>
                    <input type="email" name="requester_email" required>
                </div>
                <div class="form-group">
                    <label><?= $lang === 'it' ? 'Motivo della richiesta' : 'Reason for request' ?></label>
                    <textarea name="reason" rows="3" required></textarea>
                </div>
                <button type="submit" name="request_deletion" class="btn btn-sm btn-secondary">
                    <?= $lang === 'it' ? 'Invia Richiesta' : 'Submit Request' ?>
                </button>
            </form>
        </details>
    </div>
</div>
