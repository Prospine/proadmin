<?php
// auth.php
declare(strict_types=1);

// --- Session Hardening ---
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}


// --- Security Headers ---
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");

// Include DB connection
require __DIR__ . '/db.php';

// --- Auth Check ---
if (!isset($_SESSION['uid'], $_SESSION['role'], $_SESSION['ua'], $_SESSION['ip'])) {
    header('Location: ../../login.php');
    exit();
}

// Verify User-Agent and IP match session
$current_ua = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
$current_ip = bin2hex(@inet_pton($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));

if ($_SESSION['ua'] !== $current_ua || $_SESSION['ip'] !== $current_ip) {
    session_destroy();
    header('Location: ../../login.php');
    exit();
}

// Optional: Role-based access control
function require_role(string $role): void
{
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
}

// Optional helper: get logged-in user
function current_user(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: [];
}
