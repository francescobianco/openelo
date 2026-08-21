<?php
/**
 * Chess School Platform - Set a new password from a reset link
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/utils.php';

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$error = null;
$done  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        checkCsrf();

        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');

        if ($password !== $confirm) {
            throw new Exception(__('auth_error_password_mismatch'));
        }

        // The token is consumed here: one reset link, one use.
        $instructor = $token !== '' ? consumeInstructorToken($token, AUTH_TOKEN_RESET) : null;
        if (!$instructor) {
            throw new Exception(__('auth_reset_invalid'));
        }

        setInstructorPassword((int)$instructor['id'], $password);

        // A password reset also proves the address is reachable.
        markInstructorVerified((int)$instructor['id']);

        setFlash('success', __('auth_reset_done'));
        header('Location: ?page=login');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container auth-container">
    <div class="auth-card">
        <h1><?= __('auth_reset_title') ?></h1>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($token === ''): ?>
        <div class="alert alert-error"><?= __('auth_reset_invalid') ?></div>
        <p class="auth-alt"><a href="?page=forgot_password"><?= __('auth_forgot_submit') ?></a></p>
        <?php else: ?>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label for="password"><?= __('auth_new_password') ?></label>
                <input type="password" id="password" name="password" required minlength="8" autofocus>
                <small class="form-hint"><?= __('auth_password_hint') ?></small>
            </div>

            <div class="form-group">
                <label for="password_confirm"><?= __('auth_password_confirm') ?></label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= __('auth_reset_submit') ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>
