<?php
/**
 * Chess School Platform - Students list + create
 *
 * ?page=students                → list (with search)
 * ?page=students&action=new     → create form
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/school.php';
require_once SRC_PATH . '/utils.php';

$instructor   = requireInstructor();
$instructorId = (int)$instructor['id'];

$showForm = ($_GET['action'] ?? '') === 'new';
$search   = trim($_GET['q'] ?? '');
$error    = null;
$form     = [
    'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '',
    'birth_date' => '', 'level' => 'beginner', 'rating' => '',
    'guardian_name' => '', 'guardian_email' => '', 'guardian_phone' => '', 'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_student') {
    foreach ($form as $key => $_) {
        $form[$key] = trim($_POST[$key] ?? '');
    }
    $showForm = true;

    try {
        checkCsrf();

        if ($form['first_name'] === '' || $form['last_name'] === '') {
            throw new Exception(__('error_required'));
        }
        if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception(__('error_email'));
        }

        $db = Database::get();
        $stmt = $db->prepare("
            INSERT INTO students (instructor_id, first_name, last_name, email, phone, birth_date, level, rating,
                                  guardian_name, guardian_email, guardian_phone, notes, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $instructorId,
            $form['first_name'],
            $form['last_name'],
            $form['email'] !== '' ? $form['email'] : null,
            $form['phone'] !== '' ? $form['phone'] : null,
            $form['birth_date'] !== '' ? $form['birth_date'] : null,
            $form['level'] !== '' ? $form['level'] : 'beginner',
            $form['rating'] !== '' ? (int)$form['rating'] : null,
            $form['guardian_name'] !== '' ? $form['guardian_name'] : null,
            $form['guardian_email'] !== '' ? $form['guardian_email'] : null,
            $form['guardian_phone'] !== '' ? $form['guardian_phone'] : null,
            $form['notes'] !== '' ? $form['notes'] : null,
        ]);
        $studentId = (int)$db->lastInsertId();

        // Optional: enroll straight into a class when we came from one
        $classId = (int)($_POST['class_id'] ?? 0);
        if ($classId) {
            enrollStudent($classId, $studentId, $instructorId);
            setFlash('success', __('class_enrolled'));
            header('Location: ?page=class_detail&id=' . $classId);
            exit;
        }

        setFlash('success', __('student_created'));
        header('Location: ?page=student_detail&id=' . $studentId);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$students = getStudents($instructorId, $search);
$levels   = schoolLevels();
$classes  = getClasses($instructorId, 'active');
$flash    = getFlash();
?>

<div class="container">
    <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h1><?= __('student_list_title') ?></h1>
        <div class="header-actions">
            <?php if (!$showForm): ?>
            <a href="?page=students&action=new" class="btn btn-primary"><?= __('student_new') ?></a>
            <?php else: ?>
            <a href="?page=students" class="btn btn-secondary"><?= __('common_cancel') ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($showForm): ?>
    <div class="create-section">
        <h2><?= __('student_create') ?></h2>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="?page=students">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create_student">

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name"><?= __('student_first_name') ?> *</label>
                    <input type="text" id="first_name" name="first_name" required value="<?= htmlspecialchars($form['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label for="last_name"><?= __('student_last_name') ?> *</label>
                    <input type="text" id="last_name" name="last_name" required value="<?= htmlspecialchars($form['last_name']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email"><?= __('student_email') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($form['email']) ?>">
                </div>
                <div class="form-group">
                    <label for="phone"><?= __('student_phone') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($form['phone']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="birth_date"><?= __('student_birth_date') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="date" id="birth_date" name="birth_date" value="<?= htmlspecialchars($form['birth_date']) ?>">
                </div>
                <div class="form-group">
                    <label for="level"><?= __('student_level') ?></label>
                    <select id="level" name="level">
                        <?php foreach ($levels as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $form['level'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <fieldset class="form-fieldset">
                <legend><?= __('student_guardian') ?></legend>
                <div class="form-row">
                    <div class="form-group">
                        <label for="guardian_name"><?= __('student_guardian_name') ?></label>
                        <input type="text" id="guardian_name" name="guardian_name" value="<?= htmlspecialchars($form['guardian_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="guardian_email"><?= __('student_guardian_email') ?></label>
                        <input type="email" id="guardian_email" name="guardian_email" value="<?= htmlspecialchars($form['guardian_email']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="guardian_phone"><?= __('student_guardian_phone') ?></label>
                    <input type="tel" id="guardian_phone" name="guardian_phone" value="<?= htmlspecialchars($form['guardian_phone']) ?>">
                </div>
            </fieldset>

            <div class="form-row">
                <div class="form-group">
                    <label for="rating"><?= __('student_rating') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <input type="number" id="rating" name="rating" min="0" max="3500" value="<?= htmlspecialchars($form['rating']) ?>">
                </div>
                <?php if (!empty($classes)): ?>
                <div class="form-group">
                    <label for="class_id"><?= __('nav_classes') ?> <small>(<?= __('common_optional') ?>)</small></label>
                    <select id="class_id" name="class_id">
                        <option value=""><?= __('form_select') ?></option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= (int)$class['id'] ?>"><?= htmlspecialchars($class['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="notes"><?= __('student_notes') ?> <small>(<?= __('common_optional') ?>)</small></label>
                <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($form['notes']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?= __('student_create') ?></button>
        </form>
    </div>
    <?php endif; ?>

    <?php if (!empty($students) || $search !== ''): ?>
    <form method="GET" class="search-bar">
        <input type="hidden" name="page" value="students">
        <input type="search" name="q" placeholder="<?= __('student_search') ?>" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-secondary btn-sm"><?= __('student_search') ?></button>
    </form>
    <?php endif; ?>

    <?php if (empty($students)): ?>
        <?php if (!$showForm): ?>
        <div class="empty-state">
            <div class="feature-icon">♟</div>
            <p><?= __('student_none') ?></p>
            <a href="?page=students&action=new" class="btn btn-primary"><?= __('student_create') ?></a>
        </div>
        <?php endif; ?>
    <?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><?= __('student_last_name') ?></th>
                    <th><?= __('student_level') ?></th>
                    <th><?= __('student_age') ?></th>
                    <th><?= __('student_email') ?></th>
                    <th><?= __('nav_classes') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><a href="?page=student_detail&id=<?= (int)$student['id'] ?>"><?= htmlspecialchars(studentName($student)) ?></a></td>
                    <td><?= levelLabel($student['level']) ?></td>
                    <td><?= studentAge($student['birth_date']) ?? __('common_not_set') ?></td>
                    <td><?= htmlspecialchars((string)$student['email']) ?></td>
                    <td><?= (int)$student['class_count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
