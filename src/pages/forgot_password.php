<?php
/**
 * Chess School Platform - Request a password reset link
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/mail.php';
require_once SRC_PATH . '/utils.php';

$message = null;
$error   = null;
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    try {
        checkCsrf();

        $instructor = findInstructorByEmail($email);
        if ($instructor) {
            $token = createInstructorToken((int)$instructor['id'], AUTH_TOKEN_RESET);
            sendInstructorPasswordReset($instructor['email'], $instructor['first_name'], $token);
        }

        // Always the same answer: never reveal whether an email is registered.
        $message = __('auth_forgot_sent');

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container auth-container">
    <div class="auth-card">
        <h1><?= __('auth_forgot_title') ?></h1>
        <p class="auth-subtitle"><?= __('auth_forgot_subtitle') ?></p>

        <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email"><?= __('auth_email') ?></label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= __('auth_forgot_submit') ?></button>
        </form>

        <p class="auth-alt"><a href="?page=login"><?= __('common_back') ?></a></p>
    </div>
</div>
