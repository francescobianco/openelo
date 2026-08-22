<?php
/**
 * Chess School Platform - Instructor dashboard
 *
 * The home of the platform once logged in: counters, quick actions,
 * the instructor's classes and their latest students.
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/school.php';
require_once SRC_PATH . '/utils.php';

$instructor = requireInstructor();
$instructorId = (int)$instructor['id'];

$stats   = getSchoolStats($instructorId);
$classes = getClasses($instructorId);
$students = array_slice(getStudents($instructorId), 0, 8);

$flash = getFlash();
?>

<div class="container">
    <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <?php if (!$instructor['email_verified']): ?>
    <div class="alert alert-warning">
        <?= __('auth_verify_banner') ?>
        <a href="?page=verify&resend=1"><?= __('auth_verify_resend') ?></a>
    </div>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1><?= __('dash_welcome') ?>, <?= htmlspecialchars($instructor['first_name']) ?></h1>
            <?php if (!empty($instructor['school_name'])): ?>
            <div class="circuit-meta" style="margin-top: 0.5rem;">
                <span><?= htmlspecialchars($instructor['school_name']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <div class="header-actions">
            <a href="?page=classes&action=new" class="btn btn-primary"><?= __('dash_new_class') ?></a>
            <a href="?page=students&action=new" class="btn btn-secondary"><?= __('dash_new_student') ?></a>
        </div>
    </div>

    <section class="stats">
        <a href="?page=classes" class="stat-card" style="text-decoration: none;">
            <div class="number"><?= $stats['classes'] ?></div>
            <div class="label"><?= __('dash_active_classes') ?></div>
        </a>
        <a href="?page=students" class="stat-card" style="text-decoration: none;">
            <div class="number"><?= $stats['students'] ?></div>
            <div class="label"><?= __('dash_total_students') ?></div>
        </a>
        <div class="stat-card">
            <div class="number"><?= $stats['enrollments'] ?></div>
            <div class="label"><?= __('dash_enrollments') ?></div>
        </div>
    </section>

    <?php if (empty($classes) && empty($students)): ?>
    <div class="empty-state">
        <div class="feature-icon">♟</div>
        <h2><?= __('dash_empty_title') ?></h2>
        <p><?= __('dash_empty_desc') ?></p>
        <a href="?page=classes&action=new" class="btn btn-primary"><?= __('class_create') ?></a>
    </div>
    <?php else: ?>

    <section class="circuits-section">
        <div class="section-header">
            <h2><?= __('dash_recent_classes') ?></h2>
            <a href="?page=classes" class="btn btn-secondary"><?= __('class_list_title') ?></a>
        </div>

        <?php if (empty($classes)): ?>
        <p class="empty-state"><?= __('class_none') ?></p>
        <?php else: ?>
        <div class="circuits-grid">
            <?php foreach (array_slice($classes, 0, 6) as $class): ?>
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
                <?php if (!empty($class['schedule'])): ?>
                <div class="circuit-date"><?= htmlspecialchars($class['schedule']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <?php if (!empty($students)): ?>
    <section class="circuits-section">
        <div class="section-header">
            <h2><?= __('dash_recent_students') ?></h2>
            <a href="?page=students" class="btn btn-secondary"><?= __('student_list_title') ?></a>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= __('student_full_name') ?></th>
                        <th><?= __('student_birth_year') ?></th>
                        <th><?= __('student_level') ?></th>
                        <th><?= __('nav_classes') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><a href="?page=student_detail&id=<?= (int)$student['id'] ?>"><?= htmlspecialchars(trim($student['last_name'] . ' ' . $student['first_name'])) ?></a></td>
                        <td><?= !empty($student['birth_date']) && strtotime($student['birth_date']) ? date('Y', strtotime($student['birth_date'])) : __('common_not_set') ?></td>
                        <td><?= levelLabel($student['level']) ?></td>
                        <td><?= (int)$student['class_count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php endif; ?>
</div>
