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
            pb.email as black_email, pb.first_name as black_first, pb.last_name as black_last,
            c.name as circuit_name,
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
        } elseif ($role === 'president' && !$matchData['president_confirmed']) {
            $email = $matchData['president_email'];
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
        pw.id as white_id, pw.first_name as white_first, pw.last_name as white_last, pw.email as white_email,
        pb.id as black_id, pb.first_name as black_first, pb.last_name as black_last, pb.email as black_email,
        c.name as circuit_name, c.id as circuit_id,
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
    $pendingConfirmations[] = [
        'type' => 'president',
        'description' => $lang === 'it'
            ? 'Conferma del presidente del circolo (' . htmlspecialchars($match['president_email']) . ')'
            : 'Club president confirmation (' . htmlspecialchars($match['president_email']) . ')'
    ];
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
    <div class="alert alert-warning">
        <h3 style="margin-top: 0;">⏳ <?= $lang === 'it' ? 'Approvazioni in attesa' : 'Pending Approvals' ?></h3>
        <p><?= $lang === 'it' ? 'Questa partita non è ancora stata validata. Sono necessarie le seguenti approvazioni:' : 'This match has not been validated yet. The following approvals are required:' ?></p>
        <ul style="margin: 1rem 0;">
            <?php foreach ($pendingConfirmations as $pending): ?>
            <li style="margin: 0.5rem 0;">
                <?= $pending['description'] ?>
                <form method="POST" style="display: inline; margin-left: 1rem;">
                    <input type="hidden" name="action" value="resend_match">
                    <input type="hidden" name="role" value="<?= $pending['type'] ?>">
                    <button type="submit" class="btn btn-sm"><?= $lang === 'it' ? 'Invia di nuovo richiesta' : 'Resend request' ?></button>
                </form>
            </li>
            <?php endforeach; ?>
        </ul>
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
        <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 2rem; align-items: center; text-align: center;">
            <!-- White -->
            <div>
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
            <div style="font-size: 2rem; font-weight: bold; min-width: 80px;">
                <?= htmlspecialchars($match['result']) ?>
            </div>

            <!-- Black -->
            <div>
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

        <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--border);">

        <div style="text-align: center;">
            <h4><?= $lang === 'it' ? 'Validazione Presidente' : 'President Validation' ?></h4>
            <?php if ($match['president_confirmed']): ?>
            <div style="color: var(--success); margin-top: 0.5rem;">✓ <?= $lang === 'it' ? 'Confermato' : 'Confirmed' ?></div>
            <?php else: ?>
            <div style="color: var(--warning); margin-top: 0.5rem;">⏳ <?= $lang === 'it' ? 'In attesa' : 'Pending' ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
