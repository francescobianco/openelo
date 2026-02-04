<?php
/**
 * OpenElo - Club Detail Page
 */

require_once SRC_PATH . '/mail.php';

$db = Database::get();

$clubId = (int)($_GET['id'] ?? 0);
$message = null;
$messageType = null;

// Handle resend requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'resend_president') {
        $stmt = $db->prepare("SELECT c.*, cc.id as membership_id, ci.name as circuit_name
            FROM clubs c
            JOIN circuit_clubs cc ON cc.club_id = c.id
            JOIN circuits ci ON ci.id = cc.circuit_id
            WHERE c.id = ? AND cc.is_primary = 1");
        $stmt->execute([$clubId]);
        $data = $stmt->fetch();

        if ($data && !$data['president_confirmed']) {
            $token = createConfirmation('club_president', $data['membership_id'], $data['president_email']);
            sendClubPresidentConfirmation($data['president_email'], $data['name'], $data['circuit_name'], $token);
            $message = $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!';
            $messageType = 'success';
        }
    } elseif ($_POST['action'] === 'resend_circuit' && isset($_POST['membership_id'])) {
        $membershipId = (int)$_POST['membership_id'];
        $stmt = $db->prepare("SELECT c.name as club_name, ci.name as circuit_name, ci.owner_email, cc.*
            FROM circuit_clubs cc
            JOIN clubs c ON c.id = cc.club_id
            JOIN circuits ci ON ci.id = cc.circuit_id
            WHERE cc.id = ?");
        $stmt->execute([$membershipId]);
        $data = $stmt->fetch();

        if ($data && !$data['circuit_confirmed']) {
            $token = createConfirmation($data['is_primary'] ? 'club_circuit' : 'membership_circuit', $membershipId, $data['owner_email']);
            sendClubCircuitConfirmation($data['owner_email'], $data['club_name'], $data['circuit_name'], $token);
            $message = $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!';
            $messageType = 'success';
        }
    }
}

// Get club (allow access even if not fully confirmed)
$stmt = $db->prepare("
    SELECT c.*,
        (SELECT COUNT(*) FROM circuit_clubs cc WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1) as active_circuits
    FROM clubs c
    WHERE c.id = ?
");
$stmt->execute([$clubId]);
$club = $stmt->fetch();

if (!$club) {
    header('Location: ?page=circuits');
    exit;
}

// Check pending confirmations
$pendingConfirmations = [];
if (!$club['president_confirmed']) {
    $pendingConfirmations[] = [
        'type' => 'president',
        'description' => $lang === 'it'
            ? 'Conferma del presidente (' . htmlspecialchars($club['president_email']) . ')'
            : 'President confirmation (' . htmlspecialchars($club['president_email']) . ')'
    ];
}

// Handle join circuit request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_circuit') {
    try {
        $circuitId = (int)($_POST['circuit_id'] ?? 0);

        if (!$circuitId) {
            throw new Exception(__('error_required'));
        }

        // Get circuit
        $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ? AND confirmed = 1");
        $stmt->execute([$circuitId]);
        $circuit = $stmt->fetch();

        if (!$circuit) {
            throw new Exception(__('error_not_found'));
        }

        // Check if already member
        $stmt = $db->prepare("SELECT * FROM circuit_clubs WHERE circuit_id = ? AND club_id = ?");
        $stmt->execute([$circuitId, $clubId]);
        if ($stmt->fetch()) {
            throw new Exception(__('error_already_member'));
        }

        // Create membership request
        $stmt = $db->prepare("INSERT INTO circuit_clubs (circuit_id, club_id) VALUES (?, ?)");
        $stmt->execute([$circuitId, $clubId]);
        $membershipId = $db->lastInsertId();

        // Send email to president
        $tokenPresident = createConfirmation('membership_club', $membershipId, $club['president_email']);
        sendClubPresidentConfirmation($club['president_email'], $club['name'], $circuit['name'], $tokenPresident);

        // Send email to circuit manager
        $tokenCircuit = createConfirmation('membership_circuit', $membershipId, $circuit['owner_email']);
        sendClubCircuitConfirmation($circuit['owner_email'], $club['name'], $circuit['name'], $tokenCircuit);

        $message = __('club_request_sent');
        $messageType = 'success';

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Get club's circuits
$stmt = $db->prepare("
    SELECT ci.*, cc.id as membership_id, cc.club_confirmed, cc.circuit_confirmed
    FROM circuit_clubs cc
    JOIN circuits ci ON ci.id = cc.circuit_id
    WHERE cc.club_id = ?
    ORDER BY ci.name
");
$stmt->execute([$clubId]);
$clubCircuits = $stmt->fetchAll();

// Check for circuit approval pending
foreach ($clubCircuits as $cc) {
    if (!$cc['club_confirmed']) {
        $pendingConfirmations[] = [
            'type' => 'club_circuit',
            'description' => $lang === 'it'
                ? 'Conferma del presidente per il circuito "' . htmlspecialchars($cc['name']) . '"'
                : 'President confirmation for circuit "' . htmlspecialchars($cc['name']) . '"'
        ];
    }
    if (!$cc['circuit_confirmed']) {
        $pendingConfirmations[] = [
            'type' => 'circuit_manager',
            'membership_id' => $cc['membership_id'],
            'description' => $lang === 'it'
                ? 'Conferma del responsabile del circuito "' . htmlspecialchars($cc['name']) . '"'
                : 'Circuit manager confirmation for "' . htmlspecialchars($cc['name']) . '"'
        ];
    }
}

// Get players in this club
$stmt = $db->prepare("
    SELECT p.* FROM players p
    WHERE p.club_id = ? AND p.confirmed = 1
    ORDER BY p.last_name, p.first_name
");
$stmt->execute([$clubId]);
$players = $stmt->fetchAll();

// Get circuits club can join
$stmt = $db->prepare("
    SELECT c.* FROM circuits c
    WHERE c.confirmed = 1
    AND NOT EXISTS (
        SELECT 1 FROM circuit_clubs cc
        WHERE cc.circuit_id = c.id AND cc.club_id = ?
    )
    ORDER BY c.name
");
$stmt->execute([$clubId]);
$availableCircuits = $stmt->fetchAll();

$isActive = $club['active_circuits'] > 0;
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($club['name']) ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <?php if ($isActive): ?>
                <span class="badge badge-success"><?= __('club_active') ?></span>
                <?php else: ?>
                <span class="badge badge-warning"><?= __('club_pending') ?></span>
                <?php endif; ?>
                <span><?= count($players) ?> <?= __('circuit_players') ?></span>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($pendingConfirmations)): ?>
    <div class="alert alert-warning">
        <h3 style="margin-top: 0;">⏳ <?= $lang === 'it' ? 'Approvazioni in attesa' : 'Pending Approvals' ?></h3>
        <p><?= $lang === 'it' ? 'Questo circolo non è ancora completamente attivo. Sono necessarie le seguenti approvazioni:' : 'This club is not yet fully active. The following approvals are required:' ?></p>
        <ul style="margin: 1rem 0;">
            <?php foreach ($pendingConfirmations as $pending): ?>
            <li style="margin: 0.5rem 0;">
                <?= $pending['description'] ?>
                <?php if ($pending['type'] === 'president'): ?>
                <form method="POST" style="display: inline; margin-left: 1rem;">
                    <input type="hidden" name="action" value="resend_president">
                    <button type="submit" class="btn btn-sm"><?= $lang === 'it' ? 'Invia di nuovo richiesta' : 'Resend request' ?></button>
                </form>
                <?php elseif ($pending['type'] === 'circuit_manager'): ?>
                <form method="POST" style="display: inline; margin-left: 1rem;">
                    <input type="hidden" name="action" value="resend_circuit">
                    <input type="hidden" name="membership_id" value="<?= $pending['membership_id'] ?>">
                    <button type="submit" class="btn btn-sm"><?= $lang === 'it' ? 'Invia di nuovo richiesta' : 'Resend request' ?></button>
                </form>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="create-grid">
        <!-- Club's Circuits -->
        <div class="create-section">
            <h2><?= __('nav_circuits') ?></h2>
            <?php if (empty($clubCircuits)): ?>
            <p style="color: var(--text-secondary);"><?= $lang === 'it' ? 'Nessun circuito.' : 'No circuits.' ?></p>
            <?php else: ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($clubCircuits as $cc): ?>
                <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                    <a href="?page=circuit&id=<?= $cc['id'] ?>"><?= htmlspecialchars($cc['name']) ?></a>
                    <?php if ($cc['club_confirmed'] && $cc['circuit_confirmed']): ?>
                    <span class="badge badge-success" style="margin-left: 0.5rem;"><?= __('status_active') ?></span>
                    <?php elseif (!$cc['club_confirmed']): ?>
                    <span class="badge badge-warning" style="margin-left: 0.5rem;"><?= __('status_pending_president') ?></span>
                    <?php else: ?>
                    <span class="badge badge-warning" style="margin-left: 0.5rem;"><?= __('status_pending_circuit') ?></span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Join Another Circuit -->
        <?php if (!empty($availableCircuits)): ?>
        <div class="create-section">
            <h2><?= __('club_join_circuit') ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="join_circuit">
                <div class="form-group">
                    <label for="circuit_id"><?= __('form_circuit') ?></label>
                    <select id="circuit_id" name="circuit_id" required>
                        <option value="">-- <?= __('form_select') ?> --</option>
                        <?php foreach ($availableCircuits as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Players -->
        <div class="create-section">
            <h2><?= __('circuit_players') ?></h2>
            <?php if (empty($players)): ?>
            <p style="color: var(--text-secondary);"><?= $lang === 'it' ? 'Nessun giocatore.' : 'No players.' ?></p>
            <?php else: ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($players as $p): ?>
                <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                    <a href="?page=player&id=<?= $p['id'] ?>"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
