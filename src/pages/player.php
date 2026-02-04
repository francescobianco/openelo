<?php
/**
 * OpenElo - Player Profile Page
 */

require_once SRC_PATH . '/mail.php';
require_once SRC_PATH . '/utils.php';

$db = Database::get();

$playerId = (int)($_GET['id'] ?? 0);
$message = null;
$messageType = null;

// Handle resend requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'resend_player') {
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
            $message = $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!';
            $messageType = 'success';
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
            $message = $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!';
            $messageType = 'success';
        }
    }
}

// Get player with club (allow access even if not confirmed)
$stmt = $db->prepare("
    SELECT p.*, c.name as club_name, c.id as club_id, c.president_email
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

// Check pending confirmations
$pendingConfirmations = [];
if (!$player['player_confirmed']) {
    $pendingConfirmations[] = [
        'type' => 'player',
        'description' => $lang === 'it'
            ? 'Conferma del giocatore (' . htmlspecialchars($player['email']) . ')'
            : 'Player confirmation (' . htmlspecialchars($player['email']) . ')'
    ];
}
if (!$player['president_confirmed']) {
    $pendingConfirmations[] = [
        'type' => 'president',
        'description' => $lang === 'it'
            ? 'Conferma del presidente del circolo (' . htmlspecialchars($player['president_email']) . ')'
            : 'Club president confirmation (' . htmlspecialchars($player['president_email']) . ')'
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

        $message = __('player_transfer_requested');
        $messageType = 'success';

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

        $message = $lang === 'it'
            ? 'Richiesta inviata! Tutti i responsabili riceveranno un\'email di conferma.'
            : 'Request sent! All responsible parties will receive a confirmation email.';
        $messageType = 'success';

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
    WHERE r.player_id = ?
    ORDER BY r.rating DESC
");
$stmt->execute([$playerId]);
$ratings = $stmt->fetchAll();

// Get available clubs for transfer (active clubs, not current)
$stmt = $db->prepare("
    SELECT c.* FROM clubs c
    WHERE c.president_confirmed = 1
    AND c.id != ?
    AND EXISTS (
        SELECT 1 FROM circuit_clubs cc
        WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
    )
    ORDER BY c.name
");
$stmt->execute([$player['club_id']]);
$availableClubs = $stmt->fetchAll();

// Get recent matches with rating changes (only matches with stored rating data)
$stmt = $db->prepare("
    SELECT m.*, ci.name as circuit_name, ci.id as circuit_id,
        pw.first_name as white_first, pw.last_name as white_last,
        pb.first_name as black_first, pb.last_name as black_last,
        m.white_rating_before, m.black_rating_before,
        m.white_rating_change, m.black_rating_change,
        m.created_at
    FROM matches m
    JOIN circuits ci ON ci.id = m.circuit_id
    JOIN players pw ON pw.id = m.white_player_id
    JOIN players pb ON pb.id = m.black_player_id
    WHERE (m.white_player_id = ? OR m.black_player_id = ?)
        AND m.rating_applied = 1
        AND m.white_rating_before IS NOT NULL
        AND m.white_rating_before > 0
    ORDER BY m.created_at DESC
    LIMIT 20
");
$stmt->execute([$playerId, $playerId]);
$matches = $stmt->fetchAll();

// Get pending matches (not yet fully confirmed)
$stmt = $db->prepare("
    SELECT m.*, ci.name as circuit_name,
        pw.first_name as white_first, pw.last_name as white_last,
        pb.first_name as black_first, pb.last_name as black_last
    FROM matches m
    JOIN circuits ci ON ci.id = m.circuit_id
    JOIN players pw ON pw.id = m.white_player_id
    JOIN players pb ON pb.id = m.black_player_id
    WHERE (m.white_player_id = ? OR m.black_player_id = ?) AND m.rating_applied = 0
    ORDER BY m.created_at DESC
");
$stmt->execute([$playerId, $playerId]);
$pendingMatches = $stmt->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= __('form_club') ?>: <a href="?page=club&id=<?= $player['club_id'] ?>"><?= htmlspecialchars($player['club_name']) ?></a></span>
                <span><?= $lang === 'it' ? 'Categoria' : 'Category' ?>: <strong><?= htmlspecialchars($player['category'] ?: 'NC') ?></strong></span>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($pendingConfirmations)): ?>
    <div class="alert alert-warning" style="display: flex; gap: 1rem;">
        <div style="font-size: 2rem; line-height: 1; flex-shrink: 0;">⏳</div>
        <div style="flex: 1;">
            <h3 style="margin: 0 0 0.5rem 0;"><?= $lang === 'it' ? 'Approvazioni in attesa' : 'Pending Approvals' ?></h3>
            <p style="margin: 0 0 1rem 0;"><?= $lang === 'it' ? 'Questo giocatore non è ancora attivo. Sono necessarie le seguenti approvazioni:' : 'This player is not yet active. The following approvals are required:' ?></p>
            <ul class="pending-approvals-list">
                <?php foreach ($pendingConfirmations as $pending): ?>
                <li>
                    <?= $pending['description'] ?>
                    <?php if ($pending['type'] === 'player'): ?>
                    <form method="POST" style="display: inline; margin-left: 1rem;">
                        <input type="hidden" name="action" value="resend_player">
                        <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: inherit;">
                            <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                        </button>
                    </form>
                    <?php elseif ($pending['type'] === 'president'): ?>
                    <form method="POST" style="display: inline; margin-left: 1rem;">
                        <input type="hidden" name="action" value="resend_president">
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

    <div class="player-grid">
        <!-- Ratings -->
        <div class="create-section">
            <h2><?= __('rankings_title') ?></h2>
            <?php if (empty($ratings)): ?>
            <p style="color: var(--text-secondary);"><?= $lang === 'it' ? 'Nessun rating ancora.' : 'No ratings yet.' ?></p>
            <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><?= __('form_circuit') ?></th>
                            <th><?= __('rankings_rating') ?></th>
                            <th><?= __('rankings_games') ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ratings as $r): ?>
                        <tr>
                            <td><a href="?page=circuit&id=<?= $r['circuit_id'] ?>"><?= htmlspecialchars($r['circuit_name']) ?></a></td>
                            <td class="rating"><?= $r['rating'] ?></td>
                            <td><?= $r['games_played'] ?></td>
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

        <!-- Pending Matches -->
        <?php if (!empty($pendingMatches)): ?>
        <div class="create-section">
            <h2>⏳ <?= $lang === 'it' ? 'Partite in attesa di approvazione' : 'Matches Pending Approval' ?></h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><?= __('form_white') ?></th>
                            <th></th>
                            <th><?= __('form_black') ?></th>
                            <th><?= __('form_circuit') ?></th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingMatches as $m): ?>
                        <tr>
                            <td <?= $m['white_player_id'] == $playerId ? 'style="font-weight: bold;"' : '' ?>>
                                <?= htmlspecialchars($m['white_first'] . ' ' . $m['white_last']) ?>
                            </td>
                            <td><strong><?= $m['result'] ?></strong></td>
                            <td <?= $m['black_player_id'] == $playerId ? 'style="font-weight: bold;"' : '' ?>>
                                <?= htmlspecialchars($m['black_first'] . ' ' . $m['black_last']) ?>
                            </td>
                            <td><?= htmlspecialchars($m['circuit_name']) ?></td>
                            <td>
                                <a href="?page=match&id=<?= $m['id'] ?>" class="btn btn-sm">
                                    <?= $lang === 'it' ? 'Vedi dettagli' : 'View details' ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Manual Rating Request -->
        <?php if (!empty($ratings)): ?>
        <div class="create-section">
            <h2>⭐ <?= $lang === 'it' ? 'Richiedi Variazione Manuale' : 'Request Manual Rating Change' ?></h2>
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
                        ? 'Categoria attuale: <strong>' . htmlspecialchars($player['category']) . '</strong>. Le categorie possono solo salire, non retrocedere.'
                        : 'Current category: <strong>' . htmlspecialchars($player['category']) . '</strong>. Categories can only go up, not down.' ?>
                </p>
                <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Rating History -->
        <?php if (!empty($matches)): ?>
        <div class="create-section">
            <h2>📊 <?= $lang === 'it' ? 'Storico Variazioni Rating' : 'Rating History' ?></h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><?= $lang === 'it' ? 'Data' : 'Date' ?></th>
                            <th><?= $lang === 'it' ? 'Avversario' : 'Opponent' ?></th>
                            <th><?= __('form_circuit') ?></th>
                            <th><?= $lang === 'it' ? 'Risultato' : 'Result' ?></th>
                            <th><?= $lang === 'it' ? 'Rating' : 'Rating' ?></th>
                            <th><?= $lang === 'it' ? 'Variazione' : 'Change' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $m):
                            $isWhite = ($m['white_player_id'] == $playerId);
                            $opponentName = $isWhite
                                ? $m['black_first'] . ' ' . $m['black_last']
                                : $m['white_first'] . ' ' . $m['white_last'];
                            $ratingBefore = $isWhite ? $m['white_rating_before'] : $m['black_rating_before'];
                            $ratingChange = $isWhite ? $m['white_rating_change'] : $m['black_rating_change'];
                            $ratingAfter = $ratingBefore + $ratingChange;

                            // Determine result from player's perspective
                            if ($m['result'] === '0.5-0.5') {
                                $playerResult = '=';
                            } elseif (($isWhite && $m['result'] === '1-0') || (!$isWhite && $m['result'] === '0-1')) {
                                $playerResult = 'W';
                            } else {
                                $playerResult = 'L';
                            }
                        ?>
                        <tr>
                            <td style="font-size: 0.85rem; color: var(--text-secondary);">
                                <?= date('d/m/Y', strtotime($m['created_at'])) ?>
                            </td>
                            <td>
                                <?= $isWhite ? '♚' : '♔' ?>
                                <?= htmlspecialchars($opponentName) ?>
                            </td>
                            <td>
                                <a href="?page=circuit&id=<?= $m['circuit_id'] ?>">
                                    <?= htmlspecialchars($m['circuit_name']) ?>
                                </a>
                            </td>
                            <td style="text-align: center;">
                                <strong style="color: <?= $playerResult === 'W' ? 'var(--success)' : ($playerResult === 'L' ? 'var(--error)' : 'var(--text-secondary)') ?>">
                                    <?= $playerResult ?>
                                </strong>
                            </td>
                            <td style="text-align: center;">
                                <span style="color: var(--text-secondary); font-size: 0.9rem;"><?= $ratingBefore ?></span>
                                →
                                <strong><?= $ratingAfter ?></strong>
                            </td>
                            <td style="text-align: center; font-weight: bold; color: <?= $ratingChange > 0 ? 'var(--success)' : ($ratingChange < 0 ? 'var(--error)' : 'var(--text-secondary)') ?>">
                                <?= $ratingChange > 0 ? '+' : '' ?><?= $ratingChange ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
