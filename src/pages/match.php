<?php
/**
 * OpenElo - Match Detail Page
 */

require_once SRC_PATH . '/mail.php';

$db = Database::get();

$matchId = (int)($_GET['id'] ?? 0);
$message = null;
$messageType = null;

// Handle resend requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend_match') {
    $role = $_POST['role'] ?? '';

    $stmt = $db->prepare("
        SELECT m.*,
            pw.email as white_email, pw.first_name as white_first, pw.last_name as white_last,
            pw.club_id as white_club_id,
            pb.email as black_email, pb.first_name as black_first, pb.last_name as black_last,
            pb.club_id as black_club_id,
            c.name as circuit_name, c.owner_email as circuit_owner_email,
            cl.president_email
        FROM matches m
        JOIN players pw ON pw.id = m.white_player_id
        JOIN players pb ON pb.id = m.black_player_id
        JOIN circuits c ON c.id = m.circuit_id
        JOIN clubs cl ON cl.id = pw.club_id
        WHERE m.id = ?
    ");
    $stmt->execute([$matchId]);
    $matchData = $stmt->fetch();

    if ($matchData) {
        // Determine if players are from the same club
        $sameClub = ($matchData['white_club_id'] === $matchData['black_club_id']);

        $matchDetails = [
            'white_name' => $matchData['white_first'] . ' ' . $matchData['white_last'],
            'black_name' => $matchData['black_first'] . ' ' . $matchData['black_last'],
            'result' => $matchData['result'],
            'circuit_name' => $matchData['circuit_name']
        ];

        $email = '';
        $confirmRole = '';

        if ($role === 'white' && !$matchData['white_confirmed']) {
            $email = $matchData['white_email'];
            $confirmRole = 'white';
        } elseif ($role === 'black' && !$matchData['black_confirmed']) {
            $email = $matchData['black_email'];
            $confirmRole = 'black';
        } elseif ($role === 'president' && !$matchData['president_confirmed'] && $sameClub) {
            $email = $matchData['president_email'];
            $confirmRole = 'president';
        } elseif ($role === 'circuit_manager' && !$matchData['president_confirmed'] && !$sameClub) {
            $email = $matchData['circuit_owner_email'];
            $confirmRole = 'president';
        }

        if ($email) {
            $token = createConfirmation('match', $matchId, $email, $confirmRole);
            sendMatchConfirmation($email, $confirmRole, $matchDetails, $token);
            $message = $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!';
            $messageType = 'success';
        }
    }
}

// Get match details
$stmt = $db->prepare("
    SELECT m.*,
        pw.id as white_id, pw.first_name as white_first, pw.last_name as white_last, pw.email as white_email, pw.club_id as white_club_id,
        pb.id as black_id, pb.first_name as black_first, pb.last_name as black_last, pb.email as black_email, pb.club_id as black_club_id,
        c.name as circuit_name, c.id as circuit_id, c.owner_email as circuit_owner_email,
        clw.name as white_club, clw.president_email as president_email,
        clb.name as black_club
    FROM matches m
    JOIN players pw ON pw.id = m.white_player_id
    JOIN players pb ON pb.id = m.black_player_id
    JOIN circuits c ON c.id = m.circuit_id
    JOIN clubs clw ON clw.id = pw.club_id
    JOIN clubs clb ON clb.id = pb.club_id
    WHERE m.id = ?
");
$stmt->execute([$matchId]);
$match = $stmt->fetch();

if (!$match) {
    header('Location: ?page=circuits');
    exit;
}

// Determine if players are from the same club
$sameClub = ($match['white_club_id'] === $match['black_club_id']);

// Check pending confirmations
$pendingConfirmations = [];
if (!$match['white_confirmed']) {
    $pendingConfirmations[] = [
        'type' => 'white',
        'description' => $lang === 'it'
            ? 'Conferma del giocatore con il Bianco: ' . htmlspecialchars($match['white_first'] . ' ' . $match['white_last']) . ' (' . htmlspecialchars($match['white_email']) . ')'
            : 'White player confirmation: ' . htmlspecialchars($match['white_first'] . ' ' . $match['white_last']) . ' (' . htmlspecialchars($match['white_email']) . ')'
    ];
}
if (!$match['black_confirmed']) {
    $pendingConfirmations[] = [
        'type' => 'black',
        'description' => $lang === 'it'
            ? 'Conferma del giocatore con il Nero: ' . htmlspecialchars($match['black_first'] . ' ' . $match['black_last']) . ' (' . htmlspecialchars($match['black_email']) . ')'
            : 'Black player confirmation: ' . htmlspecialchars($match['black_first'] . ' ' . $match['black_last']) . ' (' . htmlspecialchars($match['black_email']) . ')'
    ];
}
if (!$match['president_confirmed']) {
    if ($sameClub) {
        $pendingConfirmations[] = [
            'type' => 'president',
            'description' => $lang === 'it'
                ? 'Conferma del presidente del circolo (' . htmlspecialchars($match['president_email']) . ')'
                : 'Club president confirmation (' . htmlspecialchars($match['president_email']) . ')'
        ];
    } else {
        $pendingConfirmations[] = [
            'type' => 'circuit_manager',
            'description' => $lang === 'it'
                ? 'Conferma del responsabile del circuito (' . htmlspecialchars($match['circuit_owner_email']) . ')'
                : 'Circuit manager confirmation (' . htmlspecialchars($match['circuit_owner_email']) . ')'
        ];
    }
}

$isApplied = $match['rating_applied'] == 1;
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
            <p style="margin: 0 0 1rem 0;"><?= $lang === 'it' ? 'Questa partita non è ancora stata validata. Sono necessarie le seguenti approvazioni:' : 'This match has not been validated yet. The following approvals are required:' ?></p>
            <ul class="pending-approvals-list">
                <?php foreach ($pendingConfirmations as $pending): ?>
                <li>
                    <?= $pending['description'] ?>
                    <form method="POST" style="display: inline; margin-left: 1rem;">
                        <input type="hidden" name="action" value="resend_match">
                        <input type="hidden" name="role" value="<?= $pending['type'] ?>">
                        <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: inherit;">
                            <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                        </button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php elseif ($isApplied): ?>
    <div class="alert alert-success">
        <p style="margin: 0;">✓ <?= $lang === 'it' ? 'Partita confermata e rating aggiornati' : 'Match confirmed and ratings updated' ?></p>
    </div>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1><?= $lang === 'it' ? 'Dettaglio Partita' : 'Match Details' ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= __('form_circuit') ?>: <a href="?page=circuit&id=<?= $match['circuit_id'] ?>"><?= htmlspecialchars($match['circuit_name']) ?></a></span>
                <span><?= date('d/m/Y H:i', strtotime($match['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <div class="card" style="max-width: 600px; margin: 2rem auto;">
        <div class="match-display">
            <!-- White -->
            <div class="match-white">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">♔</div>
                <h3 style="margin: 0.5rem 0;">
                    <a href="?page=player&id=<?= $match['white_id'] ?>">
                        <?= htmlspecialchars($match['white_first'] . ' ' . $match['white_last']) ?>
                    </a>
                </h3>
                <p style="color: var(--text-secondary); margin: 0;">
                    <?= htmlspecialchars($match['white_club']) ?>
                </p>
                <?php if ($match['white_confirmed']): ?>
                <div style="color: var(--success); margin-top: 0.5rem;">✓ <?= $lang === 'it' ? 'Confermato' : 'Confirmed' ?></div>
                <?php else: ?>
                <div style="color: var(--warning); margin-top: 0.5rem;">⏳ <?= $lang === 'it' ? 'In attesa' : 'Pending' ?></div>
                <?php endif; ?>
            </div>

            <!-- Result -->
            <div class="match-vs" style="font-size: 2rem; font-weight: bold; min-width: 80px;">
                <?= htmlspecialchars(str_replace('-', ' - ', $match['result'])) ?>
            </div>

            <!-- Black -->
            <div class="match-black">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">♚</div>
                <h3 style="margin: 0.5rem 0;">
                    <a href="?page=player&id=<?= $match['black_id'] ?>">
                        <?= htmlspecialchars($match['black_first'] . ' ' . $match['black_last']) ?>
                    </a>
                </h3>
                <p style="color: var(--text-secondary); margin: 0;">
                    <?= htmlspecialchars($match['black_club']) ?>
                </p>
                <?php if ($match['black_confirmed']): ?>
                <div style="color: var(--success); margin-top: 0.5rem;">✓ <?= $lang === 'it' ? 'Confermato' : 'Confirmed' ?></div>
                <?php else: ?>
                <div style="color: var(--warning); margin-top: 0.5rem;">⏳ <?= $lang === 'it' ? 'In attesa' : 'Pending' ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ELO Stakes -->
        <?php if ($match['white_rating_before'] && $match['white_rating_change'] !== null): ?>
        <div style="text-align: center; margin-top: 1.5rem; padding: 1rem; background: var(--bg-secondary); border-radius: 8px;">
            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                <?= $lang === 'it' ? 'Posta ELO in gioco' : 'ELO at stake' ?>
            </div>
            <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                <div>
                    <span style="color: var(--text-secondary);">♔</span>
                    <strong style="color: <?= $match['white_rating_change'] > 0 ? 'var(--success)' : ($match['white_rating_change'] < 0 ? 'var(--error)' : 'var(--text-secondary)') ?>">
                        <?= $match['white_rating_change'] > 0 ? '+' : '' ?><?= $match['white_rating_change'] ?>
                    </strong>
                </div>
                <div>
                    <span style="color: var(--text-secondary);">♚</span>
                    <strong style="color: <?= $match['black_rating_change'] > 0 ? 'var(--success)' : ($match['black_rating_change'] < 0 ? 'var(--error)' : 'var(--text-secondary)') ?>">
                        <?= $match['black_rating_change'] > 0 ? '+' : '' ?><?= $match['black_rating_change'] ?>
                    </strong>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--border);">

        <div style="text-align: center;">
            <h4><?php
                if ($sameClub) {
                    echo $lang === 'it' ? 'Validazione Presidente' : 'President Validation';
                } else {
                    echo $lang === 'it' ? 'Validazione Responsabile Circuito' : 'Circuit Manager Validation';
                }
            ?></h4>
            <?php if ($match['president_confirmed']): ?>
            <div style="color: var(--success); margin-top: 0.5rem;">✓ <?= $lang === 'it' ? 'Confermato' : 'Confirmed' ?></div>
            <?php else: ?>
            <div style="color: var(--warning); margin-top: 0.5rem;">⏳ <?= $lang === 'it' ? 'In attesa' : 'Pending' ?></div>
            <?php endif; ?>
        </div>
    </div>

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
                <input type="hidden" name="entity_type" value="match">
                <input type="hidden" name="entity_id" value="<?= $matchId ?>">
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
