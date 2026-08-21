<?php
/**
 * Chess School Platform - Domain helpers
 *
 * Every query here is scoped to a single instructor: an instructor can only
 * ever see and touch their own classes and students.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Available class/student levels.
 */
function schoolLevels(): array {
    return [
        'beginner'     => __('level_beginner'),
        'intermediate' => __('level_intermediate'),
        'advanced'     => __('level_advanced'),
        'competitive'  => __('level_competitive'),
    ];
}

function levelLabel(?string $level): string {
    $levels = schoolLevels();
    return $levels[$level ?? ''] ?? __('common_not_set');
}

// ── Classes ──────────────────────────────────────────────────────────────────

/**
 * All classes of an instructor, with the enrolled-student count.
 */
function getClasses(int $instructorId, ?string $status = null): array {
    $db = Database::get();
    $sql = "
        SELECT c.*,
            (SELECT COUNT(*) FROM class_students cs
              JOIN students s ON s.id = cs.student_id
              WHERE cs.class_id = c.id AND cs.status = 'active' AND s.deleted_at IS NULL) AS student_count
        FROM classes c
        WHERE c.instructor_id = ? AND c.deleted_at IS NULL
    ";
    $params = [$instructorId];
    if ($status !== null) {
        $sql .= " AND c.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY c.status ASC, c.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * One class, only if it belongs to the given instructor.
 */
function getClass(int $classId, int $instructorId): ?array {
    $db = Database::get();
    $stmt = $db->prepare("SELECT * FROM classes WHERE id = ? AND instructor_id = ? AND deleted_at IS NULL");
    $stmt->execute([$classId, $instructorId]);
    return $stmt->fetch() ?: null;
}

/**
 * Students enrolled in a class.
 */
function getClassStudents(int $classId): array {
    $db = Database::get();
    $stmt = $db->prepare("
        SELECT s.*, cs.enrolled_at, cs.status AS enrollment_status
        FROM class_students cs
        JOIN students s ON s.id = cs.student_id
        WHERE cs.class_id = ? AND cs.status = 'active' AND s.deleted_at IS NULL
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute([$classId]);
    return $stmt->fetchAll();
}

// ── Students ─────────────────────────────────────────────────────────────────

/**
 * All students of an instructor, optionally filtered by a free-text search.
 */
function getStudents(int $instructorId, string $search = ''): array {
    $db = Database::get();
    $sql = "
        SELECT s.*,
            (SELECT COUNT(*) FROM class_students cs
              JOIN classes c ON c.id = cs.class_id
              WHERE cs.student_id = s.id AND cs.status = 'active' AND c.deleted_at IS NULL) AS class_count
        FROM students s
        WHERE s.instructor_id = ? AND s.deleted_at IS NULL
    ";
    $params = [$instructorId];

    if ($search !== '') {
        $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= " ORDER BY s.last_name, s.first_name";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * One student, only if they belong to the given instructor.
 */
function getStudent(int $studentId, int $instructorId): ?array {
    $db = Database::get();
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ? AND instructor_id = ? AND deleted_at IS NULL");
    $stmt->execute([$studentId, $instructorId]);
    return $stmt->fetch() ?: null;
}

/**
 * Classes a student is enrolled in.
 */
function getStudentClasses(int $studentId): array {
    $db = Database::get();
    $stmt = $db->prepare("
        SELECT c.*, cs.enrolled_at
        FROM class_students cs
        JOIN classes c ON c.id = cs.class_id
        WHERE cs.student_id = ? AND cs.status = 'active' AND c.deleted_at IS NULL
        ORDER BY c.name
    ");
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

/**
 * Students of an instructor NOT yet enrolled in the given class.
 */
function getStudentsNotInClass(int $instructorId, int $classId): array {
    $db = Database::get();
    $stmt = $db->prepare("
        SELECT s.* FROM students s
        WHERE s.instructor_id = ? AND s.deleted_at IS NULL
          AND NOT EXISTS (
            SELECT 1 FROM class_students cs
            WHERE cs.student_id = s.id AND cs.class_id = ? AND cs.status = 'active'
          )
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute([$instructorId, $classId]);
    return $stmt->fetchAll();
}

/**
 * Enroll a student in a class. Both must belong to the instructor.
 */
function enrollStudent(int $classId, int $studentId, int $instructorId): void {
    $db = Database::get();

    if (!getClass($classId, $instructorId) || !getStudent($studentId, $instructorId)) {
        throw new Exception(__('error_not_found'));
    }

    $stmt = $db->prepare("SELECT * FROM class_students WHERE class_id = ? AND student_id = ?");
    $stmt->execute([$classId, $studentId]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'active') {
            throw new Exception(__('class_already_enrolled'));
        }
        // Re-activate a previous enrollment instead of creating a duplicate
        $stmt = $db->prepare("UPDATE class_students SET status = 'active', left_at = NULL, enrolled_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$existing['id']]);
        return;
    }

    $stmt = $db->prepare("INSERT INTO class_students (class_id, student_id, status, enrolled_at) VALUES (?, ?, 'active', CURRENT_TIMESTAMP)");
    $stmt->execute([$classId, $studentId]);
}

/**
 * Remove a student from a class (soft: the enrollment is kept as history).
 */
function unenrollStudent(int $classId, int $studentId, int $instructorId): void {
    if (!getClass($classId, $instructorId)) {
        throw new Exception(__('error_not_found'));
    }
    $db = Database::get();
    $stmt = $db->prepare("UPDATE class_students SET status = 'left', left_at = CURRENT_TIMESTAMP WHERE class_id = ? AND student_id = ?");
    $stmt->execute([$classId, $studentId]);
}

// ── Misc ─────────────────────────────────────────────────────────────────────

/**
 * Instructor-wide counters for the dashboard.
 */
function getSchoolStats(int $instructorId): array {
    $db = Database::get();

    $stmt = $db->prepare("SELECT COUNT(*) FROM classes WHERE instructor_id = ? AND deleted_at IS NULL AND status = 'active'");
    $stmt->execute([$instructorId]);
    $classes = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE instructor_id = ? AND deleted_at IS NULL");
    $stmt->execute([$instructorId]);
    $students = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM class_students cs
        JOIN classes c ON c.id = cs.class_id
        WHERE c.instructor_id = ? AND c.deleted_at IS NULL AND cs.status = 'active'
    ");
    $stmt->execute([$instructorId]);
    $enrollments = (int)$stmt->fetchColumn();

    return ['classes' => $classes, 'students' => $students, 'enrollments' => $enrollments];
}

/**
 * Age in years from a birth date, or null.
 */
function studentAge(?string $birthDate): ?int {
    if (empty($birthDate)) return null;
    try {
        $dob = new DateTime($birthDate);
    } catch (Exception $e) {
        return null;
    }
    return (int)$dob->diff(new DateTime())->y;
}

/**
 * Full name helper.
 */
function studentName(array $student): string {
    return trim($student['first_name'] . ' ' . $student['last_name']);
}

/**
 * Small date formatter that tolerates empty values.
 */
function schoolDate(?string $value): string {
    if (empty($value)) return __('common_not_set');
    $ts = strtotime($value);
    if (!$ts) return __('common_not_set');
    return date(getCurrentLang() === 'it' ? 'd/m/Y' : 'M j, Y', $ts);
}
