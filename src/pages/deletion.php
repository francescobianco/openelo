<?php
/**
 * OpenElo - Deletion Request Handler
 */

require_once SRC_PATH . '/mail.php';

$db = Database::get();

$requestId = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'view';
$message = null;
$messageType = null;

// Handle deletion request creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_deletion'])) {
    try {
        $entityType = $_POST['entity_type'] ?? '';
        $entityId = (int)($_POST['entity_id'] ?? 0);
        $requesterEmail = trim($_POST['requester_email'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if (empty($entityType) || !$entityId || empty($requesterEmail) || empty($reason)) {
            throw new Exception(__('error_required'));
        }

        if (!filter_var($requesterEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception(__('error_email'));
        }

        // Create deletion request with token
        $deletionToken = bin2hex(random_bytes(32));
        $stmt = $db->prepare("
            INSERT INTO deletion_requests (entity_type, entity_id, requester_email, reason, token)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$entityType, $entityId, $requesterEmail, $reason, $deletionToken]);
        $newRequestId = $db->lastInsertId();

        // Get entity details and send notifications
        $entityName = '';
        $recipients = [ADMIN_EMAIL];

        switch ($entityType) {
            case 'circuit':
                $stmt = $db->prepare("SELECT name, owner_email FROM circuits WHERE id = ?");
                $stmt->execute([$entityId]);
                $entity = $stmt->fetch();
                if ($entity) {
                    $entityName = $entity['name'];
                    $recipients[] = $entity['owner_email'];
                }
                break;

            case 'club':
                $stmt = $db->prepare("SELECT name, president_email FROM clubs WHERE id = ?");
                $stmt->execute([$entityId]);
                $entity = $stmt->fetch();
                if ($entity) {
                    $entityName = $entity['name'];
                    $recipients[] = $entity['president_email'];
                }
                break;

            case 'player':
                $stmt = $db->prepare("SELECT p.first_name, p.last_name, p.email, c.president_email
                    FROM players p
                    JOIN clubs c ON c.id = p.club_id
                    WHERE p.id = ?");
                $stmt->execute([$entityId]);
                $entity = $stmt->fetch();
                if ($entity) {
                    $entityName = $entity['first_name'] . ' ' . $entity['last_name'];
                    $recipients[] = $entity['email'];
                    $recipients[] = $entity['president_email'];
                }
                break;

            case 'match':
                $stmt = $db->prepare("SELECT m.*,
                    pw.first_name as white_first, pw.last_name as white_last, pw.email as white_email,
                    pb.first_name as black_first, pb.last_name as black_last, pb.email as black_email,
                    c.owner_email, cl.president_email
                    FROM matches m
                    JOIN players pw ON pw.id = m.white_player_id
                    JOIN players pb ON pb.id = m.black_player_id
                    JOIN circuits c ON c.id = m.circuit_id
                    JOIN clubs cl ON cl.id = pw.club_id
                    WHERE m.id = ?");
                $stmt->execute([$entityId]);
                $entity = $stmt->fetch();
                if ($entity) {
                    $entityName = $entity['white_first'] . ' ' . $entity['white_last'] . ' vs ' .
                                  $entity['black_first'] . ' ' . $entity['black_last'];
                    $recipients[] = $entity['white_email'];
                    $recipients[] = $entity['black_email'];
                    $recipients[] = $entity['president_email'];
                    $recipients[] = $entity['owner_email'];
                }
                break;
        }

        // Send notifications to all recipients
        $recipients = array_unique($recipients);
        foreach ($recipients as $recipient) {
            sendDeletionRequest($recipient, $entityType, $entityName, $requesterEmail, $reason, $newRequestId, $deletionToken);
        }

        $message = $lang === 'it'
            ? 'Richiesta di eliminazione inviata. Riceverai una notifica quando verrà processata.'
            : 'Deletion request sent. You will be notified when it is processed.';
        $messageType = 'success';

    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Validate token
$token = $_GET['token'] ?? '';

// Handle approval/rejection
if ($requestId && $action !== 'view') {
    $stmt = $db->prepare("SELECT * FROM deletion_requests WHERE id = ? AND token = ?");
    $stmt->execute([$requestId, $token]);
    $request = $stmt->fetch();

    if (!$request) {
        $message = $lang === 'it' ? 'Token non valido o richiesta non trovata.' : 'Invalid token or request not found.';
        $messageType = 'error';
    } elseif ($request['status'] === 'pending') {
        try {
            if ($action === 'approve') {
                // Soft delete the entity
                $now = date('Y-m-d H:i:s');
                $table = $request['entity_type'] === 'circuit' ? 'circuits' :
                         ($request['entity_type'] === 'club' ? 'clubs' :
                         ($request['entity_type'] === 'player' ? 'players' : 'matches'));

                $stmt = $db->prepare("UPDATE $table SET deleted_at = ? WHERE id = ?");
                $stmt->execute([$now, $request['entity_id']]);

                // Mark request as approved
                $stmt = $db->prepare("UPDATE deletion_requests SET status = 'approved' WHERE id = ?");
                $stmt->execute([$requestId]);

                $message = $lang === 'it'
                    ? 'Richiesta approvata. L\'entità è stata disattivata.'
                    : 'Request approved. The entity has been deactivated.';
                $messageType = 'success';

            } elseif ($action === 'reject') {
                $stmt = $db->prepare("UPDATE deletion_requests SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$requestId]);

                $message = $lang === 'it'
                    ? 'Richiesta rifiutata.'
                    : 'Request rejected.';
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Show request details if viewing (requires valid token)
$request = null;
if ($requestId && $token) {
    $stmt = $db->prepare("SELECT * FROM deletion_requests WHERE id = ? AND token = ?");
    $stmt->execute([$requestId, $token]);
    $request = $stmt->fetch();
}
?>

<div class="container">
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <?= $message ?>
    </div>
    <?php endif; ?>

    <?php if ($request): ?>
    <div class="card" style="max-width: 600px; margin: 2rem auto;">
        <h2><?= $lang === 'it' ? 'Richiesta di Eliminazione' : 'Deletion Request' ?></h2>

        <div style="margin: 1rem 0;">
            <strong><?= $lang === 'it' ? 'Tipo:' : 'Type:' ?></strong>
            <?= htmlspecialchars($request['entity_type']) ?><br>

            <strong><?= $lang === 'it' ? 'ID Entità:' : 'Entity ID:' ?></strong>
            <?= $request['entity_id'] ?><br>

            <strong><?= $lang === 'it' ? 'Richiedente:' : 'Requester:' ?></strong>
            <?= htmlspecialchars($request['requester_email']) ?><br>

            <strong><?= $lang === 'it' ? 'Motivo:' : 'Reason:' ?></strong>
            <?= htmlspecialchars($request['reason']) ?><br>

            <strong><?= $lang === 'it' ? 'Stato:' : 'Status:' ?></strong>
            <span class="badge badge-<?= $request['status'] === 'approved' ? 'success' : ($request['status'] === 'rejected' ? 'error' : 'warning') ?>">
                <?= htmlspecialchars($request['status']) ?>
            </span><br>

            <strong><?= $lang === 'it' ? 'Data:' : 'Date:' ?></strong>
            <?= date('d/m/Y H:i', strtotime($request['created_at'])) ?>
        </div>

        <?php if ($request['status'] === 'pending'): ?>
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <a href="?page=deletion&id=<?= $requestId ?>&token=<?= htmlspecialchars($token) ?>&action=approve" class="btn btn-primary" style="flex: 1;">
                <?= $lang === 'it' ? 'Approva Eliminazione' : 'Approve Deletion' ?>
            </a>
            <a href="?page=deletion&id=<?= $requestId ?>&token=<?= htmlspecialchars($token) ?>&action=reject" class="btn btn-secondary" style="flex: 1;">
                <?= $lang === 'it' ? 'Rifiuta' : 'Reject' ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif ($requestId): ?>
    <p style="text-align: center; color: var(--text-secondary);">
        <?= $lang === 'it' ? 'Richiesta non trovata.' : 'Request not found.' ?>
    </p>
    <?php endif; ?>
</div>
