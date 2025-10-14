<?php

declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

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

$userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
$password = $data['new_password'] ?? '';

// Validation
if (!$userId || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID and password are required.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :user_id AND role != 'superadmin'");
    $stmt->execute([
        'password_hash' => $hashedPassword,
        'user_id' => $userId
    ]);

    if ($stmt->rowCount() > 0) {
        log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'] ?? 0, 'UPDATE', 'users', $userId, null, ['action' => 'password_changed']);
        echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not update password. User not found or is a superadmin.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Password Change Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
