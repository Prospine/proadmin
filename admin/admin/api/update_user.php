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

$userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
$username = trim($data['username'] ?? '');
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$role = trim($data['role'] ?? '');
$branchId = filter_var($data['branch_id'], FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
$isActive = filter_var($data['is_active'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);

// Validation
if (!$userId || empty($username) || empty($role) || $isActive === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input. Please check all fields.']);
    exit;
}

$allowedRoles = ['reception', 'doctor', 'jrdoctor', 'admin']; // Admins cannot create/edit superadmins
if (!in_array($role, $allowedRoles)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user role specified.']);
    exit;
}

$updateFields = [
    'username' => $username,
    'email' => $email ?: null, // Store null if email is empty
    'role' => $role,
    'branch_id' => $branchId,
    'is_active' => $isActive
];

$sql = "UPDATE users SET username = :username, email = :email, role = :role, branch_id = :branch_id, is_active = :is_active";

$sql .= " WHERE id = :user_id AND role != 'superadmin'"; // Extra security: ensure we don't edit a superadmin
$updateFields['user_id'] = $userId;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($updateFields);

    if ($stmt->rowCount() > 0) {
        $logData = $updateFields;
        log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'] ?? 0, 'UPDATE', 'users', $userId, null, $logData);

        echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes were made or user not found.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    if ($e->getCode() == 23000) { // Integrity constraint violation (e.g., duplicate username/email)
        echo json_encode(['success' => false, 'message' => 'Database error: This username or email is already taken.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>