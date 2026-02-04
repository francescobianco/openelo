<?php
/**
 * OpenElo - Confirmation Handler
 */

require_once SRC_PATH . '/elo.php';

$db = Database::get();

$token = $_GET['token'] ?? '';
$message = null;
$messageType = null;
$redirectUrl = '?page=home';

if (empty($token)) {
    $message = __('confirm_error');
    $messageType = 'error';
} else {
    $confirmation = verifyConfirmation($token);

    if (!$confirmation) {
        $message = __('confirm_error');
        $messageType = 'error';
    } else {
        try {
            switch ($confirmation['type']) {
                case 'circuit':
                    $stmt = $db->prepare("UPDATE circuits SET confirmed = 1 WHERE id = ?");
                    $stmt->execute([$confirmation['target_id']]);
                    $message = __('confirm_success');
                    $messageType = 'success';
                    $redirectUrl = '?page=circuit&id=' . $confirmation['target_id'];
                    break;

                case 'club_president':
                    // President confirms club creation
                    $membershipId = $confirmation['target_id'];

                    // Get membership and club
                    $stmt = $db->prepare("
                        SELECT cc.*, c.id as club_id FROM circuit_clubs cc
                        JOIN clubs c ON c.id = cc.club_id
                        WHERE cc.id = ?
                    ");
                    $stmt->execute([$membershipId]);
                    $membership = $stmt->fetch();

                    if ($membership) {
                        // Mark club as president confirmed
                        $stmt = $db->prepare("UPDATE clubs SET president_confirmed = 1 WHERE id = ?");
                        $stmt->execute([$membership['club_id']]);

                        // Mark membership as club confirmed
                        $stmt = $db->prepare("UPDATE circuit_clubs SET club_confirmed = 1 WHERE id = ?");
                        $stmt->execute([$membershipId]);

                        // Check if both confirmed
                        $stmt = $db->prepare("SELECT * FROM circuit_clubs WHERE id = ?");
                        $stmt->execute([$membershipId]);
                        $updated = $stmt->fetch();

                        if ($updated['club_confirmed'] && $updated['circuit_confirmed']) {
                            $message = __('confirm_success') . ' ' . __('status_active');
                        } else {
                            $message = __('confirm_success') . ' ' . __('status_pending_circuit');
                        }
                    }
                    $messageType = 'success';
                    $redirectUrl = '?page=circuits';
                    break;

                case 'club_circuit':
                    // Circuit manager confirms club membership
                    $membershipId = $confirmation['target_id'];

                    $stmt = $db->prepare("UPDATE circuit_clubs SET circuit_confirmed = 1 WHERE id = ?");
                    $stmt->execute([$membershipId]);

                    // Check if both confirmed
                    $stmt = $db->prepare("SELECT * FROM circuit_clubs WHERE id = ?");
                    $stmt->execute([$membershipId]);
                    $updated = $stmt->fetch();

                    if ($updated['club_confirmed'] && $updated['circuit_confirmed']) {
                        $message = __('confirm_success') . ' ' . __('status_active');
                    } else {
                        $message = __('confirm_success') . ' ' . __('status_pending_president');
                    }
                    $messageType = 'success';
                    $redirectUrl = '?page=circuit&id=' . $updated['circuit_id'] . '&tab=clubs';
                    break;

                case 'membership_club':
                    // President confirms joining another circuit
                    $membershipId = $confirmation['target_id'];
                    $stmt = $db->prepare("UPDATE circuit_clubs SET club_confirmed = 1 WHERE id = ?");
                    $stmt->execute([$membershipId]);

                    $stmt = $db->prepare("SELECT * FROM circuit_clubs WHERE id = ?");
                    $stmt->execute([$membershipId]);
                    $updated = $stmt->fetch();

                    if ($updated['club_confirmed'] && $updated['circuit_confirmed']) {
                        $message = __('confirm_success') . ' ' . __('status_active');
                    } else {
                        $message = __('confirm_success') . ' ' . __('status_pending_circuit');
                    }
                    $messageType = 'success';
                    $redirectUrl = '?page=club&id=' . $updated['club_id'];
                    break;

                case 'membership_circuit':
                    // Circuit manager confirms club joining
                    $membershipId = $confirmation['target_id'];
                    $stmt = $db->prepare("UPDATE circuit_clubs SET circuit_confirmed = 1 WHERE id = ?");
                    $stmt->execute([$membershipId]);

                    $stmt = $db->prepare("SELECT * FROM circuit_clubs WHERE id = ?");
                    $stmt->execute([$membershipId]);
                    $updated = $stmt->fetch();

                    if ($updated['club_confirmed'] && $updated['circuit_confirmed']) {
                        $message = __('confirm_success') . ' ' . __('status_active');
                    } else {
                        $message = __('confirm_success') . ' ' . __('status_pending_president');
                    }
                    $messageType = 'success';
                    $redirectUrl = '?page=circuit&id=' . $updated['circuit_id'] . '&tab=clubs';
                    break;

                case 'player_self':
                    // Player confirms their registration
                    $playerId = $confirmation['target_id'];
                    $stmt = $db->prepare("UPDATE players SET player_confirmed = 1 WHERE id = ?");
                    $stmt->execute([$playerId]);

                    // Check if both confirmed
                    $stmt = $db->prepare("SELECT * FROM players WHERE id = ?");
                    $stmt->execute([$playerId]);
                    $player = $stmt->fetch();

                    if ($player['player_confirmed'] && $player['president_confirmed']) {
                        // Mark as fully confirmed and add to circuits
                        $stmt = $db->prepare("UPDATE players SET confirmed = 1 WHERE id = ?");
                        $stmt->execute([$playerId]);
                        addPlayerToClubCircuits($playerId, $player['club_id']);
                        $message = __('confirm_success') . ' ' . __('status_active');
                    } else {
                        $message = __('confirm_success') . ' ' . __('status_pending_president');
                    }
                    $messageType = 'success';
                    break;

                case 'player_president':
                    // President confirms player registration
                    $playerId = $confirmation['target_id'];
                    $stmt = $db->prepare("UPDATE players SET president_confirmed = 1 WHERE id = ?");
                    $stmt->execute([$playerId]);

                    // Check if both confirmed
                    $stmt = $db->prepare("SELECT * FROM players WHERE id = ?");
                    $stmt->execute([$playerId]);
                    $player = $stmt->fetch();

                    if ($player['player_confirmed'] && $player['president_confirmed']) {
                        // Mark as fully confirmed and add to circuits
                        $stmt = $db->prepare("UPDATE players SET confirmed = 1 WHERE id = ?");
                        $stmt->execute([$playerId]);
                        addPlayerToClubCircuits($playerId, $player['club_id']);
                        $message = __('confirm_success') . ' ' . __('status_active');
                    } else {
                        $message = __('confirm_success') . ' ' . __('status_pending_player');
                    }
                    $messageType = 'success';
                    break;

                case 'transfer_player':
                    // Player confirms transfer
                    $transferId = $confirmation['target_id'];
                    $stmt = $db->prepare("UPDATE club_transfers SET player_confirmed = 1 WHERE id = ?");
                    $stmt->execute([$transferId]);

                    // Check if both confirmed
                    $stmt = $db->prepare("SELECT * FROM club_transfers WHERE id = ?");
                    $stmt->execute([$transferId]);
                    $transfer = $stmt->fetch();

                    if ($transfer['player_confirmed'] && $transfer['president_confirmed']) {
                        // Execute transfer
                        $stmt = $db->prepare("UPDATE players SET club_id = ? WHERE id = ?");
                        $stmt->execute([$transfer['to_club_id'], $transfer['player_id']]);
                        $stmt = $db->prepare("UPDATE club_transfers SET completed = 1 WHERE id = ?");
                        $stmt->execute([$transferId]);
                        // Add to new club's circuits
                        addPlayerToClubCircuits($transfer['player_id'], $transfer['to_club_id']);
                        $message = __('confirm_success') . ' ' . __('status_active');
                    } else {
                        $message = __('confirm_success') . ' ' . __('status_pending_president');
                    }
                    $messageType = 'success';
                    break;

                case 'transfer_president':
                    // New president confirms transfer
                    $transferId = $confirmation['target_id'];
                    $stmt = $db->prepare("UPDATE club_transfers SET president_confirmed = 1 WHERE id = ?");
                    $stmt->execute([$transferId]);

                    // Check if both confirmed
                    $stmt = $db->prepare("SELECT * FROM club_transfers WHERE id = ?");
                    $stmt->execute([$transferId]);
                    $transfer = $stmt->fetch();

                    if ($transfer['player_confirmed'] && $transfer['president_confirmed']) {
                        // Execute transfer
                        $stmt = $db->prepare("UPDATE players SET club_id = ? WHERE id = ?");
                        $stmt->execute([$transfer['to_club_id'], $transfer['player_id']]);
                        $stmt = $db->prepare("UPDATE club_transfers SET completed = 1 WHERE id = ?");
                        $stmt->execute([$transferId]);
                        // Add to new club's circuits
                        addPlayerToClubCircuits($transfer['player_id'], $transfer['to_club_id']);
                        $message = __('confirm_success') . ' ' . __('status_active');
                    } else {
                        $message = __('confirm_success') . ' ' . __('status_pending_player');
                    }
                    $messageType = 'success';
                    break;

                case 'match':
                    $matchId = $confirmation['target_id'];
                    $role = $confirmation['role'];

                    // Update the appropriate confirmation flag
                    switch ($role) {
                        case 'white':
                            $stmt = $db->prepare("UPDATE matches SET white_confirmed = 1 WHERE id = ?");
                            break;
                        case 'black':
                            $stmt = $db->prepare("UPDATE matches SET black_confirmed = 1 WHERE id = ?");
                            break;
                        case 'president':
                            $stmt = $db->prepare("UPDATE matches SET president_confirmed = 1 WHERE id = ?");
                            break;
                    }
                    $stmt->execute([$matchId]);

                    // Check if all confirmations are in
                    $stmt = $db->prepare("SELECT * FROM matches WHERE id = ?");
                    $stmt->execute([$matchId]);
                    $match = $stmt->fetch();

                    if ($match['white_confirmed'] && $match['black_confirmed'] && $match['president_confirmed']) {
                        // All confirmed - apply rating change
                        applyRatingChange($matchId);
                        $message = $lang === 'it'
                            ? 'Confermato! La partita è stata registrata e i rating aggiornati.'
                            : 'Confirmed! Match has been recorded and ratings updated.';
                    } else {
                        $pending = [];
                        if (!$match['white_confirmed']) $pending[] = $lang === 'it' ? 'Bianco' : 'White';
                        if (!$match['black_confirmed']) $pending[] = $lang === 'it' ? 'Nero' : 'Black';
                        if (!$match['president_confirmed']) $pending[] = $lang === 'it' ? 'Presidente' : 'President';

                        $message = __('confirm_success') . ' ' . __('match_pending') . ': ' . implode(', ', $pending);
                    }

                    $messageType = 'success';
                    $redirectUrl = '?page=circuit&id=' . $match['circuit_id'];
                    break;

                default:
                    throw new Exception('Unknown confirmation type');
            }
        } catch (Exception $e) {
            $message = __('error_generic');
            $messageType = 'error';
        }
    }
}
?>

<?php if ($messageType === 'success'): ?>
<meta http-equiv="refresh" content="3;url=<?= $redirectUrl ?>">
<?php endif; ?>

<div class="container" style="text-align: center; padding-top: 4rem;">
    <div class="card" style="max-width: 500px; margin: 0 auto;">
        <div class="alert alert-<?= $messageType ?>" style="margin-bottom: 1rem;">
            <?= htmlspecialchars($message) ?>
        </div>

        <?php if ($messageType === 'success'): ?>
        <p style="color: var(--text-secondary);">
            <?= $lang === 'it' ? 'Reindirizzamento...' : 'Redirecting...' ?>
        </p>
        <?php endif; ?>

        <p style="margin-top: 1rem;">
            <a href="<?= $redirectUrl ?>" class="btn btn-primary">
                <?= $lang === 'it' ? 'Continua' : 'Continue' ?>
            </a>
        </p>
    </div>
</div>
