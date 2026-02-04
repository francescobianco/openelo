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

// Get clubs in circuit with player count
$stmt = $db->prepare("
    SELECT cl.*,
        COUNT(DISTINCT p.id) as player_count
    FROM clubs cl
    JOIN circuit_clubs cc ON cc.club_id = cl.id
    LEFT JOIN players p ON p.club_id = cl.id AND p.confirmed = 1
    WHERE cc.circuit_id = ? AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
    GROUP BY cl.id
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
    <div class="alert alert-warning" style="display: flex; gap: 1rem;">
        <div class="pending-icon">⏳</div>
        <div style="flex: 1;">
            <h3 style="margin: 0 0 0.5rem 0;"><?= $lang === 'it' ? 'Approvazioni in attesa' : 'Pending Approvals' ?></h3>
            <p style="margin: 0 0 1rem 0;"><?= $lang === 'it' ? 'Questo circuito non è ancora visibile pubblicamente. Sono necessarie le seguenti approvazioni:' : 'This circuit is not yet publicly visible. The following approvals are required:' ?></p>
            <ul class="pending-approvals-list">
                <?php foreach ($pendingConfirmations as $pending): ?>
                <li>
                    <?= $pending['description'] ?>
                    <?php if ($pending['type'] === 'circuit'): ?>
                    <form method="POST" style="display: inline; margin-left: 1rem;">
                        <input type="hidden" name="action" value="resend_circuit">
                        <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: inherit;">
                            <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
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
                        <th><?= $lang === 'it' ? 'Categoria' : 'Category' ?></th>
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
                        <td><strong><?= htmlspecialchars($player['category'] ?: 'NC') ?></strong></td>
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
            <div class="circuit-meta">
                <span><?= $club['player_count'] ?> <?= $club['player_count'] == 1 ? ($lang === 'it' ? 'giocatore' : 'player') : ($lang === 'it' ? 'giocatori' : 'players') ?></span>
            </div>
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matches as $match): ?>
                    <tr>
                        <td>
                            <a href="?page=player&id=<?= $match['white_id'] ?>"><?= htmlspecialchars($match['white_first'] . ' ' . $match['white_last']) ?></a>
                            <small style="color: var(--text-secondary);">(<?= htmlspecialchars($match['white_club']) ?>)</small>
                        </td>
                        <td><strong><?= str_replace('-', ' - ', $match['result']) ?></strong></td>
                        <td>
                            <a href="?page=player&id=<?= $match['black_id'] ?>"><?= htmlspecialchars($match['black_first'] . ' ' . $match['black_last']) ?></a>
                            <small style="color: var(--text-secondary);">(<?= htmlspecialchars($match['black_club']) ?>)</small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($match['created_at'])) ?></td>
                        <td>
                            <a href="?page=match&id=<?= $match['id'] ?>" class="btn btn-sm btn-secondary">
                                <?= $lang === 'it' ? 'Dettagli' : 'Details' ?>
                            </a>
                        </td>
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
        <button onclick="openModal('deletion-modal')" class="deletion-link" style="background: none; border: none; cursor: pointer; font-size: 0.9rem; padding: 0;">
            🗑 <?= $lang === 'it' ? 'Segnala / Richiedi Eliminazione' : 'Report / Request Deletion' ?>
        </button>
    </div>

    <!-- Deletion Request Modal -->
    <div id="deletion-modal" class="modal-overlay">
        <div class="modal-content">
            <button onclick="closeModal('deletion-modal')" class="modal-close">&times;</button>
            <h3 class="modal-title">🗑 <?= $lang === 'it' ? 'Segnala / Richiedi Eliminazione' : 'Report / Request Deletion' ?></h3>
            <form method="POST" action="?page=deletion">
                <input type="hidden" name="entity_type" value="circuit">
                <input type="hidden" name="entity_id" value="<?= $circuitId ?>">
                <div class="form-group">
                    <label><?= $lang === 'it' ? 'Tua Email' : 'Your Email' ?></label>
                    <input type="email" name="requester_email" required>
                </div>
                <div class="form-group">
                    <label><?= $lang === 'it' ? 'Motivo della richiesta' : 'Reason for request' ?></label>
                    <textarea name="reason" rows="4" required style="width: 100%; padding: 0.8rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; color: var(--text-primary); font-family: inherit;"></textarea>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" name="request_deletion" class="btn btn-primary">
                        <?= $lang === 'it' ? 'Invia Richiesta' : 'Submit Request' ?>
                    </button>
                    <button type="button" onclick="closeModal('deletion-modal')" class="btn btn-secondary">
                        <?= $lang === 'it' ? 'Annulla' : 'Cancel' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
