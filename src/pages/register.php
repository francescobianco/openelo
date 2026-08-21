<?php
/**
 * Chess School Platform - Instructor registration
 *
 * This is the main entry point of the new platform: an independent chess
 * instructor creates an account here and lands on their dashboard.
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/mail.php';
require_once SRC_PATH . '/utils.php';

// Already logged in? Nothing to do here.
if (isLoggedIn()) {
    header('Location: ?page=dashboard');
    exit;
}

$error = null;
$form = [
    'first_name'  => '',
    'last_name'   => '',
    'email'       => '',
    'school_name' => '',
    'city'        => '',
    'phone'       => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $_) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    try {
        checkCsrf();

        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');

        if ($password !== $confirm) {
            throw new Exception(__('auth_error_password_mismatch'));
        }

        $instructorId = registerInstructor($form + ['password' => $password]);

        // Email confirmation (the account is usable right away, the link only
        // flags the address as verified)
        $token = createInstructorToken($instructorId, AUTH_TOKEN_VERIFY);
        sendInstructorVerification($form['email'], $form['first_name'], $token);

        startInstructorSession($instructorId);
        setFlash('success', __('auth_registered'));

        header('Location: ?page=dashboard');
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container auth-container">
    <div class="auth-card">
        <h1><?= __('auth_register_title') ?></h1>
        <p class="auth-subtitle"><?= __('auth_register_subtitle') ?></p>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name"><?= __('auth_first_name') ?> *</label>
                    <input type="text" id="first_name" name="first_name" required
                           value="<?= htmlspecialchars($form['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label for="last_name"><?= __('auth_last_name') ?> *</label>
                    <input type="text" id="last_name" name="last_name" required
                           value="<?= htmlspecialchars($form['last_name']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email"><?= __('auth_email') ?> *</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($form['email']) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password"><?= __('auth_password') ?> *</label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <small class="form-hint"><?= __('auth_password_hint') ?></small>
                </div>
                <div class="form-group">
                    <label for="password_confirm"><?= __('auth_password_confirm') ?> *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                </div>
            </div>

            <div class="form-group">
                <label for="school_name"><?= __('auth_school_name') ?> <small>(<?= __('common_optional') ?>)</small></label>
                <input type="text" id="school_name" name="school_name"
                       value="<?= htmlspecialchars($form['school_name']) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city"><?= __('auth_city') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="text" id="city" name="city" value="<?= htmlspecialchars($form['city']) ?>">
                </div>
                <div class="form-group">
                    <label for="phone"><?= __('auth_phone') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($form['phone']) ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= __('auth_register_submit') ?></button>
        </form>

        <p class="auth-alt">
            <?= __('auth_have_account') ?> <a href="?page=login"><?= __('auth_login_submit') ?></a>
        </p>
    </div>
</div>
