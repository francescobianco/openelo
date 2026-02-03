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

                case 'club':
                    $stmt = $db->prepare("UPDATE clubs SET confirmed = 1 WHERE id = ?");
                    $stmt->execute([$confirmation['target_id']]);
                    $message = __('confirm_success');
                    $messageType = 'success';
                    $redirectUrl = '?page=circuits';
                    break;

                case 'membership':
                    $stmt = $db->prepare("UPDATE circuit_clubs SET confirmed = 1 WHERE id = ?");
                    $stmt->execute([$confirmation['target_id']]);

                    // Get circuit_id for redirect
                    $stmt = $db->prepare("SELECT circuit_id FROM circuit_clubs WHERE id = ?");
                    $stmt->execute([$confirmation['target_id']]);
                    $membership = $stmt->fetch();

                    $message = __('confirm_success');
                    $messageType = 'success';
                    $redirectUrl = '?page=circuit&id=' . $membership['circuit_id'] . '&tab=clubs';
                    break;

                case 'player':
                    $stmt = $db->prepare("UPDATE players SET confirmed = 1 WHERE id = ?");
                    $stmt->execute([$confirmation['target_id']]);
                    $message = __('confirm_success');
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
