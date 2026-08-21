<?php
/**
 * Chess School Platform - Instructor email verification
 *
 * Consumes the token sent by sendInstructorVerification(). Also handles the
 * "resend confirmation email" action from the dashboard banner.
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/mail.php';
require_once SRC_PATH . '/utils.php';

// Resend flow (only for a logged-in, still unverified instructor)
if (($_GET['resend'] ?? '') === '1') {
    $instructor = requireInstructor();

    if (!$instructor['email_verified']) {
        $token = createInstructorToken((int)$instructor['id'], AUTH_TOKEN_VERIFY);
        sendInstructorVerification($instructor['email'], $instructor['first_name'], $token);
        setFlash('success', __('auth_verify_sent'));
    }

    header('Location: ?page=dashboard');
    exit;
}

$token = trim($_GET['token'] ?? '');
$instructor = $token !== '' ? consumeInstructorToken($token, AUTH_TOKEN_VERIFY) : null;

if ($instructor) {
    markInstructorVerified((int)$instructor['id']);

    // Confirming the email also logs the instructor in — one click, done.
    startInstructorSession((int)$instructor['id']);
    setFlash('success', __('auth_verify_ok'));

    header('Location: ?page=dashboard');
    exit;
}
?>

<div class="container auth-container">
    <div class="auth-card">
        <h1><?= __('auth_verify_failed') ?></h1>
        <p class="auth-subtitle"><?= __('auth_verify_resend') ?></p>
        <a href="?page=login" class="btn btn-primary btn-block"><?= __('auth_login_submit') ?></a>
    </div>
</div>
