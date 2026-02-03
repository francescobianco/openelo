<?php
/**
 * OpenElo - Create Circuit, Club, Player
 */

require_once SRC_PATH . '/mail.php';

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

                $stmt = $db->prepare("INSERT INTO circuits (name, owner_email) VALUES (?, ?)");
                $stmt->execute([$name, $email]);
                $circuitId = $db->lastInsertId();

                $token = createConfirmation('circuit', $circuitId, $email);
                sendCircuitConfirmation($email, $name, $token);

                $message = __('circuit_created');
                $messageType = 'success';
                break;

            case 'create_club':
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');

                if (empty($name) || empty($email)) {
                    throw new Exception(__('error_required'));
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception(__('error_email'));
                }

                $stmt = $db->prepare("INSERT INTO clubs (name, president_email) VALUES (?, ?)");
                $stmt->execute([$name, $email]);
                $clubId = $db->lastInsertId();

                $token = createConfirmation('club', $clubId, $email);
                sendClubConfirmation($email, $name, $token);

                $message = __('club_created');
                $messageType = 'success';
                break;

            case 'join_circuit':
                $clubId = (int)($_POST['club_id'] ?? 0);
                $circuitId = (int)($_POST['circuit_id'] ?? 0);

                if (!$clubId || !$circuitId) {
                    throw new Exception(__('error_required'));
                }

                // Get club and circuit
                $stmt = $db->prepare("SELECT * FROM clubs WHERE id = ? AND confirmed = 1");
                $stmt->execute([$clubId]);
                $club = $stmt->fetch();

                $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ? AND confirmed = 1");
                $stmt->execute([$circuitId]);
                $circuitData = $stmt->fetch();

                if (!$club || !$circuitData) {
                    throw new Exception(__('error_not_found'));
                }

                // Check if already member
                $stmt = $db->prepare("SELECT * FROM circuit_clubs WHERE circuit_id = ? AND club_id = ?");
                $stmt->execute([$circuitId, $clubId]);
                if ($stmt->fetch()) {
                    throw new Exception($lang === 'it' ? 'Già membro del circuito' : 'Already member of circuit');
                }

                // Create membership request
                $stmt = $db->prepare("INSERT INTO circuit_clubs (circuit_id, club_id) VALUES (?, ?)");
                $stmt->execute([$circuitId, $clubId]);
                $membershipId = $db->lastInsertId();

                // Send email to circuit owner
                $token = createConfirmation('membership', $membershipId, $circuitData['owner_email']);
                sendCircuitJoinRequest($circuitData['owner_email'], $club['name'], $circuitData['name'], $token);

                $message = __('club_request_sent');
                $messageType = 'success';
                break;

            case 'register_player':
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $clubId = (int)($_POST['club_id'] ?? 0);

                if (empty($firstName) || empty($lastName) || empty($email)) {
                    throw new Exception(__('error_required'));
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception(__('error_email'));
                }

                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM players WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    throw new Exception($lang === 'it' ? 'Email già registrata' : 'Email already registered');
                }

                $stmt = $db->prepare("INSERT INTO players (first_name, last_name, email, club_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $email, $clubId ?: null]);
                $playerId = $db->lastInsertId();

                $token = createConfirmation('player', $playerId, $email);
                sendPlayerConfirmation($email, "$firstName $lastName", $token);

                $message = __('player_registered');
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get data for forms
$circuits = $db->query("SELECT * FROM circuits WHERE confirmed = 1 ORDER BY name")->fetchAll();
$clubs = $db->query("SELECT * FROM clubs WHERE confirmed = 1 ORDER BY name")->fetchAll();
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

        <!-- Create Club -->
        <div class="create-section">
            <h2><?= __('club_create') ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="create_club">
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

        <!-- Join Circuit -->
        <?php if (!empty($clubs) && !empty($circuits)): ?>
        <div class="create-section">
            <h2><?= __('club_join_circuit') ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="join_circuit">
                <div class="form-group">
                    <label for="join_club"><?= __('form_club') ?></label>
                    <select id="join_club" name="club_id" required>
                        <option value="">-- <?= __('form_club') ?> --</option>
                        <?php foreach ($clubs as $club): ?>
                        <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="join_circuit"><?= __('form_circuit') ?></label>
                    <select id="join_circuit" name="circuit_id" required>
                        <option value="">-- <?= __('form_circuit') ?> --</option>
                        <?php foreach ($circuits as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Register Player -->
        <div class="create-section">
            <h2><?= __('player_register') ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="register_player">
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
                <?php if (!empty($clubs)): ?>
                <div class="form-group">
                    <label for="player_club"><?= __('form_club') ?> (<?= $lang === 'it' ? 'opzionale' : 'optional' ?>)</label>
                    <select id="player_club" name="club_id">
                        <option value="">-- <?= __('form_club') ?> --</option>
                        <?php foreach ($clubs as $club): ?>
                        <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
            </form>
        </div>
    </div>
</div>
