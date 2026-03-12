<?php
/**
 * OpenElo - Club Detail Page
 */

require_once SRC_PATH . '/mail.php';
require_once SRC_PATH . '/utils.php';

$db = Database::get();

$clubId = (int)($_GET['id'] ?? 0);
$message = null;
$messageType = null;

// Load club first (needed by POST handlers too)
$stmt = $db->prepare("
    SELECT c.*,
        (SELECT COUNT(*) FROM circuit_clubs cc WHERE cc.club_id = c.id AND cc.club_confirmed = 1 AND cc.circuit_confirmed = 1) as active_circuits
    FROM clubs c
    WHERE c.id = ? AND c.deleted_at IS NULL
");
$stmt->execute([$clubId]);
$club = $stmt->fetch();

if (!$club) {
    header('Location: ?page=clubs');
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $isReminderAction = in_array($_POST['action'], ['resend_president', 'resend_club_circuit', 'resend_circuit']);
    $rateLimitError = $isReminderAction ? checkReminderRateLimit() : null;

    if ($rateLimitError) {
        $message = $lang === 'it'
            ? 'Stai mandando troppi solleciti! Potrai mandare il prossimo tra ' . $rateLimitError['minutes'] . ' minuti.'
            : 'You are sending too many reminders! You can send the next one in ' . $rateLimitError['minutes'] . ' minutes.';
        $messageType = 'error';
    } elseif ($_POST['action'] === 'resend_president') {
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
            logReminder();
            $message = $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!';
            $messageType = 'success';
        }
    } elseif ($_POST['action'] === 'resend_club_circuit' && isset($_POST['membership_id'])) {
        $membershipId = (int)$_POST['membership_id'];
        $stmt = $db->prepare("SELECT c.name as club_name, c.president_email, ci.name as circuit_name, cc.*
            FROM circuit_clubs cc
            JOIN clubs c ON c.id = cc.club_id
            JOIN circuits ci ON ci.id = cc.circuit_id
            WHERE cc.id = ?");
        $stmt->execute([$membershipId]);
        $data = $stmt->fetch();
        if ($data && !$data['club_confirmed']) {
            $token = createConfirmation('membership_club', $membershipId, $data['president_email']);
            sendClubPresidentConfirmation($data['president_email'], $data['club_name'], $data['circuit_name'], $token);
            logReminder();
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
            logReminder();
            $message = $lang === 'it' ? 'Email di conferma inviata nuovamente!' : 'Confirmation email sent again!';
            $messageType = 'success';
        }
    } elseif ($_POST['action'] === 'request_protected_toggle') {
        // Send email to president asking to confirm enabling/disabling protected mode
        $desiredMode = $club['protected_mode'] ? 'disable' : 'enable';
        $token = createConfirmation('protected_mode_toggle', $clubId, $club['president_email'], $desiredMode);
        sendProtectedModeConfirmation($club['president_email'], $club['name'], $desiredMode, $token);
        $message = $lang === 'it'
            ? 'Email inviata al presidente del circolo per confermare il cambio di modalità.'
            : 'Email sent to the club president to confirm the mode change.';
        $messageType = 'success';
    } elseif ($_POST['action'] === 'sono_il_presidente') {
        $token = createConfirmation('club_access_president', $clubId, $club['president_email']);
        sendClubAccessConfirmation($club['president_email'], $club['name'], 'president', $token);
        $message = $lang === 'it'
            ? 'Email inviata al presidente! Controlla la casella e clicca il link per confermare.'
            : 'Email sent to the president! Check the inbox and click the link to confirm.';
        $messageType = 'success';
    } elseif ($_POST['action'] === 'request_club_update') {
        try {
            $newName  = trim($_POST['name'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $website  = trim($_POST['website'] ?? '');

            if (empty($newName)) throw new Exception(__('error_required'));

            // Sanitize website: force https
            if ($website !== '') {
                if (!preg_match('#^https?://#i', $website)) {
                    $website = 'https://' . $website;
                }
                $website = preg_replace('#^http://#i', 'https://', $website);
                if (!str_starts_with($website, 'https://')) {
                    throw new Exception($lang === 'it' ? 'Il sito web deve usare HTTPS.' : 'Website must use HTTPS.');
                }
            }

            $stmt = $db->prepare("INSERT INTO club_update_requests (club_id, name, location, website) VALUES (?, ?, ?, ?)");
            $stmt->execute([$clubId, $newName, $location ?: null, $website ?: null]);
            $requestId = $db->lastInsertId();

            $token = createConfirmation('club_update', $requestId, $club['president_email']);
            sendClubUpdateConfirmation($club['president_email'], $club['name'], $newName, $location, $website, $token);

            $message = $lang === 'it'
                ? 'Richiesta inviata! Il presidente riceverà un\'email per approvare le modifiche.'
                : 'Request sent! The president will receive an email to approve the changes.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($_POST['action'] === 'join_circuit') {
        try {
            $circuitId = (int)($_POST['circuit_id'] ?? 0);
            if (!$circuitId) throw new Exception(__('error_required'));

            $stmt = $db->prepare("SELECT * FROM circuits WHERE id = ? AND confirmed = 1 AND deleted_at IS NULL");
            $stmt->execute([$circuitId]);
            $circuit = $stmt->fetch();
            if (!$circuit) throw new Exception(__('error_not_found'));

            $stmt = $db->prepare("SELECT * FROM circuit_clubs WHERE circuit_id = ? AND club_id = ?");
            $stmt->execute([$circuitId, $clubId]);
            if ($stmt->fetch()) throw new Exception(__('error_already_member'));

            $stmt = $db->prepare("INSERT INTO circuit_clubs (circuit_id, club_id) VALUES (?, ?)");
            $stmt->execute([$circuitId, $clubId]);
            $membershipId = $db->lastInsertId();

            $tokenPresident = createConfirmation('membership_club', $membershipId, $club['president_email']);
            sendClubPresidentConfirmation($club['president_email'], $club['name'], $circuit['name'], $tokenPresident);

            $tokenCircuit = createConfirmation('membership_circuit', $membershipId, $circuit['owner_email']);
            sendClubCircuitConfirmation($circuit['owner_email'], $club['name'], $circuit['name'], $tokenCircuit);

            $message = __('club_request_sent');
            $messageType = 'success';
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Protected mode access check via cookie
$hasClubAccess = hasClubAccess($clubId);
$isProtectedMember = ($club['protected_mode'] && $hasClubAccess);

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

// Get club's circuits
$stmt = $db->prepare("
    SELECT ci.*, cc.id as membership_id, cc.club_confirmed, cc.circuit_confirmed
    FROM circuit_clubs cc
    JOIN circuits ci ON ci.id = cc.circuit_id
    WHERE cc.club_id = ? AND ci.deleted_at IS NULL
    ORDER BY ci.name
");
$stmt->execute([$clubId]);
$clubCircuits = $stmt->fetchAll();

foreach ($clubCircuits as $cc) {
    if (!$cc['club_confirmed']) {
        $pendingConfirmations[] = [
            'type' => 'club_circuit',
            'membership_id' => $cc['membership_id'],
            'description' => $lang === 'it'
                ? 'Conferma del presidente del circolo per l\'adesione al circuito "' . htmlspecialchars($cc['name']) . '"'
                : 'Club president confirmation for joining circuit "' . htmlspecialchars($cc['name']) . '"'
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

// Get players
$stmt = $db->prepare("
    SELECT p.* FROM players p
    WHERE p.club_id = ? AND p.confirmed = 1 AND p.deleted_at IS NULL
    ORDER BY p.last_name, p.first_name
");
$stmt->execute([$clubId]);
$players = $stmt->fetchAll();

// Get joinable circuits
$stmt = $db->prepare("
    SELECT c.* FROM circuits c
    WHERE c.confirmed = 1 AND c.deleted_at IS NULL
    AND NOT EXISTS (
        SELECT 1 FROM circuit_clubs cc WHERE cc.circuit_id = c.id AND cc.club_id = ?
    )
    ORDER BY c.name
");
$stmt->execute([$clubId]);
$availableCircuits = $stmt->fetchAll();

$isActive = $club['active_circuits'] > 0;
$tab = $_GET['tab'] ?? 'main';
if (!in_array($tab, ['main', 'management'])) $tab = 'main';
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><?= htmlspecialchars($club['name']) ?></h1>
            <?php if (!empty($club['location']) || !empty($club['website'])): ?>
            <div style="margin: 0 0 0.75rem -3px; font-size: 0.9rem; color: var(--text-secondary); display: flex; flex-wrap: wrap; gap: 0.5rem 1.5rem;">
                <?php if (!empty($club['location'])): ?>
                <span>&#128205; <?= htmlspecialchars($club['location']) ?></span>
                <?php endif; ?>
                <?php if (!empty($club['website'])): ?>
                <span>&#127760; <a href="<?= htmlspecialchars($club['website']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($club['website']) ?></a></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <?php if ($isActive): ?>
                <span class="badge badge-success"><?= __('club_active') ?></span>
                <?php else: ?>
                <span class="badge badge-warning"><?= __('club_pending') ?></span>
                <?php endif; ?>
                <?php if ($club['protected_mode']): ?>
                <span class="badge badge-warning" title="<?= $lang === 'it' ? 'I nomi dei giocatori sono visibili solo ai membri del club' : 'Player names are visible only to club members' ?>">
                    &#128274; <?= $lang === 'it' ? 'Modalità protetta' : 'Protected mode' ?>
                </span>
                <?php endif; ?>
                <span><?= count($players) ?> <?= __('circuit_players') ?></span>
            </div>
        </div>
        <?php if ($isActive || ($club['protected_mode'] && !$hasClubAccess)): ?>
        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
            <?php if ($club['protected_mode'] && !$hasClubAccess): ?>
            <form method="POST">
                <input type="hidden" name="action" value="sono_il_presidente">
                <button type="submit" class="btn btn-secondary" style="font-size: 0.9rem;">
                    &#128081; <?= $lang === 'it' ? 'Sono il presidente' : 'I\'m the president' ?>
                </button>
            </form>
            <?php endif; ?>
            <?php if ($isActive): ?>
            <a href="?page=create&club=<?= $clubId ?>" class="btn btn-primary"><?= $lang === 'it' ? 'Registra Giocatore' : 'Register Player' ?></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($pendingConfirmations)): ?>
    <div class="alert alert-warning">
        <p style="margin: 0 0 0.75rem 0;">
            <strong><?= $lang === 'it' ? 'Approvazioni in attesa' : 'Pending Approvals' ?></strong>
            <span style="font-weight: 400; color: var(--text-secondary); font-size: 0.9rem;"> — <?= $lang === 'it' ? 'questo circolo non è ancora completamente attivo' : 'this club is not yet fully active' ?></span>
        </p>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <?php foreach ($pendingConfirmations as $pending): ?>
            <li style="padding: 0.5rem 0; border-top: 1px solid var(--border); display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 0.25rem 1rem;">
                <span style="font-size: 0.9rem;"><?= $pending['description'] ?></span>
                <?php if ($pending['type'] === 'president'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_president">
                    <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: 0.85rem; white-space: nowrap;">
                        <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                    </button>
                </form>
                <?php elseif ($pending['type'] === 'club_circuit'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_club_circuit">
                    <input type="hidden" name="membership_id" value="<?= $pending['membership_id'] ?>">
                    <button type="submit" style="background: none; border: none; color: var(--accent); text-decoration: underline; cursor: pointer; padding: 0; font-size: 0.85rem; white-space: nowrap;">
                        <?= $lang === 'it' ? 'manda sollecito' : 'send reminder' ?>
                    </button>
                </form>
                <?php elseif ($pending['type'] === 'circuit_manager'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="resend_circuit">
                    <input type="hidden" name="membership_id" value="<?= $pending['membership_id'] ?>">
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

    <!-- Tab Navigation -->
    <div class="tabs">
        <a href="?page=club&id=<?= $clubId ?>&tab=main" class="tab <?= $tab === 'main' ? 'active' : '' ?>">
            <?= $lang === 'it' ? 'Circuiti e Giocatori' : 'Circuits & Players' ?>
        </a>
        <a href="?page=club&id=<?= $clubId ?>&tab=management" class="tab <?= $tab === 'management' ? 'active' : '' ?>">
            <?= $lang === 'it' ? 'Gestione' : 'Management' ?>
        </a>
    </div>

    <!-- Tab: Circuits & Players -->
    <?php if ($tab === 'main'): ?>
    <div>
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
                        <span class="badge badge-warning" style="margin-left: 0.5rem;"><?= __('club_pending') ?></span>
                        <?php else: ?>
                        <span class="badge badge-warning" style="margin-left: 0.5rem;"><?= __('club_pending') ?></span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- Players -->
            <div class="create-section">
                <h2><?= __('circuit_players') ?></h2>
                <?php if (empty($players)): ?>
                <p style="color: var(--text-secondary);"><?= $lang === 'it' ? 'Nessun giocatore.' : 'No players.' ?></p>
                <?php else: ?>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($players as $p): ?>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                        <?php if (!$club['protected_mode'] || $isProtectedMember): ?>
                        <a href="?page=player&id=<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                        </a>
                        <?php else: ?>
                        <a href="?page=player&id=<?= $p['id'] ?>" style="color: var(--text-secondary); letter-spacing: 0.05em;"><?= maskName($p['first_name'] . ' ' . $p['last_name']) ?></a>
                        <?php endif; ?>
                        <span style="color: var(--text-secondary); margin-left: 0.5rem;"><?= htmlspecialchars($p['category'] ?? 'NC') ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'management'): ?>
    <div>
        <div class="create-grid-2">
            <!-- Club Info Update -->
            <div class="create-section">
                <h2><?= $lang === 'it' ? 'Cambia Intestazione' : 'Update Club Info' ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="request_club_update">
                    <div class="form-group">
                        <label for="club_name"><?= $lang === 'it' ? 'Nome circolo' : 'Club name' ?></label>
                        <input type="text" id="club_name" name="name" value="<?= htmlspecialchars($club['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="club_location"><?= $lang === 'it' ? 'Località / Indirizzo' : 'Location / Address' ?></label>
                        <input type="text" id="club_location" name="location" value="<?= htmlspecialchars($club['location'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="club_website"><?= $lang === 'it' ? 'Sito web pubblico' : 'Public website' ?></label>
                        <input type="url" id="club_website" name="website" value="<?= htmlspecialchars($club['website'] ?? '') ?>" placeholder="https://...">
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0.75rem 0;">
                        <?= $lang === 'it'
                            ? 'Le modifiche richiederanno la conferma via email del presidente del circolo.'
                            : 'Changes will require email confirmation from the club president.' ?>
                    </p>
                    <button type="submit" class="btn btn-primary"><?= __('form_submit') ?></button>
                </form>
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

            <!-- Protected Mode -->
            <div class="create-section">
                <h2>&#128274; <?= $lang === 'it' ? 'Modalità Protetta' : 'Protected Mode' ?></h2>
                <?php if ($club['protected_mode']): ?>
                <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                    <?= $lang === 'it'
                        ? 'Attiva. I nomi dei giocatori sono nascosti ai visitatori anonimi e visibili solo ai membri del club tramite il loro link personale.'
                        : 'Active. Player names are hidden from anonymous visitors and visible only to club members via their personal link.' ?>
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="request_protected_toggle">
                    <button type="submit" class="btn btn-secondary">
                        <?= $lang === 'it' ? 'Disattiva modalità protetta' : 'Disable protected mode' ?>
                    </button>
                </form>
                <?php else: ?>
                <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                    <?= $lang === 'it'
                        ? 'Non attiva. Attivandola, i nomi dei giocatori saranno visibili solo ai membri del club tramite il loro link personale. Utile per proteggere la privacy dei minori o di chi non vuole che il proprio nome sia pubblico.'
                        : 'Inactive. When enabled, player names will only be visible to club members via their personal link. Useful to protect the privacy of minors or anyone who prefers their name not to be public.' ?>
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="request_protected_toggle">
                    <button type="submit" class="btn btn-primary">
                        <?= $lang === 'it' ? 'Attiva modalità protetta' : 'Enable protected mode' ?>
                    </button>
                </form>
                <?php endif; ?>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 1rem;">
                    <?= $lang === 'it'
                        ? 'Il cambio di modalità richiede la conferma via email del presidente del circolo.'
                        : 'Changing the mode requires email confirmation from the club president.' ?>
                </p>
            </div>
        </div><!-- /.create-grid-2 -->

    </div>

    <?php endif; ?>

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
                <input type="hidden" name="entity_type" value="club">
                <input type="hidden" name="entity_id" value="<?= $clubId ?>">
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

