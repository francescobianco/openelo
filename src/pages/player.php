<?php
/**
 * OpenElo - Player Profile Page
 */

require_once SRC_PATH . '/mail.php';
require_once SRC_PATH . '/utils.php';

$db = Database::get();

$playerId = (int)($_GET['id'] ?? 0);
$flash = getFlash();
$message = $flash['message'] ?? null;
$messageType = $flash['type'] ?? null;

// Handle resend requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Check rate limit for reminder actions
    $isReminderAction = in_array($_POST['action'], ['resend_player', 'resend_president']);
    $rateLimitError = $isReminderAction ? checkReminderRateLimit() : null;

    if ($rateLimitError) {
        $message = $lang === 'it'
            ? 'Stai mandando troppi solleciti! Potrai mandare il prossimo tra ' . $rateLimitError['minutes'] . ' minuti.'
            : 'You are sending too many reminders! You can send the next one in ' . $rateLimitError['minutes'] . ' minutes.';
        $messageType = 'error';
    } elseif ($_POST['action'] === 'resend_player') {
        $stmt = $db->prepare("SELECT p.*, c.name as club_name
            FROM players p
            JOIN clubs c ON c.id = p.club_id
            WHERE p.id = ?");
        $stmt->execute([$playerId]);
        $playerData = $stmt->fetch();

        if ($playerData && !$playerData['player_confirmed']) {
            $playerName = $playerData['first_name'] . ' ' . $playerData['last_name'];
            $token = createConfirmation('player_self', $playerId, $playerData['email']);
            sendPlayerSelfConfirmation($playerData['email'], $playerName, $playerData['club_name'], $token);
            logReminder();
            setFlash('success', $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($_POST['action'] === 'resend_president') {
        $stmt = $db->prepare("SELECT p.*, c.name as club_name, c.president_email
            FROM players p
            JOIN clubs c ON c.id = p.club_id
            WHERE p.id = ?");
        $stmt->execute([$playerId]);
        $playerData = $stmt->fetch();

        if ($playerData && !$playerData['president_confirmed']) {
            $playerName = $playerData['first_name'] . ' ' . $playerData['last_name'];
            $token = createConfirmation('player_president', $playerId, $playerData['president_email']);
            sendPlayerPresidentConfirmation($playerData['president_email'], $playerName, $playerData['club_name'], $token);
            logReminder();
            setFlash('success', $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif ($_POST['action'] === 'sono_io') {
        $stmt = $db->prepare("SELECT p.*, c.name as club_name FROM players p JOIN clubs c ON c.id = p.club_id WHERE p.id = ?");
        $stmt->execute([$playerId]);
        $playerData = $stmt->fetch();
        if ($playerData && $playerData['confirmed']) {
            $token = createConfirmation('club_access_player', $playerId, $playerData['email']);
            sendClubAccessConfirmation($playerData['email'], $playerData['club_name'], 'player', $token);
            setFlash('success', $lang === 'it'
                ? 'Email inviata! Controlla la tua casella e clicca il link per confermare la tua identità.'
                : 'Email sent! Check your inbox and click the link to confirm your identity.');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

// Get player with club (allow access even if not confirmed)
$stmt = $db->prepare("
    SELECT p.*, c.name as club_name, c.id as club_id, c.president_email, c.protected_mode as club_protected
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

$canViewThisPlayer = !$player['club_protected'] || hasClubAccess((int)$player['club_id']);

// Check pending confirmations
$pendingConfirmations = [];
if (!$player['player_confirmed']) {
    $pendingConfirmations[] = [
        'type' => 'player',
        'description' => $lang === 'it'
            ? 'Conferma del giocatore'
            : 'Player confirmation'
    ];
}
if (!$player['president_confirmed']) {
    $pendingConfirmations[] = [
        'type' => 'president',
        'description' => $lang === 'it'
            ? 'Conferma del presidente del circolo'
            : 'Club president confirmation'
    ];
}

// Handle transfer request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'transfer') {
    try {
        $newClubId = (int)($_POST['club_id'] ?? 0);

        if (!$newClubId) {
            throw new Exception(__('error_required'));
        }

        if ($newClubId === $player['club_id']) {
            throw new Exception($lang === 'it' ? 'Già in questo circolo' : 'Already in this club');
        }

        // Get new club
        $stmt = $db->prepare("
            SELECT c.*,
                (SELECT COUNT(*) FROM circuit_clubs cc WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1) as active_circuits
            FROM clubs c
            WHERE c.id = ? AND c.president_confirmed = 1
        ");
        $stmt->execute([$newClubId]);
        $newClub = $stmt->fetch();

        if (!$newClub || $newClub['active_circuits'] == 0) {
            throw new Exception(__('error_not_found'));
        }

        // Check for pending transfer
        $stmt = $db->prepare("SELECT * FROM club_transfers WHERE player_id = ? AND completed = 0");
        $stmt->execute([$playerId]);
        if ($stmt->fetch()) {
            throw new Exception($lang === 'it' ? 'Trasferimento già in corso' : 'Transfer already pending');
        }

        // Create transfer request
        $stmt = $db->prepare("INSERT INTO club_transfers (player_id, from_club_id, to_club_id) VALUES (?, ?, ?)");
        $stmt->execute([$playerId, $player['club_id'], $newClubId]);
        $transferId = $db->lastInsertId();

        $playerName = $player['first_name'] . ' ' . $player['last_name'];

        // Send email to player
        $tokenPlayer = createConfirmation('transfer_player', $transferId, $player['email']);
        sendTransferPlayerConfirmation($player['email'], $playerName, $newClub['name'], $tokenPlayer);

        // Send email to new president
        $tokenPresident = createConfirmation('transfer_president', $transferId, $newClub['president_email']);
        sendTransferPresidentConfirmation($newClub['president_email'], $playerName, $newClub['name'], $tokenPresident);

        setFlash('success', __('player_transfer_requested'));
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Handle manual rating request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_manual_rating') {
    try {
        $circuitId = (int)($_POST['circuit_id'] ?? 0);
        $requestedRating = (int)($_POST['requested_rating'] ?? 0);
        $requestedCategory = trim($_POST['requested_category'] ?? '');

        if (!$circuitId || !$requestedRating || empty($requestedCategory)) {
            throw new Exception(__('error_required'));
        }

        if ($requestedRating < 0 || $requestedRating > 3000) {
            throw new Exception($lang === 'it' ? 'Rating non valido' : 'Invalid rating');
        }

        // Get circuit and verify player has rating in it
        $stmt = $db->prepare("
            SELECT c.*, r.rating as current_rating
            FROM circuits c
            JOIN ratings r ON r.circuit_id = c.id
            WHERE c.id = ? AND r.player_id = ? AND c.confirmed = 1
        ");
        $stmt->execute([$circuitId, $playerId]);
        $circuit = $stmt->fetch();

        if (!$circuit) {
            throw new Exception($lang === 'it' ? 'Circuito non trovato o giocatore non registrato' : 'Circuit not found or player not registered');
        }

        // Create manual rating request
        $stmt = $db->prepare("
            INSERT INTO manual_rating_requests (player_id, circuit_id, requested_rating, requested_category)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$playerId, $circuitId, $requestedRating, $requestedCategory]);
        $requestId = $db->lastInsertId();

        $playerName = $player['first_name'] . ' ' . $player['last_name'];

        // Send confirmation emails to player, president, and circuit manager
        $tokenPlayer = createConfirmation('manual_rating_player', $requestId, $player['email']);
        sendManualRatingConfirmation($player['email'], 'player', $playerName, $circuit['name'], $requestedRating, $requestedCategory, $tokenPlayer);

        $tokenPresident = createConfirmation('manual_rating_president', $requestId, $player['president_email']);
        sendManualRatingConfirmation($player['president_email'], 'president', $playerName, $circuit['name'], $requestedRating, $requestedCategory, $tokenPresident);

        $tokenCircuit = createConfirmation('manual_rating_circuit', $requestId, $circuit['owner_email']);
        sendManualRatingConfirmation($circuit['owner_email'], 'circuit', $playerName, $circuit['name'], $requestedRating, $requestedCategory, $tokenCircuit);

        setFlash('success', $lang === 'it'
            ? 'Richiesta inviata! Tutti i responsabili riceveranno un\'email di conferma.'
            : 'Request sent! All responsible parties will receive a confirmation email.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get player's ratings in all circuits
$stmt = $db->prepare("
    SELECT ci.name as circuit_name, ci.id as circuit_id, r.rating, r.games_played
    FROM ratings r
    JOIN circuits ci ON ci.id = r.circuit_id
    WHERE r.player_id = ? AND ci.deleted_at IS NULL
    ORDER BY r.rating DESC
");
$stmt->execute([$playerId]);
$ratings = $stmt->fetchAll();

// Get available clubs for transfer (active clubs, not current)
$stmt = $db->prepare("
    SELECT c.* FROM clubs c
    WHERE c.president_confirmed = 1
    AND c.id != ?
    AND c.deleted_at IS NULL
    AND EXISTS (
        SELECT 1 FROM circuit_clubs cc
        WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
    )
    ORDER BY c.name
");
$stmt->execute([$player['club_id']]);
$availableClubs = $stmt->fetchAll();

// Get pending matches (not yet fully confirmed)
$stmt = $db->prepare("
    SELECT m.*, ci.name as circuit_name,
        pw.first_name as white_first, pw.last_name as white_last,
        pb.first_name as black_first, pb.last_name as black_last
    FROM matches m
    JOIN circuits ci ON ci.id = m.circuit_id
    JOIN players pw ON pw.id = m.white_player_id
    JOIN players pb ON pb.id = m.black_player_id
    WHERE (m.white_player_id = ? OR m.black_player_id = ?) AND m.rating_applied = 0 AND m.deleted_at IS NULL
    ORDER BY m.created_at DESC
");
$stmt->execute([$playerId, $playerId]);
$pendingMatches = $stmt->fetchAll();

// Get last 10 approved matches
$stmt = $db->prepare("
    SELECT m.*, ci.name as circuit_name,
        pw.first_name as white_first, pw.last_name as white_last, pw.id as white_id,
        pb.first_name as black_first, pb.last_name as black_last, pb.id as black_id
    FROM matches m
    JOIN circuits ci ON ci.id = m.circuit_id
    JOIN players pw ON pw.id = m.white_player_id
    JOIN players pb ON pb.id = m.black_player_id
    WHERE (m.white_player_id = ? OR m.black_player_id = ?) AND m.rating_applied = 1
    AND m.deleted_at IS NULL
    ORDER BY m.created_at DESC
    LIMIT 10
");
$stmt->execute([$playerId, $playerId]);
$recentMatches = $stmt->fetchAll();

// Get pending transfer
$stmt = $db->prepare("
    SELECT ct.*, c.name as to_club_name
    FROM club_transfers ct
    JOIN clubs c ON c.id = ct.to_club_id
    WHERE ct.player_id = ? AND ct.completed = 0
    ORDER BY ct.created_at DESC
    LIMIT 1
");
$stmt->execute([$playerId]);
$pendingTransfer = $stmt->fetch();

// Get pending manual rating requests
$stmt = $db->prepare("
    SELECT mr.*, ci.name as circuit_name
    FROM manual_rating_requests mr
    JOIN circuits ci ON ci.id = mr.circuit_id
    WHERE mr.player_id = ? AND mr.applied = 0
    ORDER BY mr.created_at DESC
");
$stmt->execute([$playerId]);
$pendingManualRatings = $stmt->fetchAll();

// Handle resend for transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $rateLimitError = in_array($_POST['action'], ['resend_transfer_player', 'resend_transfer_president', 'resend_manual_rating_player', 'resend_manual_rating_president', 'resend_manual_rating_circuit'])
        ? checkReminderRateLimit() : null;

    if ($rateLimitError) {
        $message = $lang === 'it'
            ? 'Stai mandando troppi solleciti! Potrai mandare il prossimo tra ' . $rateLimitError['minutes'] . ' minuti.'
            : 'You are sending too many reminders! You can send the next one in ' . $rateLimitError['minutes'] . ' minutes.';
        $messageType = 'error';
    } elseif ($_POST['action'] === 'resend_transfer_player' && $pendingTransfer && !$pendingTransfer['player_confirmed']) {
        $playerName = $player['first_name'] . ' ' . $player['last_name'];
        $token = createConfirmation('transfer_player', $pendingTransfer['id'], $player['email']);
        sendTransferPlayerConfirmation($player['email'], $playerName, $pendingTransfer['to_club_name'], $token);
        logReminder();
        setFlash('success', $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } elseif ($_POST['action'] === 'resend_transfer_president' && $pendingTransfer && !$pendingTransfer['president_confirmed']) {
        $playerName = $player['first_name'] . ' ' . $player['last_name'];
        $stmt = $db->prepare("SELECT president_email FROM clubs WHERE id = ?");
        $stmt->execute([$pendingTransfer['to_club_id']]);
        $toClub = $stmt->fetch();
        $token = createConfirmation('transfer_president', $pendingTransfer['id'], $toClub['president_email']);
        sendTransferPresidentConfirmation($toClub['president_email'], $playerName, $pendingTransfer['to_club_name'], $token);
        logReminder();
        setFlash('success', $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } elseif (str_starts_with($_POST['action'], 'resend_manual_rating_') && isset($_POST['request_id'])) {
        $reqId = (int)$_POST['request_id'];
        $stmt = $db->prepare("SELECT mr.*, ci.name as circuit_name, ci.owner_email as circuit_owner_email FROM manual_rating_requests mr JOIN circuits ci ON ci.id = mr.circuit_id WHERE mr.id = ? AND mr.player_id = ? AND mr.applied = 0");
        $stmt->execute([$reqId, $playerId]);
        $mrReq = $stmt->fetch();
        if ($mrReq) {
            $playerName = $player['first_name'] . ' ' . $player['last_name'];
            $role = str_replace('resend_manual_rating_', '', $_POST['action']);
            if ($role === 'player' && !$mrReq['player_confirmed']) {
                $token = createConfirmation('manual_rating_player', $reqId, $player['email']);
                sendManualRatingConfirmation($player['email'], 'player', $playerName, $mrReq['circuit_name'], $mrReq['requested_rating'], $mrReq['requested_category'], $token);
                logReminder();
                setFlash('success', $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!');
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } elseif ($role === 'president' && !$mrReq['president_confirmed']) {
                $token = createConfirmation('manual_rating_president', $reqId, $player['president_email']);
                sendManualRatingConfirmation($player['president_email'], 'president', $playerName, $mrReq['circuit_name'], $mrReq['requested_rating'], $mrReq['requested_category'], $token);
                logReminder();
                setFlash('success', $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!');
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } elseif ($role === 'circuit' && !$mrReq['circuit_confirmed']) {
                $token = createConfirmation('manual_rating_circuit', $reqId, $mrReq['circuit_owner_email']);
                sendManualRatingConfirmation($mrReq['circuit_owner_email'], 'circuit', $playerName, $mrReq['circuit_name'], $mrReq['requested_rating'], $mrReq['requested_category'], $token);
                logReminder();
                setFlash('success', $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!');
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }
}

$tab = $_GET['tab'] ?? 'ratings';
if ($tab === 'main') $tab = 'ratings'; // backward compat
if (!in_array($tab, ['ratings', 'matches', 'management'])) $tab = 'ratings';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><?= $canViewThisPlayer ? htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) : maskName($player['first_name'] . ' ' . $player['last_name']) ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= __('form_club') ?>: <a href="?page=club&id=<?= $player['club_id'] ?>"><?= htmlspecialchars($player['club_name']) ?></a></span>
                <span><?= $lang === 'it' ? 'Categoria' : 'Category' ?>: <strong><?= htmlspecialchars($player['category'] ?? 'NC') ?></strong></span>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <button class="btn-star" data-fav-type="player" data-fav-id="<?= $playerId ?>" onclick="toggleFavorite('player', <?= $playerId ?>)" title="<?= $lang === 'it' ? 'Aggiungi ai preferiti' : 'Add to favorites' ?>">☆</button>
            <button class="btn-share" onclick="shareCurrentPage()" title="<?= $lang === 'it' ? 'Condividi' : 'Share' ?>"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg></button>
            <?php if ($player['confirmed'] && !hasClubAccess((int)$player['club_id'])): ?>
            <form method="POST">
                <input type="hidden" name="action" value="sono_io">
                <button type="submit" class="btn btn-secondary" style="font-size: 0.9rem;">
                    &#128100; <?= $lang === 'it' ? 'Sono io' : 'That\'s me' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['new'])): ?>
    <div class="alert" style="background: linear-gradient(135deg, rgba(67,97,238,0.15), rgba(67,97,238,0.05)); border: 1px solid rgba(67,97,238,0.4); border-radius: 10px; padding: 1.25rem 1.5rem;">
        <p style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 700; color: var(--text-primary);">
            <?= $lang === 'it' ? '👋 Benvenuto in OpenELO!' : '👋 Welcome to OpenELO!' ?>
        </p>
        <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6;">
            <?= $lang === 'it'
                ? 'La tua iscrizione è stata ricevuta, ma <strong style="color: var(--text-primary);">l\'attivazione non è ancora completa</strong>. Controlla la tua email: riceverai a breve un link di conferma. Solo dopo aver confermato potrai comparire nelle classifiche e partecipare alle partite ufficiali.'
                : 'Your registration has been received, but <strong style="color: var(--text-primary);">activation is not yet complete</strong>. Check your inbox: you\'ll receive a confirmation link shortly. You\'ll only appear in rankings and be able to play official matches after confirming.' ?>
        </p>
    </div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($pendingConfirmations)): ?>
    <div class="alert alert-warning">
        <p style="margin: 0 0 0.75rem 0;">
            <strong><?= $lang === 'it' ? 'Approvazioni in attesa' : 'Pending Approvals' ?></strong>
            <span style="font-weight: 400; color: var(--text-secondary); font-size: 0.9rem;"> — <?= $lang === 'it' ? 'questo giocatore non è ancora attivo' : 'this player is not yet active' ?></span>
        </p>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($pendingConfirmations as $i => $pending):
                $isLast = $i === array_key_last($pendingConfirmations);
            ?>
            <li style="padding: 0.5rem 0 <?= $isLast ? '0' : '0.5rem' ?>; border-top: 1px solid var(--border); display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.25rem 1rem;">
                <span style="font-size: 0.9rem;"><?= $pending['description'] ?></span>
                <?php if ($pending['type'] === 'player'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_player">
                    <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: 0.85rem; white-space: nowrap;">
                        <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                    </button>
                </form>
                <?php elseif ($pending['type'] === 'president'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_president">
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

    <?php if ($pendingTransfer): ?>
    <div class="alert alert-warning">
        <p style="margin: 0 0 0.75rem 0;">
            <strong><?= $lang === 'it' ? 'Trasferimento in attesa' : 'Pending Transfer' ?></strong>
            <span style="font-weight: 400; color: var(--text-secondary); font-size: 0.9rem;"> — <?= $lang === 'it'
                ? 'verso ' . htmlspecialchars($pendingTransfer['to_club_name'])
                : 'to ' . htmlspecialchars($pendingTransfer['to_club_name']) ?></span>
        </p>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="padding: 0.5rem 0; border-top: 1px solid var(--border); display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.25rem 1rem;">
                <span style="font-size: 0.9rem;"><?= $lang === 'it' ? 'Conferma del giocatore' : 'Player confirmation' ?></span>
                <?php if ($pendingTransfer['player_confirmed']): ?>
                <span style="color: var(--success); font-size: 0.85rem;">&#10003;</span>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_transfer_player">
                    <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: 0.85rem; white-space: nowrap;">
                        <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                    </button>
                </form>
                <?php endif; ?>
            </li>
            <li style="padding: 0.5rem 0 0; border-top: 1px solid var(--border); display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.25rem 1rem;">
                <span style="font-size: 0.9rem;"><?= $lang === 'it' ? 'Conferma del presidente del nuovo circolo' : 'New club president confirmation' ?></span>
                <?php if ($pendingTransfer['president_confirmed']): ?>
                <span style="color: var(--success); font-size: 0.85rem;">&#10003;</span>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_transfer_president">
                    <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: 0.85rem; white-space: nowrap;">
                        <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                    </button>
                </form>
                <?php endif; ?>
            </li>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($pendingManualRatings)): ?>
    <?php foreach ($pendingManualRatings as $mr): ?>
    <div class="alert alert-warning">
        <p style="margin: 0 0 0.75rem 0;">
            <strong><?= $lang === 'it' ? 'Variazione manuale in attesa' : 'Pending Manual Rating Change' ?></strong>
            <span style="font-weight: 400; color: var(--text-secondary); font-size: 0.9rem;"> — <?= $lang === 'it'
                ? $mr['requested_rating'] . ' (cat. ' . htmlspecialchars($mr['requested_category']) . ') · ' . htmlspecialchars($mr['circuit_name'])
                : $mr['requested_rating'] . ' (cat. ' . htmlspecialchars($mr['requested_category']) . ') · ' . htmlspecialchars($mr['circuit_name']) ?></span>
        </p>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="padding: 0.5rem 0; border-top: 1px solid var(--border); display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.25rem 1rem;">
                <span style="font-size: 0.9rem;"><?= $lang === 'it' ? 'Conferma del giocatore' : 'Player confirmation' ?></span>
                <?php if ($mr['player_confirmed']): ?>
                <span style="color: var(--success); font-size: 0.85rem;">&#10003;</span>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_manual_rating_player">
                    <input type="hidden" name="request_id" value="<?= $mr['id'] ?>">
                    <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: 0.85rem; white-space: nowrap;">
                        <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                    </button>
                </form>
                <?php endif; ?>
            </li>
            <li style="padding: 0.5rem 0; border-top: 1px solid var(--border); display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.25rem 1rem;">
                <span style="font-size: 0.9rem;"><?= $lang === 'it' ? 'Conferma del presidente del circolo' : 'Club president confirmation' ?></span>
                <?php if ($mr['president_confirmed']): ?>
                <span style="color: var(--success); font-size: 0.85rem;">&#10003;</span>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_manual_rating_president">
                    <input type="hidden" name="request_id" value="<?= $mr['id'] ?>">
                    <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: 0.85rem; white-space: nowrap;">
                        <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                    </button>
                </form>
                <?php endif; ?>
            </li>
            <li style="padding: 0.5rem 0 0; border-top: 1px solid var(--border); display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.25rem 1rem;">
                <span style="font-size: 0.9rem;"><?= $lang === 'it' ? 'Conferma del responsabile del circuito' : 'Circuit manager confirmation' ?></span>
                <?php if ($mr['circuit_confirmed']): ?>
                <span style="color: var(--success); font-size: 0.85rem;">&#10003;</span>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_manual_rating_circuit">
                    <input type="hidden" name="request_id" value="<?= $mr['id'] ?>">
                    <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: 0.85rem; white-space: nowrap;">
                        <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                    </button>
                </form>
                <?php endif; ?>
            </li>
        </ul>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tabs">
        <a href="?page=player&id=<?= $playerId ?>&tab=ratings" class="tab <?= $tab === 'ratings' ? 'active' : '' ?>">
            <?= $lang === 'it' ? 'Rating' : 'Ratings' ?>
        </a>
        <a href="?page=player&id=<?= $playerId ?>&tab=matches" class="tab <?= $tab === 'matches' ? 'active' : '' ?>">
            <?= $lang === 'it' ? 'Partite' : 'Matches' ?>
        </a>
        <a href="?page=player&id=<?= $playerId ?>&tab=management" class="tab <?= $tab === 'management' ? 'active' : '' ?>">
            <?= $lang === 'it' ? 'Gestione' : 'Management' ?>
        </a>
    </div>

    <!-- Tab: Ratings -->
    <?php if ($tab === 'ratings'): ?>
    <div class="card">
        <?php if (empty($ratings)): ?>
        <div class="empty-state">
            <p><?= $lang === 'it' ? 'Nessun rating ancora.' : 'No ratings yet.' ?></p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table-nowrap">
                <thead>
                    <tr>
                        <th><?= __('form_circuit') ?></th>
                        <th style="text-align: center;"><?= __('rankings_rating') ?></th>
                        <th style="text-align: center;"><?= __('rankings_games') ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ratings as $r): ?>
                    <tr>
                        <td><a href="?page=circuit&id=<?= $r['circuit_id'] ?>"><?= htmlspecialchars($r['circuit_name']) ?></a></td>
                        <td class="rating" style="text-align: center;"><?= $r['rating'] ?></td>
                        <td style="text-align: center;"><?= $r['games_played'] ?></td>
                        <td>
                            <a href="?page=player_history&player=<?= $playerId ?>&circuit=<?= $r['circuit_id'] ?>" class="btn btn-sm btn-secondary">
                                <?= $lang === 'it' ? 'Storico' : 'History' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Matches -->
    <?php elseif ($tab === 'matches'): ?>

    <?php if (!empty($pendingMatches)): ?>
    <div class="card" style="margin-bottom: 2rem;">
        <h3 style="margin: 0 0 1rem 0; font-size: 1rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">
            ⏳ <?= $lang === 'it' ? 'In attesa di approvazione' : 'Pending Approval' ?>
        </h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= $lang === 'it' ? 'Bianco' : 'White' ?></th>
                        <th><?= $lang === 'it' ? 'Nero' : 'Black' ?></th>
                        <th style="text-align: center;"><?= $lang === 'it' ? 'Risultato' : 'Result' ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingMatches as $m): ?>
                    <tr>
                        <td <?= $m['white_player_id'] == $playerId ? 'style="font-weight: bold;"' : '' ?>>
                            <?= htmlspecialchars($m['white_first'] . ' ' . $m['white_last']) ?>
                        </td>
                        <td <?= $m['black_player_id'] == $playerId ? 'style="font-weight: bold;"' : '' ?>>
                            <?= htmlspecialchars($m['black_first'] . ' ' . $m['black_last']) ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;"><strong><?= htmlspecialchars(str_replace('-', ' - ', $m['result'])) ?></strong></td>
                        <td>
                            <a href="?page=match&id=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">
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
        <?php if (empty($recentMatches)): ?>
        <div class="empty-state">
            <p><?= $lang === 'it' ? 'Nessuna partita ancora.' : 'No matches yet.' ?></p>
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentMatches as $m): ?>
                    <tr>
                        <td <?= $m['white_id'] == $playerId ? 'style="font-weight: bold;"' : '' ?>>
                            <a href="?page=player&id=<?= $m['white_id'] ?>"><?= htmlspecialchars($m['white_first'] . ' ' . $m['white_last']) ?></a>
                        </td>
                        <td <?= $m['black_id'] == $playerId ? 'style="font-weight: bold;"' : '' ?>>
                            <a href="?page=player&id=<?= $m['black_id'] ?>"><?= htmlspecialchars($m['black_first'] . ' ' . $m['black_last']) ?></a>
                        </td>
                        <td style="text-align: center; white-space: nowrap;"><strong><?= htmlspecialchars(str_replace('-', ' - ', $m['result'])) ?></strong></td>
                        <td>
                            <a href="?page=match&id=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">
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

    <!-- Tab: Management -->
    <?php elseif ($tab === 'management'): ?>
    <div class="create-grid-2">
        <!-- Manual Rating Request -->
        <?php if (!empty($ratings)): ?>
        <div class="create-section">
            <h2><?= $lang === 'it' ? 'Richiedi Variazione Manuale' : 'Request Manual Rating Change' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="request_manual_rating">
                <div class="form-group">
                    <label for="circuit_id"><?= __('form_circuit') ?></label>
                    <select id="circuit_id" name="circuit_id" required>
                        <option value="">-- <?= __('form_select') ?> --</option>
                        <?php foreach ($ratings as $r): ?>
                        <option value="<?= $r['circuit_id'] ?>"><?= htmlspecialchars($r['circuit_name']) ?> (<?= $lang === 'it' ? 'Attuale' : 'Current' ?>: <?= $r['rating'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="requested_rating"><?= $lang === 'it' ? 'Nuovo Rating' : 'New Rating' ?></label>
                    <input type="number" id="requested_rating" name="requested_rating" min="0" max="3000" required>
                </div>
                <div class="form-group">
                    <label for="requested_category"><?= $lang === 'it' ? 'Categoria' : 'Category' ?></label>
                    <select id="requested_category" name="requested_category" required>
                        <option value="">-- <?= __('form_select') ?> --</option>
                        <?php foreach (getAvailableCategories() as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $player['category'] === $code ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 1rem 0;">
                    <?= $lang === 'it'
                        ? 'Categoria attuale: <strong>' . htmlspecialchars($player['category'] ?? 'NC') . '</strong>. Le categorie possono solo salire, non retrocedere.'
                        : 'Current category: <strong>' . htmlspecialchars($player['category'] ?? 'NC') . '</strong>. Categories can only go up, not down.' ?>
                </p>
                <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Change Club -->
        <?php if (!empty($availableClubs)): ?>
        <div class="create-section">
            <h2><?= __('player_change_club') ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="transfer">
                <div class="form-group">
                    <label for="club_id"><?= __('form_club') ?></label>
                    <select id="club_id" name="club_id" required>
                        <option value="">-- <?= __('form_select') ?> --</option>
                        <?php foreach ($availableClubs as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
            </form>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <!-- Deletion Request Link -->
    <div style="text-align: center; margin-top: 1.5rem;">
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
                <input type="hidden" name="entity_type" value="player">
                <input type="hidden" name="entity_id" value="<?= $playerId ?>">
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
