<?php
/**
 * Chess School Platform - Single student
 *
 * View / edit a student, see and change the classes they attend.
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/school.php';
require_once SRC_PATH . '/utils.php';

$instructor   = requireInstructor();
$instructorId = (int)$instructor['id'];

$studentId = (int)($_GET['id'] ?? ($_POST['student_id'] ?? 0));
$student   = $studentId ? getStudent($studentId, $instructorId) : null;

if (!$student) {
    setFlash('error', __('error_not_found'));
    header('Location: ?page=students');
    exit;
}

$error   = null;
$editing = ($_GET['action'] ?? '') === 'edit';
$db      = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        checkCsrf();

        switch ($action) {

            case 'update_student':
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName  = trim($_POST['last_name'] ?? '');
                if ($firstName === '' || $lastName === '') {
                    throw new Exception(__('error_required'));
                }
                $email = trim($_POST['email'] ?? '');
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception(__('error_email'));
                }
                $rating = trim($_POST['rating'] ?? '');

                $stmt = $db->prepare("
                    UPDATE students
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, birth_date = ?, level = ?, rating = ?,
                        guardian_name = ?, guardian_email = ?, guardian_phone = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND instructor_id = ?
                ");
                $stmt->execute([
                    $firstName,
                    $lastName,
                    $email !== '' ? $email : null,
                    trim($_POST['phone'] ?? '') ?: null,
                    trim($_POST['birth_date'] ?? '') ?: null,
                    trim($_POST['level'] ?? '') ?: 'beginner',
                    $rating !== '' ? (int)$rating : null,
                    trim($_POST['guardian_name'] ?? '') ?: null,
                    trim($_POST['guardian_email'] ?? '') ?: null,
                    trim($_POST['guardian_phone'] ?? '') ?: null,
                    trim($_POST['notes'] ?? '') ?: null,
                    $studentId,
                    $instructorId,
                ]);

                setFlash('success', __('student_updated'));
                header('Location: ?page=student_detail&id=' . $studentId);
                exit;

            case 'enroll_class':
                enrollStudent((int)($_POST['class_id'] ?? 0), $studentId, $instructorId);

                setFlash('success', __('class_enrolled'));
                header('Location: ?page=student_detail&id=' . $studentId);
                exit;

            case 'unenroll_class':
                unenrollStudent((int)($_POST['class_id'] ?? 0), $studentId, $instructorId);

                setFlash('success', __('class_unenrolled'));
                header('Location: ?page=student_detail&id=' . $studentId);
                exit;

            case 'delete_student':
                // Soft delete: enrollments and history stay in place
                $stmt = $db->prepare("UPDATE students SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND instructor_id = ?");
                $stmt->execute([$studentId, $instructorId]);

                setFlash('success', __('student_deleted'));
                header('Location: ?page=students');
                exit;
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$studentClasses = getStudentClasses($studentId);
$enrolledIds    = array_column($studentClasses, 'id');
$otherClasses   = array_filter(getClasses($instructorId, 'active'), function($c) use ($enrolledIds) {
    return !in_array($c['id'], $enrolledIds);
});
$levels = schoolLevels();
$flash  = getFlash();
$age    = studentAge($student['birth_date']);
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
            <h1><?= htmlspecialchars(studentName($student)) ?></h1>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= levelLabel($student['level']) ?></span>
                <?php if ($age !== null): ?><span><?= $age ?> <?= __('student_age') ?></span><?php endif; ?>
                <?php if (!empty($student['rating'])): ?><span><?= __('student_rating') ?> <?= (int)$student['rating'] ?></span><?php endif; ?>
            </div>
        </div>
        <div class="header-actions">
            <a href="?page=students" class="btn btn-secondary btn-sm"><?= __('common_back') ?></a>
            <?php if (!$editing): ?>
            <a href="?page=student_detail&id=<?= $studentId ?>&action=edit" class="btn btn-secondary btn-sm"><?= __('common_edit') ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($editing): ?>
    <!-- ── Edit student ───────────────────────────────────────────── -->
    <div class="create-section">
        <h2><?= __('student_edit') ?></h2>
        <form method="POST" action="?page=student_detail&id=<?= $studentId ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_student">
            <input type="hidden" name="student_id" value="<?= $studentId ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name"><?= __('student_first_name') ?> *</label>
                    <input type="text" id="first_name" name="first_name" required value="<?= htmlspecialchars($student['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label for="last_name"><?= __('student_last_name') ?> *</label>
                    <input type="text" id="last_name" name="last_name" required value="<?= htmlspecialchars($student['last_name']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email"><?= __('student_email') ?></label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars((string)$student['email']) ?>">
                </div>
                <div class="form-group">
                    <label for="phone"><?= __('student_phone') ?></label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars((string)$student['phone']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="birth_date"><?= __('student_birth_date') ?></label>
                    <input type="date" id="birth_date" name="birth_date" value="<?= htmlspecialchars((string)$student['birth_date']) ?>">
                </div>
                <div class="form-group">
                    <label for="level"><?= __('student_level') ?></label>
                    <select id="level" name="level">
                        <?php foreach ($levels as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $student['level'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <fieldset class="form-fieldset">
                <legend><?= __('student_guardian') ?></legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guardian_name"><?= __('student_guardian_name') ?></label>
                        <input type="text" id="guardian_name" name="guardian_name" value="<?= htmlspecialchars((string)$student['guardian_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="guardian_email"><?= __('student_guardian_email') ?></label>
                        <input type="email" id="guardian_email" name="guardian_email" value="<?= htmlspecialchars((string)$student['guardian_email']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="guardian_phone"><?= __('student_guardian_phone') ?></label>
                    <input type="tel" id="guardian_phone" name="guardian_phone" value="<?= htmlspecialchars((string)$student['guardian_phone']) ?>">
                </div>
            </fieldset>

            <div class="form-group">
                <label for="rating"><?= __('student_rating') ?></label>
                <input type="number" id="rating" name="rating" min="0" max="3500" value="<?= htmlspecialchars((string)$student['rating']) ?>">
            </div>

            <div class="form-group">
                <label for="notes"><?= __('student_notes') ?></label>
                <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars((string)$student['notes']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?= __('common_save') ?></button>
            <a href="?page=student_detail&id=<?= $studentId ?>" class="btn btn-secondary"><?= __('common_cancel') ?></a>
        </form>

        <div class="danger-zone">
            <form method="POST" action="?page=student_detail&id=<?= $studentId ?>"
                  onsubmit="return confirm('<?= __('common_confirm_delete') ?>');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_student">
                <input type="hidden" name="student_id" value="<?= $studentId ?>">
                <button type="submit" class="btn btn-danger btn-sm"><?= __('common_delete') ?></button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- ── Student card ───────────────────────────────────────────── -->
    <div class="card">
        <dl class="detail-list">
            <div><dt><?= __('student_email') ?></dt><dd><?= htmlspecialchars((string)$student['email']) ?: __('common_not_set') ?></dd></div>
            <div><dt><?= __('student_phone') ?></dt><dd><?= htmlspecialchars((string)$student['phone']) ?: __('common_not_set') ?></dd></div>
            <div><dt><?= __('student_birth_date') ?></dt><dd><?= schoolDate($student['birth_date']) ?></dd></div>
            <div><dt><?= __('student_guardian_name') ?></dt><dd><?= htmlspecialchars((string)$student['guardian_name']) ?: __('common_not_set') ?></dd></div>
            <div><dt><?= __('student_guardian_email') ?></dt><dd><?= htmlspecialchars((string)$student['guardian_email']) ?: __('common_not_set') ?></dd></div>
            <div><dt><?= __('student_guardian_phone') ?></dt><dd><?= htmlspecialchars((string)$student['guardian_phone']) ?: __('common_not_set') ?></dd></div>
        </dl>
        <?php if (!empty($student['notes'])): ?>
        <p><strong><?= __('student_notes') ?>:</strong><br><?= nl2br(htmlspecialchars($student['notes'])) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Classes attended ───────────────────────────────────────── -->
    <section class="circuits-section">
        <div class="section-header">
            <h2><?= __('student_classes') ?></h2>
        </div>

        <?php if (empty($studentClasses)): ?>
        <p class="empty-state"><?= __('student_no_classes') ?></p>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= __('class_name') ?></th>
                        <th><?= __('class_level') ?></th>
                        <th><?= __('class_schedule') ?></th>
                        <th><?= __('common_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentClasses as $class): ?>
                    <tr>
                        <td><a href="?page=class_detail&id=<?= (int)$class['id'] ?>"><?= htmlspecialchars($class['name']) ?></a></td>
                        <td><?= levelLabel($class['level']) ?></td>
                        <td><?= htmlspecialchars((string)$class['schedule']) ?></td>
                        <td>
                            <form method="POST" action="?page=student_detail&id=<?= $studentId ?>" style="display:inline;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="unenroll_class">
                                <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                <input type="hidden" name="class_id" value="<?= (int)$class['id'] ?>">
                                <button type="submit" class="btn btn-secondary btn-sm"><?= __('common_delete') ?></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($otherClasses)): ?>
        <form method="POST" action="?page=student_detail&id=<?= $studentId ?>" class="inline-form">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="enroll_class">
            <input type="hidden" name="student_id" value="<?= $studentId ?>">
            <select name="class_id" required>
                <option value=""><?= __('form_select') ?></option>
                <?php foreach ($otherClasses as $class): ?>
                <option value="<?= (int)$class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><?= __('class_enroll_existing') ?></button>
        </form>
        <?php endif; ?>
    </section>
</div>
