<?php
/**
 * Chess School Platform - Single class
 *
 * View / edit a class, enroll students into it, remove them.
 * Everything is scoped to the logged-in instructor.
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/school.php';
require_once SRC_PATH . '/mail.php';
require_once SRC_PATH . '/utils.php';

$instructor   = requireInstructor();
$instructorId = (int)$instructor['id'];

$classId = (int)($_GET['id'] ?? ($_POST['class_id'] ?? 0));
$class   = $classId ? getClass($classId, $instructorId) : null;

if (!$class) {
    setFlash('error', __('error_not_found'));
    header('Location: ?page=classes');
    exit;
}

$error   = null;
$editing = ($_GET['action'] ?? '') === 'edit';
$tab     = $_GET['tab'] ?? 'class';
$tabs    = ['class', 'register', 'lessons', 'calendar'];
if (!in_array($tab, $tabs, true) || $editing) {
    $tab = 'class';
}
$db      = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        checkCsrf();

        switch ($action) {

            case 'update_class':
                $name = trim($_POST['name'] ?? '');
                if ($name === '') {
                    throw new Exception(__('error_required'));
                }
                $maxStudents = trim($_POST['max_students'] ?? '');

                $stmt = $db->prepare("
                    UPDATE classes
                    SET name = ?, description = ?, level = ?, location = ?, schedule = ?,
                        start_date = ?, end_date = ?, max_students = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND instructor_id = ?
                ");
                $stmt->execute([
                    $name,
                    trim($_POST['description'] ?? '') ?: null,
                    trim($_POST['level'] ?? '') ?: 'beginner',
                    trim($_POST['location'] ?? '') ?: null,
                    trim($_POST['schedule'] ?? '') ?: null,
                    trim($_POST['start_date'] ?? '') ?: null,
                    trim($_POST['end_date'] ?? '') ?: null,
                    $maxStudents !== '' ? (int)$maxStudents : null,
                    $classId,
                    $instructorId,
                ]);

                setFlash('success', __('class_updated'));
                header('Location: ?page=class_detail&id=' . $classId);
                exit;

            case 'enroll_student':
                $studentId = (int)($_POST['student_id'] ?? 0);
                enrollStudent($classId, $studentId, $instructorId);

                setFlash('success', __('class_enrolled'));
                header('Location: ?page=class_detail&id=' . $classId);
                exit;

            case 'create_and_enroll':
                // Quick add: create the student and enroll them in one step
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName  = trim($_POST['last_name'] ?? '');
                if ($firstName === '' || $lastName === '') {
                    throw new Exception(__('error_required'));
                }

                $stmt = $db->prepare("
                    INSERT INTO students (instructor_id, first_name, last_name, email, level, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)
                ");
                $stmt->execute([
                    $instructorId,
                    $firstName,
                    $lastName,
                    trim($_POST['email'] ?? '') ?: null,
                    $class['level'] ?: 'beginner',
                ]);
                enrollStudent($classId, (int)$db->lastInsertId(), $instructorId);

                setFlash('success', __('class_enrolled'));
                header('Location: ?page=class_detail&id=' . $classId);
                exit;

            case 'unenroll_student':
                unenrollStudent($classId, (int)($_POST['student_id'] ?? 0), $instructorId);

                setFlash('success', __('class_unenrolled'));
                header('Location: ?page=class_detail&id=' . $classId);
                exit;

            case 'toggle_status':
                $newStatus = $class['status'] === 'active' ? 'archived' : 'active';
                $stmt = $db->prepare("UPDATE classes SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND instructor_id = ?");
                $stmt->execute([$newStatus, $classId, $instructorId]);

                setFlash('success', $newStatus === 'archived' ? __('class_archived') : __('class_restored'));
                header('Location: ?page=class_detail&id=' . $classId);
                exit;

            case 'delete_class':
                // Soft delete: nothing is ever really removed
                $stmt = $db->prepare("UPDATE classes SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND instructor_id = ?");
                $stmt->execute([$classId, $instructorId]);

                setFlash('success', __('class_deleted'));
                header('Location: ?page=classes');
                exit;
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$students     = getClassStudents($classId);
$available    = getStudentsNotInClass($instructorId, $classId);
$levels       = schoolLevels();
$flash        = getFlash();
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
            <h1>
                <?= htmlspecialchars($class['name']) ?>
                <?php if ($class['status'] === 'archived'): ?>
                <span class="badge badge-warning"><?= __('common_archived') ?></span>
                <?php endif; ?>
            </h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= levelLabel($class['level']) ?></span>
                <span><?= count($students) ?> <?= __('nav_students') ?></span>
                <?php if (!empty($class['schedule'])): ?><span><?= htmlspecialchars($class['schedule']) ?></span><?php endif; ?>
                <?php if (!empty($class['location'])): ?><span><?= htmlspecialchars($class['location']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="header-actions">
            <a href="?page=classes" class="btn btn-secondary btn-sm"><?= __('common_back') ?></a>
            <?php if (!$editing): ?>
            <a href="?page=class_detail&id=<?= $classId ?>&action=edit" class="btn btn-secondary btn-sm"><?= __('common_edit') ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="tabs">
        <a href="?page=class_detail&id=<?= $classId ?>&tab=class" class="tab <?= $tab === 'class' ? 'active' : '' ?>"><?= __('class_tab_class') ?></a>
        <a href="?page=class_detail&id=<?= $classId ?>&tab=register" class="tab <?= $tab === 'register' ? 'active' : '' ?>"><?= __('class_tab_register') ?></a>
        <a href="?page=class_detail&id=<?= $classId ?>&tab=lessons" class="tab <?= $tab === 'lessons' ? 'active' : '' ?>"><?= __('class_tab_lessons') ?></a>
        <a href="?page=class_detail&id=<?= $classId ?>&tab=calendar" class="tab <?= $tab === 'calendar' ? 'active' : '' ?>"><?= __('class_tab_calendar') ?></a>
    </div>

    <?php if ($tab !== 'class'): ?>
    <div class="card empty-state">
        <h2><?= __('class_tab_' . $tab) ?></h2>
        <p><?= __('class_tab_coming_soon') ?></p>
    </div>
    <?php endif; ?>

    <?php if ($tab === 'class'): ?>
    <?php if (!empty($class['description']) && !$editing): ?>
    <div class="card">
        <p><?= nl2br(htmlspecialchars($class['description'])) ?></p>
    </div>
    <?php endif; ?>

    <?php if ($editing): ?>
    <!-- ── Edit class ─────────────────────────────────────────────── -->
    <div class="create-section">
        <h2><?= __('class_edit') ?></h2>
        <form method="POST" action="?page=class_detail&id=<?= $classId ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_class">
            <input type="hidden" name="class_id" value="<?= $classId ?>">

            <div class="form-group">
                <label for="name"><?= __('class_name') ?> *</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($class['name']) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="level"><?= __('class_level') ?></label>
                    <select id="level" name="level">
                        <?php foreach ($levels as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $class['level'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="max_students"><?= __('class_max_students') ?></label>
                    <input type="number" id="max_students" name="max_students" min="1" value="<?= htmlspecialchars((string)$class['max_students']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="location"><?= __('class_location') ?></label>
                    <input type="text" id="location" name="location" value="<?= htmlspecialchars((string)$class['location']) ?>">
                </div>
                <div class="form-group">
                    <label for="schedule"><?= __('class_schedule') ?></label>
                    <input type="text" id="schedule" name="schedule" value="<?= htmlspecialchars((string)$class['schedule']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="start_date"><?= __('class_start_date') ?></label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars((string)$class['start_date']) ?>">
                </div>
                <div class="form-group">
                    <label for="end_date"><?= __('class_end_date') ?></label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars((string)$class['end_date']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="description"><?= __('class_description') ?></label>
                <textarea id="description" name="description" rows="3"><?= htmlspecialchars((string)$class['description']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?= __('common_save') ?></button>
            <a href="?page=class_detail&id=<?= $classId ?>" class="btn btn-secondary"><?= __('common_cancel') ?></a>
        </form>

        <div class="danger-zone">
            <form method="POST" action="?page=class_detail&id=<?= $classId ?>" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="class_id" value="<?= $classId ?>">
                <button type="submit" class="btn btn-secondary btn-sm">
                    <?= $class['status'] === 'active' ? __('class_archive') : __('class_restore') ?>
                </button>
            </form>
            <form method="POST" action="?page=class_detail&id=<?= $classId ?>" style="display:inline;"
                  onsubmit="return confirm('<?= __('common_confirm_delete') ?>');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_class">
                <input type="hidden" name="class_id" value="<?= $classId ?>">
                <button type="submit" class="btn btn-danger btn-sm"><?= __('common_delete') ?></button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Students of this class ─────────────────────────────────── -->
    <section class="circuits-section">
        <div class="section-header">
            <h2><?= __('class_students') ?></h2>
        </div>

        <?php if (empty($students)): ?>
        <p class="empty-state"><?= __('class_no_students') ?></p>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= __('student_last_name') ?></th>
                        <th><?= __('student_level') ?></th>
                        <th><?= __('student_email') ?></th>
                        <th><?= __('common_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><a href="?page=student_detail&id=<?= (int)$student['id'] ?>"><?= htmlspecialchars(studentName($student)) ?></a></td>
                        <td><?= levelLabel($student['level']) ?></td>
                        <td><?= htmlspecialchars((string)$student['email']) ?></td>
                        <td>
                            <form method="POST" action="?page=class_detail&id=<?= $classId ?>" style="display:inline;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="unenroll_student">
                                <input type="hidden" name="class_id" value="<?= $classId ?>">
                                <input type="hidden" name="student_id" value="<?= (int)$student['id'] ?>">
                                <button type="submit" class="btn btn-secondary btn-sm"><?= __('common_delete') ?></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── Add students ───────────────────────────────────────────── -->
    <div class="create-grid-2">
        <div class="create-section">
            <h2><?= __('class_add_student') ?></h2>
            <form method="POST" action="?page=class_detail&id=<?= $classId ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_and_enroll">
                <input type="hidden" name="class_id" value="<?= $classId ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name"><?= __('student_first_name') ?> *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name"><?= __('student_last_name') ?> *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email"><?= __('student_email') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="email" id="email" name="email">
                </div>

                <button type="submit" class="btn btn-primary"><?= __('student_create') ?></button>
            </form>
        </div>

        <div class="create-section">
            <h2><?= __('class_enroll_existing') ?></h2>
            <?php if (empty($available)): ?>
            <p class="empty-state"><?= __('student_none') ?></p>
            <?php else: ?>
            <form method="POST" action="?page=class_detail&id=<?= $classId ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="enroll_student">
                <input type="hidden" name="class_id" value="<?= $classId ?>">

                <div class="form-group">
                    <label for="student_id"><?= __('nav_students') ?></label>
                    <select id="student_id" name="student_id" required>
                        <option value=""><?= __('form_select') ?></option>
                        <?php foreach ($available as $student): ?>
                        <option value="<?= (int)$student['id'] ?>"><?= htmlspecialchars(studentName($student)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><?= __('class_add_student') ?></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
