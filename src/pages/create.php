<?php
/**
 * OpenElo - Create Circuit, Club, Player
 */

require_once SRC_PATH . '/mail.php';
require_once SRC_PATH . '/utils.php';

$db = Database::get();

$message = null;
$messageType = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create_circuit':
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');

                if (empty($name) || empty($email)) {
                    throw new Exception(__('error_required'));
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception(__('error_email'));
                }

                // Check for similar names
                $similar = checkSimilarName($db, 'circuits', $name);
                if ($similar) {
                    throw new Exception(($lang === 'it'
                        ? 'Esiste già un circuito con nome simile: "'
                        : 'A circuit with a similar name already exists: "')
                        . htmlspecialchars($similar['name']) . '"');
                }

                $stmt = $db->prepare("INSERT INTO circuits (name, owner_email) VALUES (?, ?)");
                $stmt->execute([$name, $email]);
                $circuitId = $db->lastInsertId();

                $token = createConfirmation('circuit', $circuitId, $email);
                sendCircuitConfirmation($email, $name, $token);

                // Redirect to circuit page
                header('Location: ?page=circuit&id=' . $circuitId);
                exit;

            case 'create_club':
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $circuitId = (int)($_POST['circuit_id'] ?? 0);

                if (empty($name) || empty($email) || !$circuitId) {
                    throw new Exception(__('error_required'));
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception(__('error_email'));
                }

                // Get circuit
                $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ? AND confirmed = 1");
                $stmt->execute([$circuitId]);
                $circuit = $stmt->fetch();

                if (!$circuit) {
                    throw new Exception(__('error_not_found'));
                }

                // Check for similar names
                $similar = checkSimilarName($db, 'clubs', $name);
                if ($similar) {
                    throw new Exception(($lang === 'it'
                        ? 'Esiste già un circolo con nome simile: "'
                        : 'A club with a similar name already exists: "')
                        . htmlspecialchars($similar['name']) . '"');
                }

                // Create club
                $stmt = $db->prepare("INSERT INTO clubs (name, president_email) VALUES (?, ?)");
                $stmt->execute([$name, $email]);
                $clubId = $db->lastInsertId();

                // Create circuit-club membership (primary)
                $stmt = $db->prepare("INSERT INTO circuit_clubs (circuit_id, club_id, is_primary) VALUES (?, ?, 1)");
                $stmt->execute([$circuitId, $clubId]);
                $membershipId = $db->lastInsertId();

                // Send email to president
                $tokenPresident = createConfirmation('club_president', $membershipId, $email);
                sendClubPresidentConfirmation($email, $name, $circuit['name'], $tokenPresident);

                // Send email to circuit manager
                $tokenCircuit = createConfirmation('club_circuit', $membershipId, $circuit['owner_email']);
                sendClubCircuitConfirmation($circuit['owner_email'], $name, $circuit['name'], $tokenCircuit);

                // Redirect to club page
                header('Location: ?page=club&id=' . $clubId);
                exit;

            case 'register_player':
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $clubId = (int)($_POST['club_id'] ?? 0);

                if (empty($firstName) || empty($lastName) || empty($email) || !$clubId) {
                    throw new Exception(__('error_required'));
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception(__('error_email'));
                }

                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM players WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    throw new Exception(__('error_email_exists'));
                }

                // Get club (must be active)
                $stmt = $db->prepare("SELECT c.*,
                    (SELECT COUNT(*) FROM circuit_clubs cc WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1) as active_circuits
                    FROM clubs c WHERE c.id = ? AND c.president_confirmed = 1");
                $stmt->execute([$clubId]);
                $club = $stmt->fetch();

                if (!$club || $club['active_circuits'] == 0) {
                    throw new Exception(__('error_not_found'));
                }

                // Create player
                $stmt = $db->prepare("INSERT INTO players (first_name, last_name, email, club_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $email, $clubId]);
                $playerId = $db->lastInsertId();

                $playerName = "$firstName $lastName";

                // Send email to player
                $tokenPlayer = createConfirmation('player_self', $playerId, $email);
                sendPlayerSelfConfirmation($email, $playerName, $club['name'], $tokenPlayer);

                // Send email to president
                $tokenPresident = createConfirmation('player_president', $playerId, $club['president_email']);
                sendPlayerPresidentConfirmation($club['president_email'], $playerName, $club['name'], $tokenPresident);

                // Redirect to player page
                header('Location: ?page=player&id=' . $playerId);
                exit;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get data for forms
$preselectedCircuit = (int)($_GET['circuit'] ?? 0);
$circuits = $db->query("SELECT * FROM circuits WHERE confirmed = 1 ORDER BY name")->fetchAll();

// Get active clubs (president confirmed + at least one active circuit)
$clubs = $db->query("
    SELECT c.* FROM clubs c
    WHERE c.president_confirmed = 1
    AND EXISTS (
        SELECT 1 FROM circuit_clubs cc
        WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
    )
    ORDER BY c.name
")->fetchAll();
?>

<div class="container">
    <h1 style="margin-bottom: 2rem;"><?= __('nav_create') ?></h1>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <div class="create-grid">
        <!-- Create Circuit -->
        <div class="create-section">
            <h2><?= __('circuit_create') ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_circuit">
                <div class="form-group">
                    <label for="circuit_name"><?= __('circuit_name') ?></label>
                    <input type="text" id="circuit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="circuit_email"><?= __('circuit_owner_email') ?></label>
                    <input type="email" id="circuit_email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
            </form>
        </div>

        <!-- Create Club (requires circuit) -->
        <?php if (!empty($circuits)): ?>
        <div class="create-section<?= $preselectedCircuit ? ' glow-highlight' : '' ?>" <?= $preselectedCircuit ? 'id="create-club"' : '' ?>>
            <h2><?= __('club_create') ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_club">
                <div class="form-group">
                    <label for="club_circuit"><?= __('form_circuit') ?></label>
                    <select id="club_circuit" name="circuit_id" required>
                        <option value="">-- <?= __('form_select') ?> --</option>
                        <?php foreach ($circuits as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $preselectedCircuit ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="club_name"><?= __('club_name') ?></label>
                    <input type="text" id="club_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="club_email"><?= __('club_president_email') ?></label>
                    <input type="email" id="club_email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Register Player (requires active club) -->
        <?php if (!empty($clubs)): ?>
        <div class="create-section">
            <h2><?= __('player_register') ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="register_player">
                <div class="form-group">
                    <label for="player_club"><?= __('form_club') ?></label>
                    <select id="player_club" name="club_id" required>
                        <option value="">-- <?= __('form_select') ?> --</option>
                        <?php foreach ($clubs as $club): ?>
                        <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="player_first"><?= __('form_first_name') ?></label>
                    <input type="text" id="player_first" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="player_last"><?= __('form_last_name') ?></label>
                    <input type="text" id="player_last" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="player_email"><?= __('form_email') ?></label>
                    <input type="email" id="player_email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($preselectedCircuit): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('create-club');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>
<?php endif; ?>
