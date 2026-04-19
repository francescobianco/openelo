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
                $viewToken = generateToken();
                $stmt = $db->prepare("INSERT INTO players (first_name, last_name, email, club_id, view_token) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $email, $clubId, $viewToken]);
                $playerId = $db->lastInsertId();

                $playerName = "$firstName $lastName";

                // Send email to player
                $tokenPlayer = createConfirmation('player_self', $playerId, $email);
                sendPlayerSelfConfirmation($email, $playerName, $club['name'], $tokenPlayer);

                // Send email to president
                $tokenPresident = createConfirmation('player_president', $playerId, $club['president_email']);
                sendPlayerPresidentConfirmation($club['president_email'], $playerName, $club['name'], $tokenPresident);

                // Redirect to player page
                header('Location: ?page=player&id=' . $playerId . '&new=1');
                exit;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get data for forms
$preselectedCircuit = (int)($_GET['circuit'] ?? 0);
$preselectedClub    = (int)($_GET['club'] ?? 0);
$joinCircuitId      = (int)($_GET['join_circuit'] ?? 0);
$highlightCircuit   = ($_GET['highlight'] ?? '') === 'circuit';
$circuits = $db->query("SELECT * FROM circuits WHERE confirmed = 1 AND deleted_at IS NULL ORDER BY name")->fetchAll();

// Get active clubs — filtered to circuit if join_circuit is set
if ($joinCircuitId) {
    $stmt = $db->prepare("
        SELECT c.* FROM clubs c
        JOIN circuit_clubs cc ON cc.club_id = c.id
        WHERE c.president_confirmed = 1 AND c.deleted_at IS NULL
          AND cc.circuit_id = ? AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
        ORDER BY c.name
    ");
    $stmt->execute([$joinCircuitId]);
    $clubs = $stmt->fetchAll();
    // If single club, lock it as preselected
    if (count($clubs) === 1) {
        $preselectedClub = $clubs[0]['id'];
    }
    // Load the circuit name for display
    $stmt = $db->prepare("SELECT name FROM circuits WHERE id = ?");
    $stmt->execute([$joinCircuitId]);
    $joinCircuit = $stmt->fetch();
} else {
    $joinCircuit = null;
    $clubs = $db->query("
        SELECT c.* FROM clubs c
        WHERE c.president_confirmed = 1
        AND c.deleted_at IS NULL
        AND EXISTS (
            SELECT 1 FROM circuit_clubs cc
            WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1
        )
        ORDER BY c.name
    ")->fetchAll();
}
?>

<div class="container">
    <?php if ($joinCircuit): ?>
    <div class="page-header" style="margin-bottom: 1.5rem;">
        <div>
            <h1><?= $lang === 'it' ? 'Iscriviti al Circuito' : 'Join Circuit' ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><a href="?page=circuit&id=<?= $joinCircuitId ?>"><?= htmlspecialchars($joinCircuit['name']) ?></a></span>
            </div>
        </div>
    </div>
    <?php else: ?>
    <h1 style="margin-bottom: 2rem; text-align: center;"><?= __('nav_create') ?></h1>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($joinCircuitId): ?>
    <!-- Join circuit: show only player registration -->
    <div class="create-grid">
        <?php if (!empty($clubs)): ?>
        <div class="create-section glow-highlight" id="register-player">
            <h2><?= __('player_register') ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="register_player">
                <?php if (count($clubs) === 1): ?>
                <input type="hidden" name="club_id" value="<?= $clubs[0]['id'] ?>">
                <div class="form-group">
                    <label><?= __('form_club') ?></label>
                    <input type="text" value="<?= htmlspecialchars($clubs[0]['name']) ?>" disabled style="opacity: 0.7; cursor: not-allowed;">
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label for="player_club"><?= __('form_club') ?></label>
                    <select id="player_club" name="club_id" required>
                        <option value="">-- <?= __('form_select') ?> --</option>
                        <?php foreach ($clubs as $club): ?>
                        <option value="<?= $club['id'] ?>" <?= $club['id'] == $preselectedClub ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
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
        <?php else: ?>
        <div class="create-section">
            <p style="color: var(--text-secondary);">
                <?= $lang === 'it'
                    ? 'Nessun circolo attivo in questo circuito. Contatta il responsabile per aderire.'
                    : 'No active clubs in this circuit. Contact the manager to join.' ?>
            </p>
            <a href="?page=circuit&id=<?= $joinCircuitId ?>&tab=manager" class="btn btn-secondary" style="margin-top: 1rem;">
                <?= $lang === 'it' ? 'Contatta il Responsabile' : 'Contact Manager' ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="create-grid">
        <!-- Create Circuit -->
        <div class="create-section<?= $highlightCircuit ? ' glow-highlight' : '' ?>"<?= $highlightCircuit ? ' id="create-circuit"' : '' ?>>
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
        <div class="create-section<?= $preselectedClub ? ' glow-highlight' : '' ?>"<?= $preselectedClub ? ' id="register-player"' : '' ?>>
            <h2><?= __('player_register') ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="register_player">
                <div class="form-group">
                    <label for="player_club"><?= __('form_club') ?></label>
                    <select id="player_club" name="club_id" required>
                        <option value="">-- <?= __('form_select') ?> --</option>
                        <?php foreach ($clubs as $club): ?>
                        <option value="<?= $club['id'] ?>" <?= $club['id'] == $preselectedClub ? 'selected' : '' ?>><?= htmlspecialchars($club['name']) ?></option>
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
    <?php endif; // end join_circuit else ?>
</div>

<?php
$scrollTarget = null;
if (!$joinCircuitId) {
    if ($preselectedCircuit) $scrollTarget = 'create-club';
    elseif ($preselectedClub) $scrollTarget = 'register-player';
    elseif ($highlightCircuit) $scrollTarget = 'create-circuit';
} else {
    $scrollTarget = 'register-player';
}
?>
<?php if ($scrollTarget): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('<?= $scrollTarget ?>');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>
<?php endif; ?>
