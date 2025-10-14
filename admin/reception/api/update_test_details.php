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

$data = json_decode(file_get_contents('php://input'), true);

$testId = filter_var($data['test_id'] ?? null, FILTER_VALIDATE_INT);

if (!$testId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Test ID.']);
    exit;
}

// Whitelist of columns that can be updated in the 'tests' table
$allowedFields = [
    // Patient Info
    'patient_name', 'phone_number', 'alternate_phone_no', 'gender', 'age', 'dob', 'parents', 'relation',
    // Test Info
    'test_name', 'limb', 'referred_by', 'test_done_by', 'assigned_test_date',
    // Financial Info
    'total_amount', 'advance_amount', 'discount'
];

$updateFields = [];
$params = [];

foreach ($allowedFields as $field) {
    if (isset($data[$field])) {
        $value = ($data[$field] === '' && in_array($field, ['dob', 'assigned_test_date'])) ? null : $data[$field];
        $updateFields[] = "{$field} = :{$field}";
        $params[$field] = $value;
    }
}

// --- NEW: Recalculate due_amount and payment_status if financial fields are present ---
if (isset($data['total_amount']) || isset($data['advance_amount']) || isset($data['discount'])) {
    // Fetch current values to ensure we have all parts of the calculation
    $stmtFetch = $pdo->prepare("SELECT total_amount, advance_amount, discount FROM tests WHERE test_id = ?");
    $stmtFetch->execute([$testId]);
    $currentFinancials = $stmtFetch->fetch();

    $total = (float)($data['total_amount'] ?? $currentFinancials['total_amount']);
    $advance = (float)($data['advance_amount'] ?? $currentFinancials['advance_amount']);
    $discount = (float)($data['discount'] ?? $currentFinancials['discount']);

    $params['due_amount'] = max(0, $total - $advance - $discount);
    $updateFields[] = "due_amount = :due_amount";

    if ($params['due_amount'] <= 0 && $advance > 0) {
        $newPaymentStatus = 'paid';
    } elseif ($advance > 0 && $advance < ($total - $discount)) {
        $newPaymentStatus = 'partial';
    } else {
        $newPaymentStatus = 'pending';
    }
    $updateFields[] = "payment_status = :payment_status";
    $params['payment_status'] = $newPaymentStatus;
}

if (empty($updateFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid fields to update.']);
    exit;
}

$params['test_id'] = $testId;
$params['branch_id'] = $_SESSION['branch_id'];

$sql = "UPDATE tests SET " . implode(', ', $updateFields) . " WHERE test_id = :test_id AND branch_id = :branch_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'], 'UPDATE', 'tests', $testId, null, $data);
    echo json_encode(['success' => true, 'message' => 'Test details updated successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Update Test Details Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>