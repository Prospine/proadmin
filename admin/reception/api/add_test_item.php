<?php

declare(strict_types=1);
session_start();

require_once '../../common/db.php';
require_once '../../common/logger.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['uid'], $_SESSION['branch_id'], $_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$branchId = $_SESSION['branch_id'];
$userId = $_SESSION['uid'];
$username = $_SESSION['username'];

$data = json_decode(file_get_contents('php://input'), true);

// --- Validation and Data Sanitization ---
$testId = filter_var($data['test_id'] ?? null, FILTER_VALIDATE_INT);
$testName = trim($data['test_name'] ?? '');
$assignedTestDate = $data['assigned_test_date'] ?? '';
$testDoneBy = trim($data['test_done_by'] ?? '');
$totalAmount = filter_var($data['total_amount'] ?? 0, FILTER_VALIDATE_FLOAT);

if (!$testId || empty($testName) || empty($assignedTestDate) || empty($testDoneBy) || $totalAmount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Test Name, Assigned Date, Done By, and Total Amount are required.']);
    exit;
}

// Handle optional fields gracefully, setting them to null if empty
$limb = !empty($data['limb']) ? $data['limb'] : null;
$referredBy = !empty(trim($data['referred_by'] ?? '')) ? trim($data['referred_by']) : null;
$discount = filter_var($data['discount'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.0;
$advanceAmount = filter_var($data['advance_amount'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.0;
$dueAmount = filter_var($data['due_amount'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.0;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO test_items (test_id, assigned_test_date, test_name, limb, referred_by, test_done_by, total_amount, advance_amount, due_amount, discount, payment_method, test_status)
         VALUES (:test_id, :assigned_test_date, :test_name, :limb, :referred_by, :test_done_by, :total_amount, :advance_amount, :due_amount, :discount, :payment_method, :test_status)"
    );

    $stmt->execute([
        ':test_id' => $testId,
        ':assigned_test_date' => $assignedTestDate,
        ':test_name' => $testName,
        ':limb' => $limb,
        ':referred_by' => $referredBy,
        ':test_done_by' => $testDoneBy,
        ':total_amount' => $totalAmount,
        ':advance_amount' => $advanceAmount,
        ':due_amount' => $dueAmount,
        ':discount' => $discount,
        ':payment_method' => $data['payment_method'] ?? 'cash',
        ':test_status' => $data['test_status'] ?? 'pending'
    ]);

    $newItemId = $pdo->lastInsertId();
    log_activity($pdo, $userId, $username, $branchId, 'CREATE', 'test_items', (int)$newItemId, null, ['test_id' => $testId, 'test_name' => $testName]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'New test item added successfully.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    error_log("Add Test Item Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>