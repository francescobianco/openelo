<?php
/**
 * Chess School Platform - Instructor Authentication
 *
 * The instructor is the primary user of the platform. Unlike the legacy
 * OpenELO flows (which are entirely email-confirmation based and password-less),
 * instructors have a real account: email + password + session.
 *
 * Both models coexist on purpose: legacy circuit/club/player pages keep
 * working without login, while the school area requires an authenticated
 * instructor.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

define('AUTH_SESSION_KEY', 'instructor_id');
define('AUTH_TOKEN_VERIFY', 'verify_email');
define('AUTH_TOKEN_RESET', 'password_reset');
define('AUTH_TOKEN_EXPIRY_HOURS', 48);

/**
 * Currently logged in instructor (cached per request), or null.
 */
function currentInstructor(): ?array {
    static $cached = null;
    static $loaded = false;

    if ($loaded) return $cached;
    $loaded = true;

    if (empty($_SESSION[AUTH_SESSION_KEY])) {
        return $cached = null;
    }

    $db = Database::get();
    $stmt = $db->prepare("SELECT * FROM instructors WHERE id = ? AND deleted_at IS NULL AND status = 'active'");
    $stmt->execute([(int)$_SESSION[AUTH_SESSION_KEY]]);
    $row = $stmt->fetch();

    if (!$row) {
        unset($_SESSION[AUTH_SESSION_KEY]);
        return $cached = null;
    }

    return $cached = $row;
}

function isLoggedIn(): bool {
    return currentInstructor() !== null;
}

function currentInstructorId(): ?int {
    $i = currentInstructor();
    return $i ? (int)$i['id'] : null;
}

/**
 * Redirect to login if not authenticated. Keeps the requested URL so we can
 * come back after login.
 */
function requireInstructor(): array {
    $instructor = currentInstructor();
    if (!$instructor) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '?page=dashboard';
        header('Location: ?page=login');
        exit;
    }
    return $instructor;
}

/**
 * Look up an instructor by email (active accounts only).
 */
function findInstructorByEmail(string $email): ?array {
    $db = Database::get();
    $stmt = $db->prepare("SELECT * FROM instructors WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([strtolower(trim($email))]);
    return $stmt->fetch() ?: null;
}

/**
 * Create a new instructor account. Returns the new instructor id.
 * Throws Exception on validation errors (message is already translated).
 */
function registerInstructor(array $data): int {
    $db = Database::get();

    $firstName = trim($data['first_name'] ?? '');
    $lastName  = trim($data['last_name'] ?? '');
    $email     = strtolower(trim($data['email'] ?? ''));
    $password  = (string)($data['password'] ?? '');
    $school    = trim($data['school_name'] ?? '');
    $city      = trim($data['city'] ?? '');
    $phone     = trim($data['phone'] ?? '');

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
        throw new Exception(__('error_required'));
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception(__('error_email'));
    }
    if (strlen($password) < 8) {
        throw new Exception(__('auth_error_password_short'));
    }
    if (findInstructorByEmail($email)) {
        throw new Exception(__('auth_error_email_taken'));
    }

    $stmt = $db->prepare("
        INSERT INTO instructors (first_name, last_name, email, password_hash, school_name, city, phone, lang, email_verified, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 'active', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $firstName,
        $lastName,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $school !== '' ? $school : null,
        $city !== '' ? $city : null,
        $phone !== '' ? $phone : null,
        getCurrentLang(),
    ]);

    return (int)$db->lastInsertId();
}

/**
 * Verify credentials and open a session. Returns the instructor row.
 * Throws Exception when credentials are invalid.
 */
function loginInstructor(string $email, string $password): array {
    $instructor = findInstructorByEmail($email);

    if (!$instructor || !password_verify($password, $instructor['password_hash'])) {
        throw new Exception(__('auth_error_invalid_credentials'));
    }
    if ($instructor['status'] !== 'active') {
        throw new Exception(__('auth_error_account_disabled'));
    }

    // Transparently upgrade the hash if PHP's default algorithm changed
    if (password_needs_rehash($instructor['password_hash'], PASSWORD_DEFAULT)) {
        $db = Database::get();
        $stmt = $db->prepare("UPDATE instructors SET password_hash = ? WHERE id = ?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $instructor['id']]);
    }

    startInstructorSession((int)$instructor['id']);
    return $instructor;
}

/**
 * Mark the session as authenticated for the given instructor.
 */
function startInstructorSession(int $instructorId): void {
    session_regenerate_id(true);
    $_SESSION[AUTH_SESSION_KEY] = $instructorId;

    $db = Database::get();
    $stmt = $db->prepare("UPDATE instructors SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$instructorId]);
}

function logoutInstructor(): void {
    unset($_SESSION[AUTH_SESSION_KEY]);
    session_regenerate_id(true);
}

/**
 * Create a single-purpose token (email verification, password reset).
 */
function createInstructorToken(int $instructorId, string $type): string {
    $db = Database::get();
    $token = bin2hex(random_bytes(32));

    // Invalidate previous tokens of the same type
    $stmt = $db->prepare("UPDATE instructor_tokens SET used = 1 WHERE instructor_id = ? AND type = ? AND used = 0");
    $stmt->execute([$instructorId, $type]);

    $stmt = $db->prepare("
        INSERT INTO instructor_tokens (instructor_id, token, type, used, expires_at, created_at)
        VALUES (?, ?, ?, 0, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $instructorId,
        $token,
        $type,
        date('Y-m-d H:i:s', time() + AUTH_TOKEN_EXPIRY_HOURS * 3600),
    ]);

    return $token;
}

/**
 * Consume a token. Returns the instructor row, or null when the token is
 * unknown, already used or expired.
 */
function consumeInstructorToken(string $token, string $type): ?array {
    $db = Database::get();

    $stmt = $db->prepare("SELECT * FROM instructor_tokens WHERE token = ? AND type = ? AND used = 0");
    $stmt->execute([$token, $type]);
    $row = $stmt->fetch();

    if (!$row) return null;
    if (!empty($row['expires_at']) && strtotime($row['expires_at']) < time()) return null;

    $stmt = $db->prepare("UPDATE instructor_tokens SET used = 1 WHERE id = ?");
    $stmt->execute([$row['id']]);

    $stmt = $db->prepare("SELECT * FROM instructors WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$row['instructor_id']]);

    return $stmt->fetch() ?: null;
}

function setInstructorPassword(int $instructorId, string $password): void {
    if (strlen($password) < 8) {
        throw new Exception(__('auth_error_password_short'));
    }
    $db = Database::get();
    $stmt = $db->prepare("UPDATE instructors SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $instructorId]);
}

function markInstructorVerified(int $instructorId): void {
    $db = Database::get();
    $stmt = $db->prepare("UPDATE instructors SET email_verified = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$instructorId]);
}

/**
 * CSRF token for the current session (shared by all school-area forms).
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function checkCsrf(): void {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || $sent === '' || !hash_equals(csrfToken(), $sent)) {
        throw new Exception(__('error_generic'));
    }
}

/**
 * Display name helper.
 */
function instructorName(array $instructor): string {
    return trim($instructor['first_name'] . ' ' . $instructor['last_name']);
}
