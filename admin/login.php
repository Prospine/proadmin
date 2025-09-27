<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// --- Session Hardening ---
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

// Basic CSP/headers (tweak as needed)
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");

// CSRF helpers
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function verify_csrf(string $token): bool
{
    return hash_equals($_SESSION['csrf'] ?? '', $token);
}

// Utility: pack IP for DB (IPv4/IPv6)
function packed_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $packed = @inet_pton($ip);
    return $packed !== false ? $packed : inet_pton('0.0.0.0');
}

// Role routes (update to your paths)
function role_redirect(string $role): never
{
    switch ($role) {
        case 'superadmin':
            header('Location: /admin/superadmin/dashboard.php');
            exit;
        case 'admin':
            header('Location: /admin/admin/dashboard.php');
            exit;
        case 'doctor':
            header('Location: /admin/doctor/dashboard.php');
            exit;
        case 'jrdoctor':
            header('Location: /admin/jrdoctor/dashboard.php');
            exit;
        case 'reception':
            header('Location: reception/index.html');
            exit;
        default:
            header('Location: /');
            exit;
    }
}

require __DIR__ . '/common/db.php';

// Lockout policy
const MAX_ATTEMPTS = 5;
const LOCK_MINUTES = 15;

// Get & sanitize POST
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token    = $_POST['csrf'] ?? '';
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // CSRF check
    if (!verify_csrf($token)) {
        http_response_code(400);
        $err = 'Invalid session token. Please refresh and try again.';
    }

    // Basic validation (don’t leak which field is wrong)
    if (!$err) {
        if ($username === '' || strlen($username) > 50 || $password === '' || strlen($password) > 200) {
            $err = 'Invalid credentials.';
        }
    }

    // Brute force / lockout checks
    if (!$err) {
        $ip = packed_ip();
        $pdo->beginTransaction();
        try {
            // Fetch row for (username, ip)
            $sel = $pdo->prepare("SELECT id, attempt_count, locked_until FROM login_attempts
                            WHERE username = ? AND ip = ?");
            $sel->execute([$username, $ip]);
            $row = $sel->fetch();

            $now = new DateTimeImmutable('now');
            $lockedUntil = isset($row['locked_until']) ? new DateTimeImmutable($row['locked_until']) : null;

            if ($lockedUntil && $now < $lockedUntil) {
                $pdo->commit();
                $err = 'Too many attempts. Please try again later.';
            } else {
                // Continue with auth
                $pdo->commit();
            }
        } catch (Throwable $e) {
            $pdo->rollBack();
            $err = 'Please try again.';
        }
    }

    // Attempt auth
    if (!$err) {
        $stmt = $pdo->prepare("
    SELECT id, username, password_hash, role, is_active, branch_id 
    FROM users 
    WHERE username = ? 
    LIMIT 1
");

        $stmt->execute([$username]);
        $user = $stmt->fetch();

        $valid = $user && (int)$user['is_active'] === 1 && password_verify($password, $user['password_hash']);

        // Update attempts atomically
        $ip = packed_ip();
        $pdo->beginTransaction();
        try {
            // Upsert attempt row
            $sel = $pdo->prepare("SELECT id, attempt_count FROM login_attempts WHERE username = ? AND ip = ? FOR UPDATE");
            $sel->execute([$username, $ip]);
            $row = $sel->fetch();

            if ($valid) {
                // success: clear attempts
                if ($row) {
                    $del = $pdo->prepare("DELETE FROM login_attempts WHERE id = ?");
                    $del->execute([$row['id']]);
                }
                $pdo->commit();

                // Successful login: fixate-resistant session
                session_regenerate_id(true);
                $_SESSION['uid']       = (int)$user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['branch_id'] = (int)$user['branch_id'];
                $_SESSION['ua']        = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
                $_SESSION['ip']        = bin2hex($ip);

                // Optionally store last_login timestamp on users table

                role_redirect($user['role']);
            } else {
                // failure: increment attempts
                $count = $row ? (int)$row['attempt_count'] + 1 : 1;
                $lockedUntil = null;

                if ($count >= MAX_ATTEMPTS) {
                    $lockedUntilObj = (new DateTimeImmutable('now'))->modify('+' . LOCK_MINUTES . ' minutes');
                    $lockedUntil = $lockedUntilObj->format('Y-m-d H:i:s');
                    $count = 0; // reset after lock, or keep—your choice
                }

                if ($row) {
                    $upd = $pdo->prepare("UPDATE login_attempts
              SET attempt_count = ?, last_attempt = NOW(), locked_until = ?
              WHERE id = ?");
                    $upd->execute([$count, $lockedUntil, $row['id']]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO login_attempts (username, ip, attempt_count, last_attempt, locked_until)
                                VALUES (?, ?, ?, NOW(), ?)");
                    $ins->execute([$username, $ip, $count, $lockedUntil]);
                }

                $pdo->commit();
                // Generic error to avoid username enumeration
                $err = 'Invalid credentials.';
            }
        } catch (Throwable $e) {
            $pdo->rollBack();
            $err = 'Please try again.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ProSpine Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styling for the login error message */
        .error-message {
            padding: 12px 24px;
            margin-bottom: 20px;
            border: 1px solid #e57373;
            border-radius: 8px;
            background-color: #ffebee;
            color: #c62828;
            font-size: 0.9em;
            text-align: center;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="assets/images/image.png" alt="ProSpine Logo" class="logo">

            <a href="index.html" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        </div>
    </header>
    <div class="container">

        <!-- Left Panel -->
        <div class="left-panel">
            <div class="login-box">
                <h2>Sign in</h2>
                <p class="subtitle">Use your ProSpine credentials. You’ll be routed to the correct dashboard based on
                    your role.</p>

                <?php if (!empty($err)) : ?>
                    <div class="error-message">
                        <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <form method="POST" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="input-group">
                        <label for="username">Username</label>
                        <input id="username" name="username" maxlength="50" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" minlength="8" required>
                        <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
                    </div>

                    <button type="submit" class="btn">Login</button>
                </form>

                <div class="footer-text">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>

</html>