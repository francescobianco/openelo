<?php
/**
 * OpenElo - Player Profile Page
 */

require_once SRC_PATH . '/mail.php';

$db = Database::get();

$playerId = (int)($_GET['id'] ?? 0);
$message = null;
$messageType = null;

// Get player with club
$stmt = $db->prepare("
    SELECT p.*, c.name as club_name, c.id as club_id
    FROM players p
    JOIN clubs c ON c.id = p.club_id
    WHERE p.id = ? AND p.confirmed = 1
");
$stmt->execute([$playerId]);
$player = $stmt->fetch();

if (!$player) {
    header('Location: ?page=circuits');
    exit;
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

// Get recent matches
$stmt = $db->prepare("
    SELECT m.*, ci.name as circuit_name,
        pw.first_name as white_first, pw.last_name as white_last,
        pb.first_name as black_first, pb.last_name as black_last
    FROM matches m
    JOIN circuits ci ON ci.id = m.circuit_id
    JOIN players pw ON pw.id = m.white_player_id
    JOIN players pb ON pb.id = m.black_player_id
    WHERE (m.white_player_id = ? OR m.black_player_id = ?) AND m.rating_applied = 1
    ORDER BY m.created_at DESC
    LIMIT 10
");
$stmt->execute([$playerId, $playerId]);
$matches = $stmt->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= __('form_club') ?>: <a href="?page=club&id=<?= $player['club_id'] ?>"><?= htmlspecialchars($player['club_name']) ?></a></span>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <div class="create-grid">
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ratings as $r): ?>
                        <tr>
                            <td><a href="?page=circuit&id=<?= $r['circuit_id'] ?>"><?= htmlspecialchars($r['circuit_name']) ?></a></td>
                            <td class="rating"><?= $r['rating'] ?></td>
                            <td><?= $r['games_played'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

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

        <!-- Recent Matches -->
        <?php if (!empty($matches)): ?>
        <div class="create-section">
            <h2><?= __('circuit_matches') ?></h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><?= __('form_white') ?></th>
                            <th></th>
                            <th><?= __('form_black') ?></th>
                            <th><?= __('form_circuit') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $m): ?>
                        <tr>
                            <td <?= $m['white_player_id'] == $playerId ? 'style="font-weight: bold;"' : '' ?>>
                                <?= htmlspecialchars($m['white_first'] . ' ' . $m['white_last']) ?>
                            </td>
                            <td><strong><?= $m['result'] ?></strong></td>
                            <td <?= $m['black_player_id'] == $playerId ? 'style="font-weight: bold;"' : '' ?>>
                                <?= htmlspecialchars($m['black_first'] . ' ' . $m['black_last']) ?>
                            </td>
                            <td><?= htmlspecialchars($m['circuit_name']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
