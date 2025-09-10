<?php

declare(strict_types=1);
session_start();

// -------------------------
// Error Reporting (Dev Only)
// -------------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// -------------------------
// Auth / Session Checks
// -------------------------
require_once '../../common/auth.php';
require_once '../../common/db.php'; // PDO connection

header('Content-Type: application/json');

// Check login & branch
if (!isset($_SESSION['uid'], $_SESSION['branch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$branchId = $_SESSION['branch_id'];
$type     = $_GET['type'] ?? '';
$id       = $_GET['id'] ?? null;

if (!$id || !in_array($type, ['quick', 'test'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    if ($type === 'quick') {
        $stmt = $pdo->prepare("
            SELECT inquiry_id, name, phone_number, age, gender, referralSource, 
                   chief_complain, review, expected_visit_date, created_at, status
            FROM quick_inquiry
            WHERE inquiry_id = :id AND branch_id = :branch_id
            LIMIT 1
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT inquiry_id, name, testname, reffered_by, mobile_number, 
                   expected_visit_date, created_at, status
            FROM test_inquiry
            WHERE inquiry_id = :id AND branch_id = :branch_id
            LIMIT 1
        ");
    }

    $stmt->execute([
        ':id'        => $id,
        ':branch_id' => $branchId
    ]);

    $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($inquiry) {
        echo json_encode(['success' => true, 'data' => $inquiry]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
