<?php

declare(strict_types=1);
session_start();

header('Content-Type: application/json');

require_once '../../common/db.php';
require_once '../../common/logger.php';

// Security checks: Allow 'admin' or 'superadmin'
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['uid']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: You do not have permission to perform this action.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Sanitize and validate input
$username = trim($data['username'] ?? '');
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $data['password'] ?? '';
$role = trim($data['role'] ?? '');
$branchId = filter_var($data['branch_id'], FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
$isActive = filter_var($data['is_active'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);

// Validation checks
if (empty($username) || empty($password) || empty($role) || $isActive === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username, password, role, and status are required.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

$allowedRoles = ['reception', 'doctor', 'jrdoctor', 'admin'];
if (!in_array($role, $allowedRoles)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user role specified.']);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO users (username, email, password_hash, role, branch_id, is_active) VALUES (:username, :email, :password_hash, :role, :branch_id, :is_active)";
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':username' => $username,
        ':email' => $email ?: null,
        ':password_hash' => $hashedPassword,
        ':role' => $role,
        ':branch_id' => $branchId,
        ':is_active' => $isActive
    ]);

    $newUserId = $pdo->lastInsertId();

    log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'] ?? 0, 'CREATE', 'users', (int)$newUserId, null, ['username' => $username, 'role' => $role]);

    echo json_encode(['success' => true, 'message' => 'User created successfully!']);
} catch (PDOException $e) {
    http_response_code(500);
    if ($e->getCode() == 23000) { // Integrity constraint violation (e.g., duplicate username/email)
        echo json_encode(['success' => false, 'message' => 'This username or email is already taken.']);
    } else {
        error_log("User Creation Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
    }
}
?>