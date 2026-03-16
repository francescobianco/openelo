<?php
/**
 * OpenElo - Circuit Detail & Rankings
 */

require_once SRC_PATH . '/mail.php';
require_once SRC_PATH . '/utils.php';

$db = Database::get();

$circuitId = (int)($_GET['id'] ?? 0);
$flash = getFlash();
$message = $flash['message'] ?? null;
$messageType = $flash['type'] ?? null;

// Handle contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact_manager') {
    $senderEmail = trim($_POST['sender_email'] ?? '');
    $contactMessage = trim($_POST['message'] ?? '');

    if (empty($senderEmail) || empty($contactMessage)) {
        $message = __('error_required');
        $messageType = 'error';
    } elseif (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
        $message = __('error_email');
        $messageType = 'error';
    } else {
        // Get circuit
        $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ?");
        $stmt->execute([$circuitId]);
        $circuitData = $stmt->fetch();

        if ($circuitData) {
            // Send email to circuit manager
            $subject = ($lang === 'it' ? 'Messaggio dal circuito: ' : 'Message from circuit: ') . $circuitData['name'];
            $body = ($lang === 'it' ? 'Hai ricevuto un messaggio da: ' : 'You received a message from: ') . $senderEmail . "\n\n" . $contactMessage;

            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/plain; charset=UTF-8',
                'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
                'Reply-To: ' . $senderEmail
            ];

            if (DEV_MODE) {
                // Save to file in dev mode
                $emailDir = DATA_PATH . '/emails';
                if (!is_dir($emailDir)) mkdir($emailDir, 0755, true);
                $filename = date('Y-m-d_H-i-s') . '_contact_' . md5($circuitData['owner_email']) . '.txt';
                file_put_contents($emailDir . '/' . $filename, "To: {$circuitData['owner_email']}\nSubject: $subject\n\n$body");
            } else {
                mail($circuitData['owner_email'], $subject, $body, implode("\r\n", $headers));
            }

            setFlash('success', $lang === 'it' ? 'Messaggio inviato!' : 'Message sent!');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

// Handle change manager request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_manager_change') {
    $requesterEmail = trim($_POST['requester_email'] ?? '');
    $newManagerEmail = trim($_POST['new_manager_email'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if (empty($requesterEmail) || empty($newManagerEmail)) {
        $message = __('error_required');
        $messageType = 'error';
    } elseif (!filter_var($requesterEmail, FILTER_VALIDATE_EMAIL) || !filter_var($newManagerEmail, FILTER_VALIDATE_EMAIL)) {
        $message = __('error_email');
        $messageType = 'error';
    } else {
        // Get circuit
        $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ?");
        $stmt->execute([$circuitId]);
        $circuitData = $stmt->fetch();

        if ($circuitData) {
            // Send notification to current manager
            $subject = ($lang === 'it' ? 'Richiesta cambio responsabile: ' : 'Manager change request: ') . $circuitData['name'];
            $body = ($lang === 'it'
                ? "È stata richiesta una modifica del responsabile per il circuito \"{$circuitData['name']}\".\n\nRichiesta da: $requesterEmail\nNuovo responsabile proposto: $newManagerEmail\n\nMotivo:\n$reason"
                : "A manager change has been requested for circuit \"{$circuitData['name']}\".\n\nRequested by: $requesterEmail\nProposed new manager: $newManagerEmail\n\nReason:\n$reason");

            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/plain; charset=UTF-8',
                'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
                'Reply-To: ' . $requesterEmail
            ];

            if (DEV_MODE) {
                $emailDir = DATA_PATH . '/emails';
                if (!is_dir($emailDir)) mkdir($emailDir, 0755, true);
                $filename = date('Y-m-d_H-i-s') . '_manager_change_' . md5($circuitData['owner_email']) . '.txt';
                file_put_contents($emailDir . '/' . $filename, "To: {$circuitData['owner_email']}\nSubject: $subject\n\n$body");
            } else {
                mail($circuitData['owner_email'], $subject, $body, implode("\r\n", $headers));
            }

            setFlash('success', $lang === 'it' ? 'Richiesta inviata!' : 'Request sent!');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

// Handle formula change request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_formula_change') {
    $allowedFormulas = ['classic_elo', 'ladder_no_draw', 'knockout_no_draw', 'ladder_3up_sliding'];
    $formula = trim($_POST['formula'] ?? '');

    if (!in_array($formula, $allowedFormulas)) {
        $message = __('error_required');
        $messageType = 'error';
    } else {
        $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ?");
        $stmt->execute([$circuitId]);
        $circuitData = $stmt->fetch();

        if ($circuitData) {
            $formulaLabels = [
                'classic_elo'            => __('formula_classic_elo'),
                'ladder_no_draw'         => __('formula_ladder_no_draw'),
                'knockout_no_draw'       => __('formula_knockout_no_draw'),
                'ladder_3up_sliding' => __('formula_ladder_3up_sliding'),
            ];

            $stmt = $db->prepare("INSERT INTO circuit_formula_requests (circuit_id, formula) VALUES (?, ?)");
            $stmt->execute([$circuitId, $formula]);
            $requestId = $db->lastInsertId();

            $token = createConfirmation('circuit_formula_change', $requestId, $circuitData['owner_email']);
            sendCircuitFormulaConfirmation($circuitData['owner_email'], $circuitData['name'], $formulaLabels[$formula], $token);

            setFlash('success', $lang === 'it' ? 'Richiesta inviata! Il responsabile riceverà una email di conferma.' : 'Request sent! The manager will receive a confirmation email.');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

// Handle resend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend_circuit') {
    // Check rate limit
    $rateLimitError = checkReminderRateLimit();

    if ($rateLimitError) {
        $message = $lang === 'it'
            ? 'Stai mandando troppi solleciti! Potrai mandare il prossimo tra ' . $rateLimitError['minutes'] . ' minuti.'
            : 'You are sending too many reminders! You can send the next one in ' . $rateLimitError['minutes'] . ' minutes.';
        $messageType = 'error';
    } else {
        $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ?");
        $stmt->execute([$circuitId]);
        $circuitData = $stmt->fetch();

        if ($circuitData && !$circuitData['confirmed']) {
            $token = createConfirmation('circuit', $circuitId, $circuitData['owner_email']);
            sendCircuitConfirmation($circuitData['owner_email'], $circuitData['name'], $token);
            logReminder();
            setFlash('success', $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
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
            ? 'Conferma del responsabile circuito'
            : 'Circuit manager confirmation'
    ];
}

// Get clubs in circuit with player count
$stmt = $db->prepare("
    SELECT cl.*,
        COUNT(DISTINCT p.id) as player_count
    FROM clubs cl
    JOIN circuit_clubs cc ON cc.club_id = cl.id
    LEFT JOIN players p ON p.club_id = cl.id AND p.confirmed = 1
    WHERE cc.circuit_id = ? AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1 AND cl.deleted_at IS NULL
    GROUP BY cl.id
    ORDER BY cl.name
");
$stmt->execute([$circuitId]);
$clubs = $stmt->fetchAll();

// Get rankings
$circuitFormula = $circuit['formula'] ?? 'classic_elo';
$isLadderScorrimento = ($circuitFormula === 'ladder_3up_sliding');

if ($isLadderScorrimento) {
    // Include all confirmed players in the circuit even without a ratings row yet.
    // Players without a position are shown at the bottom (NULL sorts last via CASE).
    $stmt = $db->prepare("
        SELECT p.*,
               COALESCE(r.rating, 0)            AS rating,
               r.ladder_position,
               COALESCE(r.games_played, 0)       AS games_played,
               cl.name                           AS club_name,
               cl.id                             AS club_id,
               cl.protected_mode                 AS club_protected
        FROM players p
        JOIN clubs cl ON cl.id = p.club_id
        JOIN circuit_clubs cc ON cc.club_id = cl.id
        LEFT JOIN ratings r ON r.player_id = p.id AND r.circuit_id = ?
        WHERE cc.circuit_id = ? AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
          AND p.confirmed = 1 AND p.deleted_at IS NULL AND cl.deleted_at IS NULL
        ORDER BY
            CASE WHEN r.ladder_position IS NULL THEN 1 ELSE 0 END ASC,
            r.ladder_position ASC,
            p.last_name ASC,
            p.first_name ASC
    ");
    $stmt->execute([$circuitId, $circuitId]);
} else {
    $stmt = $db->prepare("
        SELECT p.*, r.rating, NULL as ladder_position, r.games_played, cl.name as club_name, cl.id as club_id, cl.protected_mode as club_protected
        FROM ratings r
        JOIN players p ON p.id = r.player_id
        JOIN clubs cl ON cl.id = p.club_id
        WHERE r.circuit_id = ? AND p.confirmed = 1
        ORDER BY r.rating DESC
    ");
    $stmt->execute([$circuitId]);
}
$rankings = $stmt->fetchAll();

// Get pending matches (not approved, not older than 30 days)
$thirtyDaysAgo = DB_TYPE === 'mysql'
    ? "DATE_SUB(NOW(), INTERVAL 30 DAY)"
    : "datetime('now', '-30 days')";
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
    WHERE m.circuit_id = ? AND m.rating_applied = 0 AND m.deleted_at IS NULL
    AND m.created_at >= $thirtyDaysAgo
    ORDER BY m.created_at ASC
");
$stmt->execute([$circuitId]);
$pendingMatches = $stmt->fetchAll();

// Get approved matches
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
    WHERE m.circuit_id = ? AND m.rating_applied = 1 AND m.deleted_at IS NULL
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

    <?php if ($circuit['deleted_at']): ?>
    <div class="alert alert-error" style="display: flex; gap: 1rem;">
        <div class="pending-icon">🗑</div>
        <div style="flex: 1;">
            <h3 style="margin: 0 0 0.5rem 0;"><?= $lang === 'it' ? 'Circuito in fase di eliminazione' : 'Circuit pending deletion' ?></h3>
            <p style="margin: 0;"><?= $lang === 'it'
                ? 'Questo circuito è stato contrassegnato per l\'eliminazione e sarà rimosso definitivamente dal sistema a breve.'
                : 'This circuit has been marked for deletion and will be permanently removed from the system soon.' ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($pendingConfirmations)): ?>
    <div class="alert alert-warning">
        <p style="margin: 0 0 0.75rem 0;">
            <strong><?= $lang === 'it' ? 'Approvazioni in attesa' : 'Pending Approvals' ?></strong>
            <span style="font-weight: 400; color: var(--text-secondary); font-size: 0.9rem;"> — <?= $lang === 'it' ? 'questo circuito non è ancora visibile pubblicamente' : 'this circuit is not yet publicly visible' ?></span>
        </p>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($pendingConfirmations as $i => $pending):
                $isLast = $i === array_key_last($pendingConfirmations);
            ?>
            <li style="padding: 0.5rem 0 <?= $isLast ? '0' : '0.5rem' ?>; border-top: 1px solid var(--border); display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.25rem 1rem;">
                <span style="font-size: 0.9rem;"><?= $pending['description'] ?></span>
                <?php if ($pending['type'] === 'circuit'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_circuit">
                    <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: 0.85rem; white-space: nowrap;">
                        <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                    </button>
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
                <?php
                    $stmtPc = $db->prepare("SELECT COUNT(DISTINCT p.id) FROM players p JOIN circuit_clubs cc ON cc.club_id = p.club_id WHERE cc.circuit_id = ? AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1 AND p.confirmed = 1 AND p.deleted_at IS NULL");
                    $stmtPc->execute([$circuitId]);
                    $totalPlayers = (int)$stmtPc->fetchColumn();
                ?>
                <span><?= $totalPlayers ?> <?= __('circuit_players') ?></span>
                <span><?= count($matches) ?> <?= __('circuit_matches') ?></span>
            </div>
        </div>
        <?php if ($circuit['confirmed']): ?>
            <?php if (empty($clubs)): ?>
            <a href="?page=create&circuit=<?= $circuitId ?>" class="btn btn-primary"><?= $lang === 'it' ? 'Registra Circolo' : 'Register Club' ?></a>
            <?php elseif ($totalPlayers < 2): ?>
            <a href="?page=create&club=<?= count($clubs) === 1 ? $clubs[0]['id'] : '' ?>" class="btn btn-primary"><?= $lang === 'it' ? 'Registra Giocatori' : 'Register Players' ?></a>
            <?php else: ?>
            <a href="?page=submit&circuit=<?= $circuitId ?>" class="btn btn-primary"><?= __('nav_submit_result') ?></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="tabs">
        <a href="?page=circuit&id=<?= $circuitId ?>&tab=rankings" class="tab <?= $tab === 'rankings' ? 'active' : '' ?>"><?= __('rankings_title') ?></a>
        <a href="?page=circuit&id=<?= $circuitId ?>&tab=clubs" class="tab <?= $tab === 'clubs' ? 'active' : '' ?>"><?= __('circuit_clubs') ?></a>
        <a href="?page=circuit&id=<?= $circuitId ?>&tab=matches" class="tab <?= $tab === 'matches' ? 'active' : '' ?>"><?= __('circuit_matches') ?></a>
        <a href="?page=circuit&id=<?= $circuitId ?>&tab=manager" class="tab <?= $tab === 'manager' ? 'active' : '' ?>"><?= $lang === 'it' ? 'Responsabile' : 'Manager' ?></a>
        <a href="?page=circuit&id=<?= $circuitId ?>&tab=settings" class="tab <?= $tab === 'settings' ? 'active' : '' ?>"><?= $lang === 'it' ? 'Impostazioni' : 'Settings' ?></a>
    </div>

    <?php if ($tab === 'rankings'): ?>
    <div class="card">
        <?php if (empty($rankings)): ?>
        <div class="empty-state">
            <p><?= $lang === 'it' ? 'Nessun giocatore ancora. Inizia a registrare partite!' : 'No players yet. Start submitting matches!' ?></p>
        </div>
        <?php else:
            $showClub   = count($clubs) > 1;
            $showRating = !$isLadderScorrimento;
        ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= __('rankings_position') ?></th>
                        <th><?= __('rankings_player') ?></th>
                        <?php if ($showClub): ?>
                        <th><?= __('rankings_club') ?></th>
                        <?php endif; ?>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'Categoria' : 'Category' ?></th>
                        <?php if ($showRating): ?>
                        <th style="text-align: center;"><?= __('rankings_rating') ?></th>
                        <?php endif; ?>
                        <th style="text-align: center;"><?= __('rankings_games') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankings as $i => $player):
                        $hasPosition = $isLadderScorrimento ? ($player['ladder_position'] !== null) : true;
                        $displayPos  = $isLadderScorrimento ? ($player['ladder_position'] ?? null) : ($i + 1);
                        $posClass    = ($displayPos !== null && $displayPos <= 3) ? 'rank-' . $displayPos : '';
                    ?>
                    <tr>
                        <td class="rank <?= $posClass ?>" style="padding-top: 0; padding-bottom: 0; line-height: 1;">
                            <?php if ($hasPosition): ?>
                            <strong style="font-size: 1.8em; line-height: 1;"><?= $displayPos ?>°</strong>
                            <?php else: ?>
                            <span style="font-size: 1.2em; color: var(--text-secondary);">—</span>
                            <?php endif; ?>
                        </td>
                        <?php $canViewPlayer = !$player['club_protected'] || hasClubAccess((int)$player['club_id']); ?>
                        <td><?php if ($canViewPlayer): ?><a href="?page=player&id=<?= $player['id'] ?>"><?= htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) ?></a><?php else: ?><a href="?page=player&id=<?= $player['id'] ?>" style="color: var(--text-secondary);"><?= maskName($player['first_name'] . ' ' . $player['last_name']) ?></a><?php endif; ?></td>
                        <?php if ($showClub): ?>
                        <td><a href="?page=club&id=<?= $player['club_id'] ?>"><?= htmlspecialchars($player['club_name']) ?></a></td>
                        <?php endif; ?>
                        <td style="text-align: center;"><strong><?= htmlspecialchars($player['category'] ?? 'NC') ?></strong></td>
                        <?php if ($showRating): ?>
                        <td class="rating" style="text-align: center;"><?= $player['rating'] ?></td>
                        <?php endif; ?>
                        <td style="text-align: center;"><?= $player['games_played'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif ($tab === 'clubs'): ?>
        <?php if (empty($clubs)): ?>
        <div class="empty-state">
            <p><?= $lang === 'it' ? 'Nessun circolo ancora.' : 'No clubs yet.' ?></p>
            <a href="?page=create&circuit=<?= $circuitId ?>" class="btn btn-primary">
                <?= $lang === 'it' ? 'Registra un Circolo' : 'Register a Club' ?>
            </a>
        </div>
        <?php else: ?>
    <div class="circuits-grid">
        <?php foreach ($clubs as $club): ?>
        <div class="circuit-card">
            <h3><a href="?page=club&id=<?= $club['id'] ?>"><?= htmlspecialchars($club['name']) ?></a></h3>
            <div class="circuit-meta">
                <span><?= $club['player_count'] ?> <?= $club['player_count'] == 1 ? ($lang === 'it' ? 'giocatore' : 'player') : ($lang === 'it' ? 'giocatori' : 'players') ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
        <?php endif; ?>
    <?php elseif ($tab === 'matches'): ?>

    <?php if (!empty($pendingMatches)): ?>
    <div class="card" style="margin-bottom: 2rem;">
        <h3 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">
            <?= $lang === 'it' ? 'In attesa di approvazione' : 'Pending Approval' ?>
        </h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= $lang === 'it' ? 'Bianco' : 'White' ?></th>
                        <th><?= $lang === 'it' ? 'Nero' : 'Black' ?></th>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'Risultato' : 'Result' ?></th>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'In attesa da' : 'Waiting since' ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingMatches as $match):
                        $diffSecs = time() - strtotime($match['created_at']);
                        $diffDays = floor($diffSecs / 86400);
                        $diffHours = floor($diffSecs / 3600);
                        if ($diffDays >= 1) {
                            $waitingLabel = $diffDays . ($lang === 'it' ? ' giorn' . ($diffDays == 1 ? 'o' : 'i') : ' day' . ($diffDays == 1 ? '' : 's'));
                        } else {
                            $waitingLabel = $diffHours . ($lang === 'it' ? ($diffHours == 1 ? ' ora' : ' ore') : ' hour' . ($diffHours == 1 ? '' : 's'));
                        }
                    ?>
                    <tr>
                        <td><a href="?page=player&id=<?= $match['white_id'] ?>"><?= htmlspecialchars($match['white_first'] . ' ' . $match['white_last']) ?></a></td>
                        <td><a href="?page=player&id=<?= $match['black_id'] ?>"><?= htmlspecialchars($match['black_first'] . ' ' . $match['black_last']) ?></a></td>
                        <td style="text-align: center; white-space: nowrap;"><strong><?= str_replace('-', ' - ', $match['result']) ?></strong></td>
                        <td style="text-align: center; color: var(--text-secondary); font-size: 0.9rem;"><?= $waitingLabel ?></td>
                        <td>
                            <a href="?page=match&id=<?= $match['id'] ?>" class="btn btn-sm btn-secondary">
                                <?= $lang === 'it' ? 'Vedi partita' : 'View match' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <?php if (empty($matches)): ?>
        <div class="empty-state">
            <p><?= $lang === 'it' ? 'Nessuna partita approvata ancora.' : 'No approved matches yet.' ?></p>
        </div>
        <?php else: ?>
        <h3 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">
            <?= $lang === 'it' ? 'Partite approvate' : 'Approved Matches' ?>
        </h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= $lang === 'it' ? 'Bianco' : 'White' ?></th>
                        <th><?= $lang === 'it' ? 'Nero' : 'Black' ?></th>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'Risultato' : 'Result' ?></th>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'Data' : 'Date' ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matches as $match): ?>
                    <tr>
                        <td><a href="?page=player&id=<?= $match['white_id'] ?>"><?= htmlspecialchars($match['white_first'] . ' ' . $match['white_last']) ?></a></td>
                        <td><a href="?page=player&id=<?= $match['black_id'] ?>"><?= htmlspecialchars($match['black_first'] . ' ' . $match['black_last']) ?></a></td>
                        <td style="text-align: center; white-space: nowrap;"><strong><?= str_replace('-', ' - ', $match['result']) ?></strong></td>
                        <td style="text-align: center; color: var(--text-secondary); font-size: 0.9rem;"><?= date('d/m/Y', strtotime($match['created_at'])) ?></td>
                        <td>
                            <a href="?page=match&id=<?= $match['id'] ?>" class="btn btn-sm btn-secondary">
                                <?= $lang === 'it' ? 'Vedi partita' : 'View match' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif ($tab === 'manager'): ?>
    <div class="create-grid">
        <!-- Contact Manager -->
        <div class="create-section">
            <h2><?= $lang === 'it' ? 'Contatta il Responsabile' : 'Contact Manager' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="contact_manager">
                <div class="form-group">
                    <label for="sender_email"><?= $lang === 'it' ? 'Tua Email' : 'Your Email' ?></label>
                    <input type="email" id="sender_email" name="sender_email" required>
                </div>
                <div class="form-group">
                    <label for="contact_message"><?= $lang === 'it' ? 'Messaggio' : 'Message' ?></label>
                    <textarea id="contact_message" name="message" rows="4" required style="width: 100%; padding: 0.8rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; color: var(--text-primary); font-family: inherit;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?= $lang === 'it' ? 'Invia Messaggio' : 'Send Message' ?></button>
            </form>
        </div>

        <!-- Request Manager Change -->
        <div class="create-section">
            <h2><?= $lang === 'it' ? 'Richiedi Cambio Responsabile' : 'Request Manager Change' ?></h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">
                <?= $lang === 'it'
                    ? 'Usa questo modulo per richiedere il trasferimento della gestione del circuito a un nuovo responsabile.'
                    : 'Use this form to request the transfer of circuit management to a new manager.' ?>
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="request_manager_change">
                <div class="form-group">
                    <label for="requester_email"><?= $lang === 'it' ? 'Tua Email' : 'Your Email' ?></label>
                    <input type="email" id="requester_email" name="requester_email" required>
                </div>
                <div class="form-group">
                    <label for="new_manager_email"><?= $lang === 'it' ? 'Email Nuovo Responsabile' : 'New Manager Email' ?></label>
                    <input type="email" id="new_manager_email" name="new_manager_email" required>
                </div>
                <div class="form-group">
                    <label for="change_reason"><?= $lang === 'it' ? 'Motivo della richiesta' : 'Reason for request' ?></label>
                    <textarea id="change_reason" name="reason" rows="3" style="width: 100%; padding: 0.8rem; background: var(--bg-secondary); border: 1px solid var(--border); border-radius: 8px; color: var(--text-primary); font-family: inherit;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?= $lang === 'it' ? 'Invia Richiesta' : 'Submit Request' ?></button>
            </form>
        </div>
    </div>
    <?php elseif ($tab === 'settings'): ?>
    <div class="create-grid">
        <!-- Formula Change -->
        <div class="create-section">
            <h2><?= $lang === 'it' ? 'Formula del Circuito' : 'Circuit Formula' ?></h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">
                <?php
                $formulaLabels = [
                    'classic_elo'            => __('formula_classic_elo'),
                    'ladder_no_draw'         => __('formula_ladder_no_draw'),
                    'knockout_no_draw'       => __('formula_knockout_no_draw'),
                    'ladder_3up_sliding' => __('formula_ladder_3up_sliding'),
                ];
                $currentFormula = $circuit['formula'] ?? 'classic_elo';
                ?>
                <?= $lang === 'it' ? 'Formula attuale:' : 'Current formula:' ?>
                <strong><?= htmlspecialchars($formulaLabels[$currentFormula] ?? $currentFormula) ?></strong>
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="request_formula_change">
                <div class="form-group">
                    <label for="formula"><?= $lang === 'it' ? 'Nuova Formula' : 'New Formula' ?></label>
                    <select id="formula" name="formula" required>
                        <option value="">-- <?= __('form_select') ?> --</option>
                        <?php
                        $sortedFormulas = $formulaLabels;
                        asort($sortedFormulas);
                        foreach ($sortedFormulas as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $currentFormula === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0.5rem 0 1rem;">
                    <?= $lang === 'it'
                        ? 'La richiesta verrà inviata al responsabile del circuito per approvazione via email.'
                        : 'The request will be sent to the circuit manager for approval via email.' ?>
                </p>
                <button type="submit" class="btn btn-primary"><?= $lang === 'it' ? 'Richiedi Cambio' : 'Request Change' ?></button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Deletion Request Link -->
    <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border);">
        <button onclick="openModal('deletion-modal')" class="deletion-link" style="background: none; border: none; cursor: pointer; font-size: 0.9rem; padding: 0;">
            <?= $lang === 'it' ? 'Segnala / Richiedi Eliminazione' : 'Report / Request Deletion' ?>
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
