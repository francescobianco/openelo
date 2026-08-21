<?php
/**
 * Chess School Platform - Instructor account settings
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/school.php';
require_once SRC_PATH . '/utils.php';

$instructor   = requireInstructor();
$instructorId = (int)$instructor['id'];

$error = null;
$db    = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        checkCsrf();

        switch ($action) {

            case 'update_profile':
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName  = trim($_POST['last_name'] ?? '');
                if ($firstName === '' || $lastName === '') {
                    throw new Exception(__('error_required'));
                }

                $stmt = $db->prepare("
                    UPDATE instructors
                    SET first_name = ?, last_name = ?, school_name = ?, city = ?, phone = ?, bio = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([
                    $firstName,
                    $lastName,
                    trim($_POST['school_name'] ?? '') ?: null,
                    trim($_POST['city'] ?? '') ?: null,
                    trim($_POST['phone'] ?? '') ?: null,
                    trim($_POST['bio'] ?? '') ?: null,
                    $instructorId,
                ]);

                setFlash('success', __('auth_profile_saved'));
                header('Location: ?page=account');
                exit;

            case 'change_password':
                $current = (string)($_POST['current_password'] ?? '');
                $new     = (string)($_POST['new_password'] ?? '');
                $confirm = (string)($_POST['password_confirm'] ?? '');

                if (!password_verify($current, $instructor['password_hash'])) {
                    throw new Exception(__('auth_error_current_password'));
                }
                if ($new !== $confirm) {
                    throw new Exception(__('auth_error_password_mismatch'));
                }

                setInstructorPassword($instructorId, $new);

                setFlash('success', __('auth_password_changed'));
                header('Location: ?page=account');
                exit;
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$stats = getSchoolStats($instructorId);
$flash = getFlash();
?>

<div class="container">
    <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1><?= __('auth_account_title') ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= htmlspecialchars($instructor['email']) ?></span>
                <?php if ($instructor['email_verified']): ?>
                <span class="badge badge-success">✓</span>
                <?php else: ?>
                <span class="badge badge-pending"><a href="?page=verify&resend=1"><?= __('auth_verify_resend') ?></a></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-actions">
            <a href="?page=logout" class="btn btn-secondary btn-sm"><?= __('nav_logout') ?></a>
        </div>
    </div>

    <div class="create-grid-2">
        <div class="create-section">
            <h2><?= __('auth_profile') ?></h2>
            <form method="POST" action="?page=account">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name"><?= __('auth_first_name') ?> *</label>
                        <input type="text" id="first_name" name="first_name" required value="<?= htmlspecialchars($instructor['first_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name"><?= __('auth_last_name') ?> *</label>
                        <input type="text" id="last_name" name="last_name" required value="<?= htmlspecialchars($instructor['last_name']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="school_name"><?= __('auth_school_name') ?></label>
                    <input type="text" id="school_name" name="school_name" value="<?= htmlspecialchars((string)$instructor['school_name']) ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city"><?= __('auth_city') ?></label>
                        <input type="text" id="city" name="city" value="<?= htmlspecialchars((string)$instructor['city']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone"><?= __('auth_phone') ?></label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars((string)$instructor['phone']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="bio">Bio <small>(<?= __('common_optional') ?>)</small></label>
                    <textarea id="bio" name="bio" rows="3"><?= htmlspecialchars((string)$instructor['bio']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary"><?= __('common_save') ?></button>
            </form>
        </div>

        <div class="create-section">
            <h2><?= __('auth_change_password') ?></h2>
            <form method="POST" action="?page=account">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label for="current_password"><?= __('auth_current_password') ?></label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password"><?= __('auth_new_password') ?></label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                    <small class="form-hint"><?= __('auth_password_hint') ?></small>
                </div>

                <div class="form-group">
                    <label for="password_confirm"><?= __('auth_password_confirm') ?></label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary"><?= __('auth_change_password') ?></button>
            </form>

            <div class="stats" style="margin-top: 2rem;">
                <div class="stat-card">
                    <div class="number"><?= $stats['classes'] ?></div>
                    <div class="label"><?= __('dash_active_classes') ?></div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $stats['students'] ?></div>
                    <div class="label"><?= __('dash_total_students') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
