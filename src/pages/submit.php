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
        $clubId = (int)($_POST['club_id'] ?? 0);
        $whiteId = (int)($_POST['white_player_id'] ?? 0);
        $blackId = (int)($_POST['black_player_id'] ?? 0);
        $result = $_POST['result'] ?? '';

        if (!$circuitId || !$clubId || !$whiteId || !$blackId || !$result) {
            throw new Exception(__('error_required'));
        }

        if ($whiteId === $blackId) {
            throw new Exception($lang === 'it' ? 'I giocatori devono essere diversi' : 'Players must be different');
        }

        // Validate result
        if (!in_array($result, ['1-0', '0-1', '0.5-0.5'])) {
            throw new Exception($lang === 'it' ? 'Risultato non valido' : 'Invalid result');
        }

        // Get club and verify it's in the circuit
        $stmt = $db->prepare("
            SELECT c.* FROM clubs c
            JOIN circuit_clubs cc ON cc.club_id = c.id
            WHERE c.id = ? AND cc.circuit_id = ? AND cc.confirmed = 1 AND c.confirmed = 1
        ");
        $stmt->execute([$clubId, $circuitId]);
        $club = $stmt->fetch();

        if (!$club) {
            throw new Exception($lang === 'it' ? 'Circolo non trovato nel circuito' : 'Club not found in circuit');
        }

        // Get circuit
        $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ? AND confirmed = 1");
        $stmt->execute([$circuitId]);
        $circuitData = $stmt->fetch();

        // Get players
        $stmt = $db->prepare("SELECT * FROM players WHERE id = ? AND confirmed = 1");
        $stmt->execute([$whiteId]);
        $whitePlayer = $stmt->fetch();

        $stmt->execute([$blackId]);
        $blackPlayer = $stmt->fetch();

        if (!$whitePlayer || !$blackPlayer) {
            throw new Exception($lang === 'it' ? 'Giocatore non trovato' : 'Player not found');
        }

        // Create match
        $stmt = $db->prepare("
            INSERT INTO matches (circuit_id, club_id, white_player_id, black_player_id, result)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$circuitId, $clubId, $whiteId, $blackId, $result]);
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

        $tokenPresident = createConfirmation('match', $matchId, $club['president_email'], 'president');
        sendMatchConfirmation($club['president_email'], 'president', $matchDetails, $tokenPresident);

        $message = __('match_submitted');
        $messageType = 'success';

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get data for forms
$circuits = $db->query("SELECT * FROM circuits WHERE confirmed = 1 ORDER BY name")->fetchAll();
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
                <select id="circuit" name="circuit_id" required onchange="loadCircuitData()">
                    <option value="">-- <?= __('form_circuit') ?> --</option>
                    <?php foreach ($circuits as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selectedCircuit === $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="club"><?= __('form_club') ?></label>
                <select id="club" name="club_id" required disabled>
                    <option value="">-- <?= __('form_club') ?> --</option>
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
                    <option value="">-- <?= __('form_result') ?> --</option>
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
function loadCircuitData() {
    const circuitId = document.getElementById('circuit').value;
    const clubSelect = document.getElementById('club');
    const whiteSelect = document.getElementById('white');
    const blackSelect = document.getElementById('black');

    if (!circuitId) {
        clubSelect.disabled = true;
        whiteSelect.disabled = true;
        blackSelect.disabled = true;
        clubSelect.innerHTML = '<option value="">-- <?= __('form_club') ?> --</option>';
        whiteSelect.innerHTML = '<option value="">-- <?= __('form_player') ?> --</option>';
        blackSelect.innerHTML = '<option value="">-- <?= __('form_player') ?> --</option>';
        return;
    }

    // Load clubs and players via API
    fetch('?page=api&action=circuit_data&circuit_id=' + circuitId)
        .then(r => r.json())
        .then(data => {
            // Populate clubs
            clubSelect.innerHTML = '<option value="">-- <?= __('form_club') ?> --</option>';
            data.clubs.forEach(club => {
                clubSelect.innerHTML += `<option value="${club.id}">${club.name}</option>`;
            });
            clubSelect.disabled = false;

            // Populate players
            let playerOptions = '<option value="">-- <?= __('form_player') ?> --</option>';
            data.players.forEach(player => {
                playerOptions += `<option value="${player.id}">${player.first_name} ${player.last_name} (${player.rating})</option>`;
            });
            whiteSelect.innerHTML = playerOptions;
            blackSelect.innerHTML = playerOptions;
            whiteSelect.disabled = false;
            blackSelect.disabled = false;
        });
}

// Load on page load if circuit is preselected
if (document.getElementById('circuit').value) {
    loadCircuitData();
}
</script>
