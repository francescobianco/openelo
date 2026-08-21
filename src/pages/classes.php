<?php
/**
 * Chess School Platform - Classes list + create
 *
 * ?page=classes                → list
 * ?page=classes&action=new     → create form
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/school.php';
require_once SRC_PATH . '/utils.php';

$instructor   = requireInstructor();
$instructorId = (int)$instructor['id'];

$showForm = ($_GET['action'] ?? '') === 'new';
$error    = null;
$form     = [
    'name' => '', 'description' => '', 'level' => 'beginner', 'location' => '',
    'schedule' => '', 'start_date' => '', 'end_date' => '', 'max_students' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_class') {
    foreach ($form as $key => $_) {
        $form[$key] = trim($_POST[$key] ?? '');
    }
    $showForm = true;

    try {
        checkCsrf();

        if ($form['name'] === '') {
            throw new Exception(__('error_required'));
        }

        $db = Database::get();
        $stmt = $db->prepare("
            INSERT INTO classes (instructor_id, name, description, level, location, schedule, start_date, end_date, max_students, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $instructorId,
            $form['name'],
            $form['description'] !== '' ? $form['description'] : null,
            $form['level'] !== '' ? $form['level'] : 'beginner',
            $form['location'] !== '' ? $form['location'] : null,
            $form['schedule'] !== '' ? $form['schedule'] : null,
            $form['start_date'] !== '' ? $form['start_date'] : null,
            $form['end_date'] !== '' ? $form['end_date'] : null,
            $form['max_students'] !== '' ? (int)$form['max_students'] : null,
        ]);
        $classId = (int)$db->lastInsertId();

        setFlash('success', __('class_created'));
        header('Location: ?page=class_detail&id=' . $classId);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$classes = getClasses($instructorId);
$levels  = schoolLevels();
$flash   = getFlash();
?>

<div class="container">
    <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h1><?= __('class_list_title') ?></h1>
        <div class="header-actions">
            <?php if (!$showForm): ?>
            <a href="?page=classes&action=new" class="btn btn-primary"><?= __('class_new') ?></a>
            <?php else: ?>
            <a href="?page=classes" class="btn btn-secondary"><?= __('common_cancel') ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($showForm): ?>
    <div class="create-section">
        <h2><?= __('class_create') ?></h2>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="?page=classes">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create_class">

            <div class="form-group">
                <label for="name"><?= __('class_name') ?> *</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($form['name']) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="level"><?= __('class_level') ?></label>
                    <select id="level" name="level">
                        <?php foreach ($levels as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $form['level'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="max_students"><?= __('class_max_students') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="number" id="max_students" name="max_students" min="1"
                           value="<?= htmlspecialchars($form['max_students']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="location"><?= __('class_location') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="text" id="location" name="location" value="<?= htmlspecialchars($form['location']) ?>">
                </div>
                <div class="form-group">
                    <label for="schedule"><?= __('class_schedule') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="text" id="schedule" name="schedule" placeholder="Lun 17:00 - 18:30"
                           value="<?= htmlspecialchars($form['schedule']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date"><?= __('class_start_date') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($form['start_date']) ?>">
                </div>
                <div class="form-group">
                    <label for="end_date"><?= __('class_end_date') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($form['end_date']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="description"><?= __('class_description') ?> <small>(<?= __('common_optional') ?>)</small></label>
                <textarea id="description" name="description" rows="3"><?= htmlspecialchars($form['description']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?= __('class_create') ?></button>
        </form>
    </div>
    <?php endif; ?>

    <?php if (empty($classes)): ?>
        <?php if (!$showForm): ?>
        <div class="empty-state">
            <div class="feature-icon">♟</div>
            <p><?= __('class_none') ?></p>
            <a href="?page=classes&action=new" class="btn btn-primary"><?= __('class_create') ?></a>
        </div>
        <?php endif; ?>
    <?php else: ?>
    <div class="circuits-grid">
        <?php foreach ($classes as $class): ?>
        <div class="circuit-card">
            <h3>
                <a href="?page=class_detail&id=<?= (int)$class['id'] ?>"><?= htmlspecialchars($class['name']) ?></a>
                <?php if ($class['status'] === 'archived'): ?>
                <span class="badge badge-warning"><?= __('common_archived') ?></span>
                <?php endif; ?>
            </h3>
            <div class="circuit-meta">
                <span><?= levelLabel($class['level']) ?></span>
                <span><?= (int)$class['student_count'] ?> <?= __('nav_students') ?></span>
            </div>
            <?php if (!empty($class['schedule']) || !empty($class['location'])): ?>
            <div class="circuit-date">
                <?= htmlspecialchars(trim($class['schedule'] . ' · ' . $class['location'], ' ·')) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
