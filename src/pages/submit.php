<?php
/**
 * OpenElo - Submit Match Result
 */

require_once SRC_PATH . '/mail.php';

$db = Database::get();

$message = null;
$messageType = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $circuitId = (int)($_POST['circuit_id'] ?? 0);
        $whiteId = (int)($_POST['white_player_id'] ?? 0);
        $blackId = (int)($_POST['black_player_id'] ?? 0);
        $result = $_POST['result'] ?? '';

        if (!$circuitId || !$whiteId || !$blackId || !$result) {
            throw new Exception(__('error_required'));
        }

        if ($whiteId === $blackId) {
            throw new Exception(__('error_same_player'));
        }

        // Validate result
        if (!in_array($result, ['1-0', '0-1', '0.5-0.5'])) {
            throw new Exception(__('error_invalid_result'));
        }

        // Get circuit
        $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ? AND confirmed = 1");
        $stmt->execute([$circuitId]);
        $circuitData = $stmt->fetch();

        if (!$circuitData) {
            throw new Exception(__('error_not_found'));
        }

        // Get players with their clubs
        $stmt = $db->prepare("
            SELECT p.*, c.name as club_name, c.president_email
            FROM players p
            JOIN clubs c ON c.id = p.club_id
            WHERE p.id = ? AND p.confirmed = 1
        ");
        $stmt->execute([$whiteId]);
        $whitePlayer = $stmt->fetch();

        $stmt->execute([$blackId]);
        $blackPlayer = $stmt->fetch();

        if (!$whitePlayer || !$blackPlayer) {
            throw new Exception(__('error_not_found'));
        }

        // Determine which president to notify (use white player's club president)
        $presidentEmail = $whitePlayer['president_email'];

        // Create match
        $stmt = $db->prepare("
            INSERT INTO matches (circuit_id, white_player_id, black_player_id, result)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$circuitId, $whiteId, $blackId, $result]);
        $matchId = $db->lastInsertId();

        // Prepare match details for emails
        $matchDetails = [
            'white_name' => $whitePlayer['first_name'] . ' ' . $whitePlayer['last_name'],
            'black_name' => $blackPlayer['first_name'] . ' ' . $blackPlayer['last_name'],
            'result' => $result,
            'circuit_name' => $circuitData['name'],
        ];

        // Send confirmation emails
        $tokenWhite = createConfirmation('match', $matchId, $whitePlayer['email'], 'white');
        sendMatchConfirmation($whitePlayer['email'], 'player', $matchDetails, $tokenWhite);

        $tokenBlack = createConfirmation('match', $matchId, $blackPlayer['email'], 'black');
        sendMatchConfirmation($blackPlayer['email'], 'player', $matchDetails, $tokenBlack);

        $tokenPresident = createConfirmation('match', $matchId, $presidentEmail, 'president');
        sendMatchConfirmation($presidentEmail, 'president', $matchDetails, $tokenPresident);

        $message = __('match_submitted');
        $messageType = 'success';

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get circuits with active players
$circuits = $db->query("
    SELECT c.* FROM circuits c
    WHERE c.confirmed = 1
    AND EXISTS (
        SELECT 1 FROM ratings r
        JOIN players p ON p.id = r.player_id
        WHERE r.circuit_id = c.id AND p.confirmed = 1
    )
    ORDER BY c.name
")->fetchAll();

$selectedCircuit = (int)($_GET['circuit'] ?? 0);
?>

<div class="container">
    <div class="page-header">
        <h1><?= __('match_submit') ?></h1>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <div class="card" style="max-width: 600px;">
        <form method="POST" id="matchForm">
            <div class="form-group">
                <label for="circuit"><?= __('form_circuit') ?></label>
                <select id="circuit" name="circuit_id" required onchange="loadCircuitPlayers()">
                    <option value="">-- <?= __('form_select') ?> --</option>
                    <?php foreach ($circuits as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selectedCircuit === $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="white"><?= __('form_white') ?></label>
                <select id="white" name="white_player_id" required disabled>
                    <option value="">-- <?= __('form_player') ?> --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="black"><?= __('form_black') ?></label>
                <select id="black" name="black_player_id" required disabled>
                    <option value="">-- <?= __('form_player') ?> --</option>
                </select>
            </div>

            <div class="form-group">
                <label for="result"><?= __('form_result') ?></label>
                <select id="result" name="result" required>
                    <option value="">-- <?= __('form_select') ?> --</option>
                    <option value="1-0"><?= __('result_white_wins') ?></option>
                    <option value="0-1"><?= __('result_black_wins') ?></option>
                    <option value="0.5-0.5"><?= __('result_draw') ?></option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
        </form>
    </div>
</div>

<script>
function loadCircuitPlayers() {
    const circuitId = document.getElementById('circuit').value;
    const whiteSelect = document.getElementById('white');
    const blackSelect = document.getElementById('black');

    if (!circuitId) {
        whiteSelect.disabled = true;
        blackSelect.disabled = true;
        whiteSelect.innerHTML = '<option value="">-- <?= __('form_player') ?> --</option>';
        blackSelect.innerHTML = '<option value="">-- <?= __('form_player') ?> --</option>';
        return;
    }

    fetch('?page=api&action=circuit_players&circuit_id=' + circuitId)
        .then(r => r.json())
        .then(data => {
            let playerOptions = '<option value="">-- <?= __('form_player') ?> --</option>';
            data.players.forEach(player => {
                playerOptions += `<option value="${player.id}">${player.first_name} ${player.last_name} (${player.club_name}) - ${player.rating}</option>`;
            });
            whiteSelect.innerHTML = playerOptions;
            blackSelect.innerHTML = playerOptions;
            whiteSelect.disabled = false;
            blackSelect.disabled = false;
        });
}

if (document.getElementById('circuit').value) {
    loadCircuitPlayers();
}
</script>
