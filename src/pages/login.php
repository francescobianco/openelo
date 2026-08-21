<?php
/**
 * Chess School Platform - Instructor login
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/utils.php';

if (isLoggedIn()) {
    header('Location: ?page=dashboard');
    exit;
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    try {
        checkCsrf();
        $instructor = loginInstructor($email, (string)($_POST['password'] ?? ''));

        setFlash('success', __('auth_welcome_back') . ', ' . $instructor['first_name'] . '!');

        $redirect = $_SESSION['redirect_after_login'] ?? '?page=dashboard';
        unset($_SESSION['redirect_after_login']);

        header('Location: ' . $redirect);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$flash = getFlash();
?>

<div class="container auth-container">
    <div class="auth-card">
        <h1><?= __('auth_login_title') ?></h1>

        <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="email"><?= __('auth_email') ?></label>
                <input type="email" id="email" name="email" required autofocus
                       value="<?= htmlspecialchars($email) ?>">
            </div>

            <div class="form-group">
                <label for="password"><?= __('auth_password') ?></label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><?= __('auth_login_submit') ?></button>
        </form>

        <p class="auth-alt">
            <a href="?page=forgot_password"><?= __('auth_forgot_password') ?></a>
        </p>
        <p class="auth-alt">
            <?= __('auth_no_account') ?> <a href="?page=register"><?= __('auth_register_submit') ?></a>
        </p>
    </div>
</div>
