<?php
/**
 * OpenElo - Submit Match Result
 */

require_once SRC_PATH . '/mail.php';
require_once SRC_PATH . '/elo.php';

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
        $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ? AND confirmed = 1 AND deleted_at IS NULL");
        $stmt->execute([$circuitId]);
        $circuitData = $stmt->fetch();

        if (!$circuitData) {
            throw new Exception(__('error_not_found'));
        }

        $circuitFormula = $circuitData['formula'] ?? 'classic_elo';

        // Ladder 3up Scorrimento: no draws allowed
        if ($circuitFormula === 'ladder_3up_sliding' && $result === '0.5-0.5') {
            throw new Exception(__('error_draw_not_allowed'));
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

        // Determine approval authority based on whether players are from same club
        $sameClub = ($whitePlayer['club_id'] === $blackPlayer['club_id']);

        if ($sameClub) {
            // Same club: president approval
            $approverEmail = $whitePlayer['president_email'];
            $approverRole = 'president';
        } else {
            // Different clubs: circuit manager approval
            $approverEmail = $circuitData['owner_email'];
            $approverRole = 'circuit_manager';
        }

        // Ladder 3up Sliding: validate position gap WITHOUT writing to DB.
        // Positions are only assigned/changed when the match is fully confirmed.
        if ($circuitFormula === 'ladder_3up_sliding') {
            $stmtPos = $db->prepare("
                SELECT player_id, ladder_position FROM ratings
                WHERE circuit_id = ? AND player_id IN (?, ?) AND ladder_position IS NOT NULL
            ");
            $stmtPos->execute([$circuitId, $whiteId, $blackId]);
            $existingPos = [];
            foreach ($stmtPos->fetchAll() as $row) {
                $existingPos[(int)$row['player_id']] = (int)$row['ladder_position'];
            }

            // Unranked players are treated as sitting just beyond the last position
            $stmtMax = $db->prepare("SELECT COALESCE(MAX(ladder_position), 0) FROM ratings WHERE circuit_id = ?");
            $stmtMax->execute([$circuitId]);
            $maxPos = (int)$stmtMax->fetchColumn();
            $virtual = 1;
            foreach ([$whiteId, $blackId] as $pid) {
                if (!isset($existingPos[$pid])) {
                    $existingPos[$pid] = $maxPos + $virtual++;
                }
            }

            if (abs($existingPos[$whiteId] - $existingPos[$blackId]) > 3) {
                throw new Exception(__('error_ladder_gap'));
            }
        }

        // Calculate rating changes at match creation time (frozen values)
        // For ladder circuits no ELO changes apply; store zeros
        if ($circuitFormula === 'ladder_3up_sliding') {
            $ratingChanges = [
                'white_rating' => 0,
                'black_rating' => 0,
                'white_change' => 0,
                'black_change' => 0,
            ];
        } else {
            $ratingChanges = calculateRatingChanges($whiteId, $blackId, $circuitId, $result);
        }

        // Create match with frozen ratings and changes
        $stmt = $db->prepare("
            INSERT INTO matches (
                circuit_id, white_player_id, black_player_id, result,
                white_rating_before, black_rating_before,
                white_rating_change, black_rating_change
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $circuitId, $whiteId, $blackId, $result,
            $ratingChanges['white_rating'], $ratingChanges['black_rating'],
            $ratingChanges['white_change'], $ratingChanges['black_change']
        ]);
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

        $tokenApprover = createConfirmation('match', $matchId, $approverEmail, 'president');
        sendMatchConfirmation($approverEmail, $approverRole === 'president' ? 'president' : 'circuit_manager', $matchDetails, $tokenApprover);

        // Redirect to match page
        header('Location: ?page=match&id=' . $matchId);
        exit;

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get circuits with active players (via club membership, not ratings)
$circuits = $db->query("
    SELECT c.* FROM circuits c
    WHERE c.confirmed = 1
    AND c.deleted_at IS NULL
    AND EXISTS (
        SELECT 1 FROM circuit_clubs cc
        JOIN players p ON p.club_id = cc.club_id
        WHERE cc.circuit_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
        AND p.confirmed = 1 AND p.deleted_at IS NULL
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

    <div class="card<?= $selectedCircuit ? ' glow-highlight' : '' ?>" style="max-width: 600px; margin: 0 auto;"<?= $selectedCircuit ? ' id="submit-match"' : '' ?>>
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
                    <option value="0.5-0.5" id="result-draw"><?= __('result_draw') ?></option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
        </form>
    </div>
</div>

<script>
let circuitFormula = '';

function loadCircuitPlayers() {
    const circuitId = document.getElementById('circuit').value;
    const whiteSelect = document.getElementById('white');
    const blackSelect = document.getElementById('black');
    const drawOption = document.getElementById('result-draw');

    if (!circuitId) {
        whiteSelect.disabled = true;
        blackSelect.disabled = true;
        whiteSelect.innerHTML = '<option value="">-- <?= __('form_player') ?> --</option>';
        blackSelect.innerHTML = '<option value="">-- <?= __('form_player') ?> --</option>';
        if (drawOption) drawOption.disabled = false;
        return;
    }

    fetch('?page=api&action=circuit_players&circuit_id=' + circuitId)
        .then(r => r.json())
        .then(data => {
            circuitFormula = data.formula || '';
            const isLadder = circuitFormula === 'ladder_3up_sliding';

            // Hide/show draw option
            if (drawOption) {
                drawOption.disabled = isLadder;
                if (isLadder && document.getElementById('result').value === '0.5-0.5') {
                    document.getElementById('result').value = '';
                }
            }

            const singleClub = data.club_count === 1;
            let playerOptions = '<option value="">-- <?= __('form_player') ?> --</option>';
            data.players.forEach(player => {
                let label;
                const name = singleClub
                    ? `${player.last_name} ${player.first_name}`
                    : `${player.last_name} ${player.first_name} (${player.club_name})`;
                if (isLadder) {
                    const pos = player.ladder_position !== null ? ('#' + player.ladder_position) : '<?= $lang === 'it' ? 'non classificato' : 'unranked' ?>';
                    label = `${name} - ${pos}`;
                } else {
                    const rating = player.rating !== null ? player.rating : <?= ELO_START ?>;
                    label = `${name} - ${rating}`;
                }
                playerOptions += `<option value="${player.id}" data-ladder-pos="${player.ladder_position ?? ''}">${label}</option>`;
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

<?php if ($selectedCircuit): ?>
var el = document.getElementById('submit-match');
if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
<?php endif; ?>
</script>
