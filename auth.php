<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Escape output to help prevent Cross-Site Scripting (XSS).
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Get the currently logged-in user.
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Require the logged-in user to have an allowed role.
 * Applies case-insensitive matching.
 */
function require_login(): array
{
    global $pdo;

    $user = current_user();

    if (!$user) {
        header('Location: login.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT id, first_name, last_name, email, role,
                can_record_materials, is_active
         FROM users
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->execute([(int) ($user['id'] ?? 0)]);
    $freshUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$freshUser || (int) $freshUser['is_active'] !== 1) {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        header('Location: login.php');
        exit;
    }

    $_SESSION['user'] = $freshUser;

    return $freshUser;
}

/**
 * Generate or retrieve a CSRF token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify a supplied CSRF token.
 * Supports form field and AJAX header based tokens.
 */
function verify_csrf_token(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Verify the CSRF token on POST requests.
 */
function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $submittedToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if (!verify_csrf_token($submittedToken)) {
        http_response_code(400);
        exit('Invalid request token. Please go back and try again.');
    }
}

/**
 * Record an action in the audit log.
 */
function audit(
    PDO $pdo,
    string $action,
    string $entityType,
    ?int $entityId = null,
    string $details = ''
): void {
    $user = current_user();

    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs
            (user_id, action, entity_type, entity_id, details, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );

    $stmt->execute([
        $user['id'] ?? null,
        $action,
        $entityType,
        $entityId,
        $details,
    ]);
}

/**
 * Send a notification to a user.
 */
function notify_user(
    PDO $pdo,
    int $userId,
    string $title,
    string $message
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO notifications
            (user_id, title, message, is_read, created_at)
         VALUES (?, ?, ?, 0, NOW())'
    );

    $stmt->execute([
        $userId,
        $title,
        $message,
    ]);
}

/**
 * Redirect to the dashboard according to the user role.
 */
function redirect_dashboard(string $role): void
{
    $normalized = strtolower(trim($role));
    $routes = [
        'client' => 'client_dashboard.php',
        'personnel' => 'personnel_dashboard.php',
        'manager' => 'manager_dashboard.php',
    ];

    $destination = $routes[$normalized] ?? 'login.php';

    header('Location: ' . $destination);
    exit;
}
function require_role(array $allowedRoles): array
{
    $user = require_login();

    $allowed = array_map('strtolower', $allowedRoles);
    $role = strtolower((string) ($user['role'] ?? ''));

    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('Access denied.');
    }

    return $user;
}